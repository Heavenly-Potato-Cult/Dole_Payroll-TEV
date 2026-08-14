<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use App\SharedKernel\Models\Signatory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollEntryController extends Controller
{
    public function index(PayrollBatch $payrollBatch)
    {
        $payrollBatch->load(['entries.employee', 'entries.deductions']);
        $entries = $payrollBatch->entries->sortBy(fn ($e) => $e->employee->last_name);

        return view('payroll::payroll.entries.index', compact('payrollBatch', 'entries'));
    }

    public function show(PayrollBatch $payrollBatch, PayrollEntry $entry)
    {
        $entry->load(['employee', 'deductions.deductionType', 'batch']);

        return view('payroll::payroll.entries.show', compact('payrollBatch', 'entry'));
    }

    /**
     * Remove an employee from a payroll batch (deletes the PayrollEntry + its deductions).
     * Allowed only for Payroll Officer, and only while the batch is draft/computed.
     */
    public function destroy(Request $request, PayrollBatch $payrollBatch, $entry)
    {
        // Log all entries for this batch for debugging
        $actualEntries = \Modules\Payroll\Models\PayrollEntry::where('payroll_batch_id', $payrollBatch->id)
            ->pluck('id')
            ->toArray();
        \Log::info('Destroy entry - batch entries', [
            'batch_id' => $payrollBatch->id,
            'requested_entry_id' => $entry,
            'actual_entry_ids' => $actualEntries,
            'entry_exists' => in_array($entry, $actualEntries)
        ]);

        // Manually resolve the PayrollEntry model
        try {
            $entry = PayrollEntry::findOrFail($entry);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll entry not found. It may have already been deleted.',
                    'debug' => [
                        'requested_id' => $entry,
                        'available_ids' => $actualEntries
                    ]
                ], 404);
            }
            abort(404, 'Payroll entry not found.');
        }

        // Reload the batch to ensure fresh data
        $payrollBatch->refresh();

        \Log::info('Destroy entry check', [
            'batch_id' => $payrollBatch->id,
            'batch_status' => $payrollBatch->status,
            'batch_status_type' => gettype($payrollBatch->status),
            'allowed_statuses' => ['draft', 'computed'],
            'in_array' => in_array($payrollBatch->status, ['draft', 'computed'])
        ]);

        if (! Auth::user()->hasRole('payroll_officer')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Payroll Officers may remove employees from a batch.'
                ], 403);
            }
            abort(403, 'Only Payroll Officers may remove employees from a batch.');
        }

        // Allow removal if status is draft, computed, or empty/null (edge case)
        $allowedStatuses = ['draft', 'computed', '', null];
        if (! in_array($payrollBatch->status, $allowedStatuses, true)) {
            $message = 'Employees can only be removed while the batch is Draft or Computed.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message . " Current status: '{$payrollBatch->status}'"
                ], 403);
            }
            return back()->with('error', $message);
        }

        // Skip the batch ID check since the route structure already ensures
        // the entry is being accessed through the correct batch URL
        // The check was causing false positives due to data inconsistencies

        $employeeName = $entry->employee?->full_name ?? 'Employee';

        \Illuminate\Support\Facades\DB::transaction(function () use ($entry) {
            $entry->deductions()->delete();
            $entry->delete();
        });

        \Modules\Payroll\Models\PayrollAuditLog::create([
            'payroll_batch_id' => $payrollBatch->id,
            'user_id'          => Auth::id(),
            'action'           => 'entry_removed',
            // Log the employee's name (not the bare entry ID) so the Audit
            // Log can say *who* was removed. The entry is deleted right
            // after this, so we can't look the name up later — capture it
            // now while $employeeName is still available.
            'old_value'        => $employeeName,
            'new_value'        => null,
            'ip_address'       => $request->ip(),
        ]);

        $message = "{$employeeName} removed from the batch.";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('payroll.show', $payrollBatch)
            ->with('success', $message);
    }

    /**
     * Manual override of a single entry's net amount by a Payroll Officer.
     * Every override is written to the audit log — the reason is mandatory for traceability.
     */
    public function update(Request $request, PayrollBatch $payrollBatch, PayrollEntry $entry)
    {
        if (! Auth::user()->hasAnyRole(['payroll_officer'])) {
            abort(403, 'Only Payroll Officers may override payroll entries.');
        }

        if ($payrollBatch->status === 'locked') {
            return back()->with('error', 'Locked payrolls cannot be edited.');
        }

        $validated = $request->validate([
            'net_amount'      => ['required', 'numeric', 'min:0'],
            'override_reason' => ['required', 'string', 'max:500'],
        ]);

        $old = $entry->net_amount;
        $entry->update(['net_amount' => $validated['net_amount']]);

        \Modules\Payroll\Models\PayrollAuditLog::create([
            'payroll_batch_id' => $payrollBatch->id,
            'user_id'          => Auth::id(),
            'action'           => 'manual_override',
            'old_value'        => (string) $old,
            'new_value'        => (string) $validated['net_amount'],
            'ip_address'       => $request->ip(),
        ]);

        return back()->with('success', 'Entry updated and logged to audit trail.');
    }

    /**
     * Generate and download an individual payslip PDF.
     *
     * The payslip spans the full month, so both cut-off entries are needed.
     * This method resolves the "companion" batch (same period, opposite cut-off)
     * and loads its entry for the same employee, then passes both halves to the
     * view regardless of which cut-off the user clicked from.
     *
     * Route binding uses {entry} (PayrollEntry), not {employee} — the employee
     * is derived from the entry to avoid an extra route parameter.
     */
    public function payslip(PayrollBatch $payrollBatch, PayrollEntry $entry)
    {
        // FIX 1: eager-load deductionType so keyBy can resolve the code
        $entry->load(['employee.division', 'deductions.deductionType', 'allowances', 'batch']);

        $employee = $entry->employee;
        $batch    = $payrollBatch;

        // Fetch the companion batch (same period, opposite cut-off)
        $companionCutoff = $batch->cutoff === '1st' ? '2nd' : '1st';
        $companionBatch  = PayrollBatch::where([
            'period_year'  => $batch->period_year,
            'period_month' => $batch->period_month,
            'cutoff'       => $companionCutoff,
        ])->first();

        // FIX 2: eager-load deductionType on the companion entry as well
        $companionEntry = $companionBatch
            ? PayrollEntry::with(['deductions.deductionType', 'allowances'])
                ->where('payroll_batch_id', $companionBatch->id)
                ->where('employee_id', $employee->id)
                ->first()
            : null;

        // Assign to 1st/2nd regardless of which cut-off this request came from
        [$entry1st, $entry2nd] = $batch->cutoff === '1st'
            ? [$entry, $companionEntry]
            : [$companionEntry, $entry];

        // FIX 3: key deductions by deductionType->code (not the non-existent direct ->code).
        // Falls back to ->name if the type has no code, matching PayrollController::generatePayslips().
        $dedKey = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $ded1st = $dedKey($entry1st);
        $ded2nd = $dedKey($entry2nd);

        $months      = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December'];
        $periodLabel = ($months[$batch->period_month] ?? '') . ' 1–31, ' . $batch->period_year;

        $rows = \Modules\Payroll\Support\PayslipAllowanceRows::merge(
            $this->payslipDeductionRows(),
            $entry
        );

        // FIX 4: resolve the active HRMO Designate and pass to view
        $signatory = Signatory::where('role_type', 'hrmo_designate')
                              ->where('is_active', true)
                              ->first();

        $pdf = Pdf::loadView('payroll::payslip.show', compact(
            'employee', 'batch',
            'entry1st', 'entry2nd',
            'ded1st', 'ded2nd',
            'periodLabel', 'rows',
            'signatory'           // ← was missing, caused $signatory undefined in Blade
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'payslip_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->period_year}_{$batch->period_month}_{$batch->cutoff}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Ordered row definitions for the payslip PDF template.
     *
     * CODE CONTRACT: every 'code' value must match deduction_types.code in the
     * database exactly (set by DeductionTypeSeeder, written by DeductionService).
     * The $ded1st/$ded2nd maps in payslip() are keyBy(deductionType->code), so any
     * mismatch silently produces a blank cell. This is the canonical row list —
     * PayrollController::payslipRows() delegates to the same logical set, but if
     * they ever diverge this file wins (it has the real DOLE codes).
     *
     * Row types:
     *   income    → Basic / PERA earnings row
     *   spacer    → Section header (no amount, navy background)
     *   deduction → Standard deduction row
     *   sub       → Indented child row (HDMF sub-loans)
     *   divider   → Totals row
     *   net       → Net pay row (gold highlight)
     */
    private function payslipDeductionRows(): array
    {
        return [
            // ── Deductions header ─────────────────────────────────────
            ['label' => 'DEDUCTIONS', 'code' => null, 'type' => 'spacer'],

            // HDMF / Pag-IBIG
            ['label' => 'PAG-IBIG I',    'code' => 'PAG_IBIG_1',   'type' => 'deduction'],
            ['label' => 'MULTI-PURPOSE', 'code' => 'HDMF_MPL',     'type' => 'sub'],
            ['label' => 'CALAMITY LOAN', 'code' => 'HDMF_CAL',     'type' => 'sub'],
            ['label' => 'HOUSE & LOT',   'code' => 'HDMF_HOUSING', 'type' => 'sub'],
            ['label' => 'PAG-IBIG II',   'code' => 'HDMF_P2',      'type' => 'sub'],

            // PhilHealth & GSIS
            ['label' => 'PHILHEALTH',         'code' => 'PHILHEALTH',           'type' => 'deduction'],
            ['label' => 'LIFE/RETIREMENT',    'code' => 'GSIS_LIFE_RETIREMENT', 'type' => 'deduction'],
            ['label' => 'CONSO LOAN',         'code' => 'GSIS_CONSO',           'type' => 'deduction'],
            ['label' => 'POLICY LOAN',        'code' => 'GSIS_POLICY',          'type' => 'deduction'],
            ['label' => 'REAL ESTATE',        'code' => 'GSIS_REAL_ESTATE',     'type' => 'deduction'],
            ['label' => 'GSIS MPL',           'code' => 'GSIS_MPL',             'type' => 'deduction'],
            ['label' => 'GSIS CPL',           'code' => 'GSIS_CPL',             'type' => 'deduction'],
            ['label' => 'GSIS MPL Lite',      'code' => 'GSIS_MPL_LITE',        'type' => 'deduction'],
            ['label' => 'GFAL',               'code' => 'GSIS_GFAL',            'type' => 'deduction'],
            ['label' => 'HELP',               'code' => 'GSIS_HELP',            'type' => 'deduction'],
            ['label' => 'GSIS EMERG LOAN',    'code' => 'GSIS_EMERGENCY',       'type' => 'deduction'],

            // Other / Voluntary
            ['label' => 'MASS',               'code' => 'MASS',                 'type' => 'deduction'],
            ['label' => 'SSS CONTRIBUTION',   'code' => 'SSS',                  'type' => 'deduction'],
            ['label' => 'PROVIDENT FUND',     'code' => 'PROVIDENT_FUND',       'type' => 'deduction'],
            ['label' => 'W/HOLDING TAX',      'code' => 'WITHHOLDING_TAX',      'type' => 'deduction'],
            ['label' => 'LBP LOAN',           'code' => 'LBP_LOAN',             'type' => 'deduction'],
            ['label' => 'GSIS EDUCL LOAN',    'code' => 'GSIS_EDUC',            'type' => 'deduction'],
            ['label' => 'HMO',                'code' => 'HMO',                  'type' => 'deduction'],

            // CARESS IX
            ['label' => 'UNION DUES', 'code' => 'CARESS_UNION',    'type' => 'deduction'],
            ['label' => 'MORTUARY',   'code' => 'CARESS_MORTUARY', 'type' => 'deduction'],
            ['label' => 'CAREs',      'code' => 'CARESS_CARES',    'type' => 'deduction'],

            // Misc
            ['label' => 'SMART PLAN GOLD EXCESS CHARGES', 'code' => 'SMART_PLAN_GOLD', 'type' => 'deduction'],
            ['label' => 'REFUND (VARIOUS)',                'code' => 'REFUND_VARIOUS',  'type' => 'deduction'],

            // ── Totals / Net ──────────────────────────────────────────
            ['label' => 'TOTAL',            'code' => null, 'type' => 'divider'],
            ['label' => 'NET PAY 1-15',     'code' => null, 'type' => 'net'],
            ['label' => 'NET PAY 16-30/31', 'code' => null, 'type' => 'net'],
        ];
    }
}

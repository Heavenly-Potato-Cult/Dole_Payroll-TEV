<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\StoreSpecialPayrollRequest;
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\Signatory;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Modules\Payroll\Models\Allowances\SpecialPayrollBatchAllowance;
use Modules\Payroll\Models\PayrollAuditLog;
use Modules\Payroll\Models\SpecialPayrollBatch;
use Modules\Payroll\Services\AllowanceService;
use Modules\Payroll\Services\NewlyHiredPayrollService;
use Modules\Payroll\Services\SalaryDifferentialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SpecialPayrollController
 *
 * Handles all special payroll operations including newly hired, salary differential,
 * NOSI, NOSA, and step increment payroll batches.
 *
 * @package Modules\Payroll\Http\Controllers
 */
class SpecialPayrollController extends Controller
{
    // =====================================================================
    //  NEWLY HIRED
    // =====================================================================

    /**
     * List all newly hired pro-rated payroll batches.
     *
     * Supports optional filtering by year and status via query string.
     * Accessible to all payroll-related roles for visibility.
     */
    public function newHireIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = SpecialPayrollBatch::with('employee')
            ->where('type', 'newly_hired')
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches     = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::special-payroll.newly-hired-index', compact('batches', 'currentYear'));
    }

    /**
     * Show the form for creating a new pro-rated payroll entry.
     *
     * Only active employees with less than 6 months tenure are listed —
     * inactive or separated employees are not eligible for newly hired
     * payroll processing.
     */
    public function newHireCreate()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name',
                   'position_title', 'basic_salary', 'pera']);

        // PERA excluded — it's already a first-class field elsewhere in this
        // form/service; offering it again as a generic allowance checkbox
        // would double-count it (see NewlyHiredPayrollService::compute()).
        $allowanceTypes = AllowanceType::where('is_active', true)
            ->where('code', '!=', 'PERA')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('payroll::special-payroll.newly-hired-create', compact('employees', 'allowanceTypes'));
    }

    /**
     * AJAX endpoint: resolve applicable allowance lines for an employee over
     * an arbitrary effectivity/cut-off window, for the live checklist on the
     * newHireCreate form. Delegates entirely to
     * AllowanceService::resolveForPeriod() — same precedence logic the
     * regular payroll module uses (standing → released assignment override),
     * no special-casing for newly hired employees.
     *
     * Also returns the resolved monthly PERA base (pera_monthly) alongside
     * the allowance checklist — not for a checklist row (PERA stays a
     * first-class field, see newHireCreate()), but so the client-side live
     * preview can use the same AllowanceService-resolved figure
     * newHireStore() actually persists, instead of the raw employee.pera
     * column embedded in the employee <option>'s data-pera attribute, which
     * goes stale the moment a standing PERA enrollment or assignment
     * override exists for the employee.
     */
    public function newHireAllowancesPreview(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $validated = $request->validate([
            'employee_id'      => ['required', 'exists:employees,id'],
            'effectivity_date' => ['required', 'date'],
            'cutoff_start'     => ['required', 'date'],
            'cutoff_end'       => ['required', 'date', 'after_or_equal:cutoff_start'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $periodStart = Carbon::parse($validated['cutoff_start']);
        $periodEnd   = Carbon::parse($validated['cutoff_end']);

        /** @var AllowanceService $service */
        $service = app(AllowanceService::class);

        $resolvedLines = $service->resolveForPeriod(
            $employee,
            $periodStart->year,
            $periodStart->month,
            $periodStart,
            $periodEnd
        );

        $peraMonthly = $this->extractPeraMonthly($resolvedLines, $employee);

        // PERA is always excluded from the checklist itself — see newHireCreate().
        $lines = array_values(array_filter($resolvedLines, fn ($l) => $l['code'] !== 'PERA'));

        return response()->json([
            'allowances'   => $lines,
            'pera_monthly' => $peraMonthly,
        ]);
    }

    /**
     * Compute and persist a newly hired pro-rated payroll batch.
     *
     * Delegates the pro-ration logic to NewlyHiredPayrollService, which
     * calculates earned pay based on the employee's effectivity date within
     * the cutoff window. The computed result is also stashed in the session
     * so the show page can render it without re-computing on redirect.
     */
    public function newHireStore(StoreSpecialPayrollRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);

        // ── Optional allowances (Goal 1) ──────────────────────────────────
        // 'allowances' is an array of checked allowance_type_id values.
        // 'allowance_override' / 'allowance_override_reason' are keyed by
        // allowance_type_id, for the rare case a preparer needs to correct
        // the pro-rated figure (e.g. RATA permanency-gating that the
        // resolver can't know about) — mirrors PayrollEntryAllowance's
        // is_overridden/override_reason pattern.
        $allowanceValidated = $request->validate([
            'allowances'                          => ['nullable', 'array'],
            'allowances.*'                         => ['integer', 'exists:allowance_types,id'],
            'allowance_override'                  => ['nullable', 'array'],
            'allowance_override.*'                => ['nullable', 'numeric', 'min:0'],
            'allowance_override_reason'           => ['nullable', 'array'],
            'allowance_override_reason.*'         => ['nullable', 'string', 'max:500'],
            'apply_gsis'                           => ['nullable', 'boolean'],
        ]);

        $selectedTypeIds = array_map('intval', $allowanceValidated['allowances'] ?? []);

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        /** @var AllowanceService $allowanceService */
        $allowanceService = app(AllowanceService::class);

        $periodStart = Carbon::parse($request->cutoff_start);
        $periodEnd   = Carbon::parse($request->cutoff_end);

        // Resolved unconditionally (not just when other allowances are
        // selected) so PERA always goes through the same standing-enrollment
        // → legacy-column-fallback → released-assignment-override precedence
        // Regular Payroll uses, instead of reading employee->pera directly.
        // This resolved figure is persisted below (pera_resolved_amount) and
        // frozen from here on — newHireShow()/newHirePayslip() read the
        // stored value rather than re-resolving live.
        $resolvedLines = $allowanceService->resolveForPeriod(
            $employee, $periodStart->year, $periodStart->month, $periodStart, $periodEnd
        );

        $peraMonthly = $this->extractPeraMonthly($resolvedLines, $employee);

        $proratedLines = [];
        if (! empty($selectedTypeIds)) {
            $workingDays   = $service->workingDays($request->effectivity_date, $request->cutoff_end);
            $proratedLines = $allowanceService->proRateLines($resolvedLines, $selectedTypeIds, $workingDays);

            // Apply manual overrides, per line, if the preparer supplied one.
            // A reason is mandatory for any override — same rule as every
            // other manual-override path in this module.
            foreach ($proratedLines as $i => $line) {
                $typeId = $line['allowance_type_id'];
                $override = $allowanceValidated['allowance_override'][$typeId] ?? null;

                if ($override !== null && $override !== '') {
                    $reason = $allowanceValidated['allowance_override_reason'][$typeId] ?? null;
                    if (! $reason) {
                        return back()->withErrors([
                            "allowance_override_reason.{$typeId}" =>
                                "A reason is required to override the {$line['name']} amount.",
                        ])->withInput();
                    }

                    $proratedLines[$i]['amount']           = round((float) $override, 2);
                    $proratedLines[$i]['is_overridden']     = true;
                    $proratedLines[$i]['override_reason']   = $reason;
                } else {
                    $proratedLines[$i]['is_overridden']   = false;
                    $proratedLines[$i]['override_reason'] = null;
                }
            }
        }

        // ── Optional GSIS deduction (Goal 3, added on request) ────────────
        // GSIS PS is only legally deducted for GSIS-covered appointees
        // (permanent/regular); COS/Job Order hires aren't covered at all.
        // Default is OFF (opt-in) per your instruction — full gross with no
        // deductions unless explicitly turned on. The resolved rate is
        // persisted on the batch (gsis_rate_applied) so re-renders of this
        // record (newHireShow/newHirePayslip) always reflect what was
        // actually applied at creation, not a re-derived default.
        $applyGsis = $request->boolean('apply_gsis', false);

        $gsisRateUsed = 0.0;
        if ($applyGsis) {
            $gsisRateUsed = isset($request->deduction_gsis_percent) && $request->deduction_gsis_percent !== null
                ? (float) $request->deduction_gsis_percent / 100
                : NewlyHiredPayrollService::GSIS_EMPLOYEE_RATE;
        }

        // ── Optional PERA override ─────────────────────────────────────────
        // Payroll officer may type the final PERA Earned figure directly
        // (e.g. when the auto pro-rated amount doesn't match the standard
        // ₱2,000 PERA cap) instead of relying on the (pera / 22 × working
        // days) computation. Validated separately from $allowanceValidated
        // since it's not part of the allowances array.
        $peraValidated = $request->validate([
            'pera_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $peraOverride = $peraValidated['pera_amount'] !== null && $peraValidated['pera_amount'] !== ''
            ? (float) $peraValidated['pera_amount']
            : null;

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $request->effectivity_date,
            cutoff_start:      $request->cutoff_start,
            cutoff_end:        $request->cutoff_end,
            lwop_days:         (int) ($request->lwop_days ?? 0),
            tardiness_minutes: 0,
            gsisRate:          $gsisRateUsed,
            allowanceLines:    $proratedLines,
            peraOverride:      $peraOverride,
            peraMonthly:       $peraMonthly
        );

        $cutoffStart = Carbon::parse($request->cutoff_start);
        $effectivity = Carbon::parse($request->effectivity_date);
        $payrollType = $request->payroll_type;

        $typeLabel = match ($payrollType) {
            'transferee' => 'Transferee',
            'others' => 'Others',
            default => 'Newly Hired',
        };
        $title = 'Pro-Rated Payroll — ' . $typeLabel . ' — '
            . $employee->last_name . ', ' . $employee->first_name
            . ' (' . $effectivity->format('M d, Y') . ')';

        $batch = DB::transaction(function () use ($request, $employee, $cutoffStart, $payrollType, $title, $result, $proratedLines, $gsisRateUsed, $peraOverride, $peraMonthly) {
            $batch = SpecialPayrollBatch::create([
                'type'                 => $payrollType,
                'title'                => $title,
                'year'                 => $cutoffStart->year,
                'month'                => $cutoffStart->month,
                'effectivity_date'     => $request->effectivity_date,
                'period_start'         => $request->cutoff_start,
                'period_end'           => $request->cutoff_end,
                'employee_id'          => $employee->id,
                'pro_rated_days'       => $result['working_days'],
                'gross_amount'         => $result['net_earned'],
                'deductions_amount'    => $result['total_deductions'],
                'gsis_rate_applied'    => $gsisRateUsed,
                'pera_override'        => $peraOverride,
                'pera_resolved_amount' => $peraMonthly,
                'net_amount'           => $result['net_amount'],
                'status'               => 'draft',
                'remarks'              => $request->remarks,
            ]);

            foreach ($proratedLines as $line) {
                SpecialPayrollBatchAllowance::create([
                    'special_payroll_batch_id' => $batch->id,
                    'allowance_type_id'        => $line['allowance_type_id'],
                    'code'                     => $line['code'],
                    'name'                     => $line['name'],
                    'full_amount'              => $line['full_amount'],
                    'amount'                   => $line['amount'],
                    'is_overridden'            => $line['is_overridden'] ?? false,
                    'override_reason'          => $line['override_reason'] ?? null,
                ]);
            }

            return $batch;
        });

        // Stash result in session to avoid re-computing on the redirect
        session(['newly_hired_result_' . $batch->id => $result]);

        $action = 'Created ' . $typeLabel . ' Pro-Rated Payroll: ' . $employee->last_name . ', ' . $employee->first_name;

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'old_value'  => null,
            'new_value'  => 'draft',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('special-payroll.newly-hired.show', $batch->id)
            ->with('success', "Pro-rated payroll created for {$employee->last_name}, {$employee->first_name}.");
    }

    /**
     * Display a single newly hired payroll batch with its computed breakdown.
     *
     * The service is re-invoked here using the batch's stored inputs so the
     * view always reflects the latest computation logic, even if the record
     * was created before a service update.
     */
    public function newHireShow(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch    = SpecialPayrollBatch::with('employee', 'approver', 'allowances')
            ->where('type', 'newly_hired')
            ->findOrFail($id);

        $employee = $batch->employee;

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        // Re-hydrate the persisted allowance lines (not a fresh resolve) so
        // the displayed math matches what was actually applied at creation
        // time, even if standing allowances/assignments change afterward.
        $allowanceLines = $batch->allowances->map(fn ($a) => [
            'allowance_type_id' => $a->allowance_type_id,
            'code'              => $a->code,
            'name'              => $a->name,
            'full_amount'       => (float) $a->full_amount,
            'amount'            => (float) $a->amount,
            'is_overridden'     => (bool) $a->is_overridden,
            'override_reason'   => $a->override_reason,
        ])->all();

        // Frozen at creation (pera_resolved_amount) — same reasoning as the
        // persisted allowance lines above: the math shown here should match
        // what was actually applied when the batch was created, not silently
        // drift if the employee's standing PERA enrollment changes later.
        // Only batches created before this column existed fall back to a
        // live resolve.
        $peraMonthly = $batch->pera_resolved_amount !== null
            ? (float) $batch->pera_resolved_amount
            : $this->resolvePeraMonthly($employee, $batch->period_start, $batch->period_end);

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $batch->effectivity_date->toDateString(),
            cutoff_start:      $batch->period_start->toDateString(),
            cutoff_end:        $batch->period_end->toDateString(),
            lwop_days:         0,
            tardiness_minutes: 0,
            // Fallback to the pre-toggle default (9%) only for batches
            // created before gsis_rate_applied existed (null on old rows).
            // Batches created after this change always have an explicit
            // value (including 0.0 for "off"), so ?? never masks a
            // deliberate opt-out for anything created going forward.
            gsisRate:          $batch->gsis_rate_applied ?? NewlyHiredPayrollService::GSIS_EMPLOYEE_RATE,
            allowanceLines:    $allowanceLines,
            peraOverride:      $batch->pera_override !== null ? (float) $batch->pera_override : null,
            peraMonthly:       $peraMonthly
        );

        return view('payroll::special-payroll.newly-hired-show', compact('batch', 'employee', 'result'));
    }

    /**
     * Advance a newly hired payroll batch through its approval workflow.
     *
     * Two-step flow:
     *   draft     → approved   (accountant only)
     *   approved  → released   (ard or chief_admin_officer only)
     *
     * Any other status is a terminal state and cannot be advanced further.
     */
    public function newHireApprove(Request $request, int $id)
    {
        $batch = SpecialPayrollBatch::where('type', 'newly_hired')->findOrFail($id);

        $old = $batch->status;

        if ($batch->status === 'draft') {
            $this->authorizeRole(['accountant']);
            $new    = 'approved';
            $action = 'Approved Newly Hired Payroll';
        } elseif ($batch->status === 'approved') {
            $this->authorizeRole(['ard', 'chief_admin_officer']);
            $new    = 'released';
            $action = 'Released Newly Hired Payroll';
        } else {
            return back()->with('error', 'This payroll record cannot be advanced further.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $batch->update([
            'status'      => $new,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks ?? $batch->remarks,
        ]);

        PayrollAuditLog::create([
            'payroll_batch_id' => null,
            'user_id'          => Auth::id(),
            'action'           => $action . ': ' . $batch->title,
            'old_value'        => $old,
            'new_value'        => $new,
            'ip_address'       => $request->ip(),
        ]);

        $label = $new === 'approved' ? 'approved' : 'approved and released';

        return redirect()->route('special-payroll.newly-hired.show', $batch->id)
            ->with('success', "Payroll record {$label} successfully.");
    }

    /**
     * Delete a newly hired payroll batch.
     *
     * Hard deletion is only permitted while the record is still in draft.
     * Approved or released records are immutable to protect the audit trail.
     */
    public function newHireDestroy(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $batch = SpecialPayrollBatch::where('type', 'newly_hired')
            ->where('status', 'draft')
            ->findOrFail($id);

        $batch->delete();

        return redirect()->route('special-payroll.newly-hired.index')
            ->with('success', 'Payroll record deleted.');
    }

    /**
     * Generate and download a payslip PDF for a released newly-hired /
     * transferee pro-rated payroll batch.
     *
     * Goal 2(A) — the "cheap path": a dedicated, DomPDF-safe single-slip
     * template (payroll::special-payroll.newly-hired-payslip), not a wrap
     * of the screen-only register view (that view's CSS/JS — stepper,
     * SweetAlert, app-layout chrome — isn't DomPDF-renderable as-is).
     * Reuses the same computation path as newHireShow() so the numbers on
     * the slip always match what's shown on the batch page.
     *
     * Gated on status === 'released' only — there is no 'locked' state on
     * SpecialPayrollBatch, and nothing in this controller currently
     * supports amending a released record, so 'released' alone is a
     * sufficient and stable gate.
     */
    public function newHirePayslip(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee.division', 'allowances')
            ->where('type', 'newly_hired')
            ->findOrFail($id);

        if ($batch->status !== 'released') {
            return back()->with('error', 'Payslips are only available once the batch is released.');
        }

        $employee = $batch->employee;

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        $allowanceLines = $batch->allowances->map(fn ($a) => [
            'allowance_type_id' => $a->allowance_type_id,
            'code'              => $a->code,
            'name'              => $a->name,
            'full_amount'       => (float) $a->full_amount,
            'amount'            => (float) $a->amount,
        ])->all();

        // Same frozen-at-creation figure newHireShow() uses — a released
        // payslip must keep reflecting what was actually released, not a
        // value that can drift if the employee's standing PERA enrollment
        // is edited afterward.
        $peraMonthly = $batch->pera_resolved_amount !== null
            ? (float) $batch->pera_resolved_amount
            : $this->resolvePeraMonthly($employee, $batch->period_start, $batch->period_end);

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $batch->effectivity_date->toDateString(),
            cutoff_start:      $batch->period_start->toDateString(),
            cutoff_end:        $batch->period_end->toDateString(),
            lwop_days:         0,
            tardiness_minutes: 0,
            gsisRate:          $batch->gsis_rate_applied ?? NewlyHiredPayrollService::GSIS_EMPLOYEE_RATE,
            allowanceLines:    $allowanceLines,
            peraOverride:      $batch->pera_override !== null ? (float) $batch->pera_override : null,
            peraMonthly:       $peraMonthly
        );

        $typeLabel = match ($batch->type) {
            'transferee' => 'Transferee',
            'others'     => 'Others',
            default      => 'Newly Hired',
        };

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.newly-hired-payslip', compact(
            'batch', 'employee', 'result', 'typeLabel', 'signatory'
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'payslip_special_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate the "GENERAL PAYROLL" register/certification document for a
     * newly hired / transferee pro-rated payroll batch.
     *
     * This is NOT the per-employee payslip (newHirePayslip()) — it's the
     * printable register page that used to be produced by hitting
     * window.print() on newly-hired-show.blade.php, which broke because
     * that view is an interactive, app-layout screen (scrollbars, nav
     * chrome, CSS the browser's print engine can't lay out onto a page)
     * that DomPDF cannot render as-is. Same "dedicated DomPDF template"
     * approach as the payslip methods — its own blade, no app layout,
     * Pdf::loadView(...) — but rendered landscape (not portrait, like the
     * payslip) since the register table is wide (14 columns for newly
     * hired). Reuses the same computation path and the same
     * $allowanceBreakdown/$allowancesTotalForDisplay construction as
     * newHireShow()'s view-local @php block, moved into the controller
     * since this template has no app layout to inherit shared helpers
     * from.
     *
     * Not gated on status === 'released' — unlike the payslip, this
     * register is the print-friendly version of the batch page itself,
     * which is viewable (and currently printed) at every stage (draft,
     * approved, released); certification blocks render blank signature
     * lines for stages not yet reached, same as the on-screen version.
     */
    public function newHireGeneralPayroll(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee.division', 'approver', 'allowances')
            ->where('type', 'newly_hired')
            ->findOrFail($id);

        $employee = $batch->employee;

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        $allowanceLines = $batch->allowances->map(fn ($a) => [
            'allowance_type_id' => $a->allowance_type_id,
            'code'              => $a->code,
            'name'              => $a->name,
            'full_amount'       => (float) $a->full_amount,
            'amount'            => (float) $a->amount,
        ])->all();

        $peraMonthly = $batch->pera_resolved_amount !== null
            ? (float) $batch->pera_resolved_amount
            : $this->resolvePeraMonthly($employee, $batch->period_start, $batch->period_end);

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $batch->effectivity_date->toDateString(),
            cutoff_start:      $batch->period_start->toDateString(),
            cutoff_end:        $batch->period_end->toDateString(),
            lwop_days:         0,
            tardiness_minutes: 0,
            gsisRate:          $batch->gsis_rate_applied ?? NewlyHiredPayrollService::GSIS_EMPLOYEE_RATE,
            allowanceLines:    $allowanceLines,
            peraOverride:      $batch->pera_override !== null ? (float) $batch->pera_override : null,
            peraMonthly:       $peraMonthly
        );

        $typeLabel = match ($batch->type) {
            'transferee' => 'Transferee',
            'others'     => 'Others',
            default      => 'Newly Hired',
        };

        $statusLabel = match ($batch->status) {
            'draft'    => 'Draft',
            'approved' => 'Approved',
            'released' => 'Released',
            default    => ucfirst($batch->status),
        };

        $periodLabel    = $batch->period_start->format('M d') . '–' . $batch->period_end->format('d, Y');
        $effectivityFmt = $batch->effectivity_date->format('M d, Y');

        // Same construction newly-hired-show.blade.php does in its @php
        // block — PERA folded in as just another allowance line, since
        // it's an AllowanceType row like any other.
        $allowanceBreakdown = collect([
            ['name' => 'PERA', 'code' => 'PERA', 'amount' => (float) $result['pera_earned']],
        ])->concat(
            collect($result['allowance_lines'] ?? [])->map(fn ($l) => [
                'name'   => $l['name'],
                'code'   => $l['code'],
                'amount' => (float) $l['amount'],
            ])
        )->values();

        $allowancesTotalForDisplay = round($allowanceBreakdown->sum('amount'), 2);

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.newly-hired-general-payroll', compact(
            'batch', 'employee', 'result', 'typeLabel', 'statusLabel', 'periodLabel',
            'effectivityFmt', 'allowanceBreakdown', 'allowancesTotalForDisplay', 'signatory'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'general_payroll_newly_hired_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->stream($filename);
    }

    // =====================================================================
    //  SALARY DIFFERENTIAL
    // =====================================================================

    /**
     * List all salary differential payroll batches.
     *
     * Supports optional filtering by year and status via query string.
     */
    public function differentialIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = SpecialPayrollBatch::with('employee')
            ->where('type', 'salary_differential')
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches     = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::special-payroll.differential-index', compact('batches', 'currentYear'));
    }

    /**
     * Show the form for creating a new salary differential record.
     *
     * Only active employees are eligible for salary differential processing.
     */
    public function differentialCreate()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name',
                   'position_title', 'basic_salary']);

        return view('payroll::special-payroll.differential-create', compact('employees'));
    }

    /**
     * Compute and persist a salary differential payroll batch.
     *
     * Delegates the month-by-month differential calculation to
     * SalaryDifferentialService. The new salary must exceed the old salary —
     * downward adjustments are not handled by this flow.
     */
    public function differentialStore(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $request->validate([
            'employee_id'          => ['required', 'integer', 'exists:employees,id'],
            'effectivity_date_from'=> ['required', 'date'],
            'effectivity_date_to'  => ['required', 'date', 'after_or_equal:effectivity_date_from'],
            'old_salary'           => ['required', 'numeric', 'min:0'],
            'new_salary'           => ['required', 'numeric', 'gt:old_salary'],
            'old_step'             => ['nullable', 'integer', 'min:1', 'max:8'],
            'new_step'             => ['nullable', 'integer', 'min:1', 'max:8'],
            'old_salary_grade'     => ['nullable', 'integer', 'min:1', 'max:33'],
            'new_salary_grade'     => ['nullable', 'integer', 'min:1', 'max:33'],
            'old_position'         => ['nullable', 'string', 'max:255'],
            'new_position'         => ['nullable', 'string', 'max:255'],
            'remarks'              => ['nullable', 'string', 'max:1000'],
            'deduction_gsis_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_philhealth_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_pagibig_amount'   => ['nullable', 'numeric', 'min:0'],
            'deduction_wht_percent'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            // ── PERA adjustment (Goal 6) ────────────────────────────────
            // Optional flat back-pay figure the payroll officer types in
            // herself — not auto-fetched from AllowanceService, since a
            // PERA change on a differential/step-increment/adjustment
            // batch is an exception case, not a recurring computation.
            // Not hard-capped at ₱2,000 — the standard PERA ceiling is a
            // guideline shown in the UI, not enforced server-side, since
            // the preparer sometimes needs to exceed it.
            'pera_adjustment'       => ['nullable', 'numeric', 'min:0'],
        ], [
            'new_salary.gt' => 'New salary must be greater than the old salary.',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $peraAdjustment = $request->pera_adjustment !== null && $request->pera_adjustment !== ''
            ? (float) $request->pera_adjustment
            : null;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $request->effectivity_date_from,
            effectivity_date_to:   $request->effectivity_date_to,
            old_salary:            (float) $request->old_salary,
            new_salary:            (float) $request->new_salary,
            deductionRates: [
                'gsis_percent' => $request->deduction_gsis_percent ?? null,
                'philhealth_percent' => $request->deduction_philhealth_percent ?? null,
                'pagibig_amount' => $request->deduction_pagibig_amount ?? null,
                'wht_percent' => $request->deduction_wht_percent ?? null,
            ],
            peraAdjustment: $peraAdjustment,
        );

        $from  = Carbon::parse($request->effectivity_date_from);
        $to    = Carbon::parse($request->effectivity_date_to);
        $title = 'Salary Differential — '
            . $employee->last_name . ', ' . $employee->first_name
            . ' (' . $from->format('M d, Y') . ' – ' . $to->format('M d, Y') . ')';

        $batch = SpecialPayrollBatch::create([
            'type'                => 'salary_differential',
            'title'               => $title,
            'year'                => $from->year,
            'month'               => $from->month,
            'effectivity_date'    => $request->effectivity_date_from,
            'period_start'        => $request->effectivity_date_from,
            'period_end'          => $request->effectivity_date_to,
            'employee_id'         => $employee->id,
            'old_basic_salary'    => $request->old_salary,
            'new_basic_salary'    => $request->new_salary,
            'differential_amount' => $result['differential'],
            'old_step'            => $request->old_step ?? null,
            'new_step'            => $request->new_step ?? null,
            'old_salary_grade'    => $request->old_salary_grade ?? null,
            'new_salary_grade'    => $request->new_salary_grade ?? null,
            'old_position'        => $request->old_position ?? null,
            'new_position'        => $request->new_position ?? null,
            'gross_amount'        => $result['gross_amount'],
            'deductions_amount'   => $result['total_deductions'],
            'pera_override'       => $peraAdjustment,
            'net_amount'          => $result['net_amount'],
            'status'              => 'draft',
            'remarks'             => $request->remarks,
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Created Salary Differential: ' . $employee->last_name . ', ' . $employee->first_name,
            'old_value'  => null,
            'new_value'  => 'draft',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('special-payroll.differential.show', $batch->id)
            ->with('success', "Salary differential created for {$employee->last_name}, {$employee->first_name}.");
    }

    /**
     * Display a single salary differential batch with its per-month breakdown.
     *
     * The service is re-invoked from the batch's stored inputs so the view
     * always reflects the current computation logic.
     */
    public function differentialShow(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'salary_differential')
            ->findOrFail($id);

        $employee = $batch->employee;

        // Re-compute from stored inputs to get full per-month breakdown
        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        return view('payroll::special-payroll.differential-show', compact('batch', 'employee', 'result'));
    }

    /**
     * Advance a salary differential batch through its approval workflow.
     *
     * Two-step flow:
     *   draft     → approved   (accountant only)
     *   approved  → released   (ard or chief_admin_officer only)
     */
    public function differentialApprove(Request $request, int $id)
    {
        $batch = SpecialPayrollBatch::where('type', 'salary_differential')->findOrFail($id);

        $old = $batch->status;

        if ($batch->status === 'draft') {
            $this->authorizeRole(['accountant']);
            $new    = 'approved';
            $action = 'Approved Salary Differential';
        } elseif ($batch->status === 'approved') {
            $this->authorizeRole(['ard', 'chief_admin_officer']);
            $new    = 'released';
            $action = 'Released Salary Differential';
        } else {
            return back()->with('error', 'This payroll record cannot be advanced further.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $batch->update([
            'status'      => $new,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks ?? $batch->remarks,
        ]);

        PayrollAuditLog::create([
            'payroll_batch_id' => null,
            'user_id'          => Auth::id(),
            'action'           => $action . ': ' . $batch->title,
            'old_value'        => $old,
            'new_value'        => $new,
            'ip_address'       => $request->ip(),
        ]);

        $label = $new === 'approved' ? 'approved' : 'approved and released';

        return redirect()->route('special-payroll.differential.show', $batch->id)
            ->with('success', "Salary differential record {$label} successfully.");
    }

    /**
     * Delete a salary differential batch.
     *
     * Hard deletion is only permitted while the record is still in draft.
     * Approved or released records are immutable to protect the audit trail.
     */
    public function differentialDestroy(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $batch = SpecialPayrollBatch::where('type', 'salary_differential')
            ->where('status', 'draft')
            ->findOrFail($id);

        $batch->delete();

        return redirect()->route('special-payroll.differential.index')
            ->with('success', 'Record deleted.');
    }

    /**
     * Generate and download a payslip PDF for a released salary
     * differential batch.
     *
     * Same "dedicated DomPDF template" approach as newHirePayslip() —
     * differential-show.blade.php's stepper/print-CSS chrome isn't
     * DomPDF-renderable as-is. Re-invokes SalaryDifferentialService from
     * the batch's stored inputs (same as differentialShow()) so the slip's
     * numbers always match what's on the batch page, including the full
     * per-month breakdown.
     *
     * Gated on status === 'released' only, same as newly-hired — there is
     * no 'locked' state on SpecialPayrollBatch and nothing here supports
     * amending a released record.
     */
    public function differentialPayslip(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'salary_differential')
            ->findOrFail($id);

        if ($batch->status !== 'released') {
            return back()->with('error', 'Payslips are only available once the batch is released.');
        }

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.differential-payslip', compact(
            'batch', 'employee', 'result', 'signatory'
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'payslip_differential_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate the "GENERAL PAYROLL" register/certification document for a
     * salary differential batch.
     *
     * Same rationale and approach as newHireGeneralPayroll(): NOT the
     * per-employee payslip (differentialPayslip()), but a dedicated
     * DomPDF-safe replacement for what window.print() on
     * differential-show.blade.php used to (badly) produce. Re-invokes
     * SalaryDifferentialService from the batch's stored inputs, same as
     * differentialShow(), so the numbers always match the batch page.
     * Landscape — the per-month earned/deduction register table is wide.
     *
     * Not gated on status === 'released', same reasoning as
     * newHireGeneralPayroll() — this mirrors what's already viewable (and
     * currently printed) on the batch page at every stage.
     */
    public function differentialGeneralPayroll(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'salary_differential')
            ->findOrFail($id);

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        $statusLabel = match ($batch->status) {
            'draft'    => 'Draft',
            'approved' => 'Approved',
            'released' => 'Released',
            default    => ucfirst($batch->status),
        };

        $period = $batch->period_start->format('M d, Y') . ' – ' . $batch->period_end->format('M d, Y');

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.differential-general-payroll', compact(
            'batch', 'employee', 'result', 'period', 'statusLabel', 'signatory'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'general_payroll_differential_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->stream($filename);
    }

    // =====================================================================
    //  NOSI / NOSA
    // =====================================================================

    /**
     * List all NOSI and NOSA payroll batches.
     *
     * Supports optional filtering by year, status, and type (nosi|nosa)
     * via query string. Both types are shown together since they share the
     * same computation logic and approval workflow.
     */
    public function nosiNosaIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = SpecialPayrollBatch::with('employee')
            ->whereIn('type', ['nosi', 'nosa'])
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $batches     = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::special-payroll.nosi-nosa-index', compact('batches', 'currentYear'));
    }

    /**
     * Show the form for creating a new NOSI or NOSA record.
     *
     * The type (nosi|nosa) is selected by the user on the form itself.
     * Only active employees are eligible.
     */
    public function nosiNosaCreate()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name',
                   'position_title', 'basic_salary']);

        return view('payroll::special-payroll.nosi-nosa-create', compact('employees'));
    }

    /**
     * Compute and persist a NOSI or NOSA payroll batch.
     *
     * NOSI (Notice of Step Increment) and NOSA (Notice of Salary Adjustment)
     * both follow the same differential computation logic via
     * SalaryDifferentialService — the type field distinguishes them for
     * reporting and approval routing purposes.
     */
    public function nosiNosaStore(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $request->validate([
            'type'                 => ['required', 'in:nosi,nosa'],
            'employee_id'          => ['required', 'integer', 'exists:employees,id'],
            'effectivity_date_from'=> ['required', 'date'],
            'effectivity_date_to'  => ['required', 'date', 'after_or_equal:effectivity_date_from'],
            'old_salary'           => ['required', 'numeric', 'min:0'],
            'new_salary'           => ['required', 'numeric', 'gt:old_salary'],
            'old_step'             => ['nullable', 'integer', 'min:1', 'max:8'],
            'new_step'             => ['nullable', 'integer', 'min:1', 'max:8'],
            'old_salary_grade'     => ['nullable', 'integer', 'min:1', 'max:33'],
            'new_salary_grade'     => ['nullable', 'integer', 'min:1', 'max:33'],
            'old_position'         => ['nullable', 'string', 'max:255'],
            'new_position'         => ['nullable', 'string', 'max:255'],
            'remarks'              => ['nullable', 'string', 'max:1000'],
            'deduction_gsis_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_philhealth_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_pagibig_amount'   => ['nullable', 'numeric', 'min:0'],
            'deduction_wht_percent'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            // ── PERA adjustment (Goal 6) — same rule as Salary Differential ──
            'pera_adjustment'       => ['nullable', 'numeric', 'min:0'],
        ], [
            'new_salary.gt' => 'New salary must be greater than the old salary.',
            'type.in'       => 'Type must be either NOSI or NOSA.',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $peraAdjustment = $request->pera_adjustment !== null && $request->pera_adjustment !== ''
            ? (float) $request->pera_adjustment
            : null;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $request->effectivity_date_from,
            effectivity_date_to:   $request->effectivity_date_to,
            old_salary:            (float) $request->old_salary,
            new_salary:            (float) $request->new_salary,
            deductionRates: [
                'gsis_percent' => $request->deduction_gsis_percent ?? null,
                'philhealth_percent' => $request->deduction_philhealth_percent ?? null,
                'pagibig_amount' => $request->deduction_pagibig_amount ?? null,
                'wht_percent' => $request->deduction_wht_percent ?? null,
            ],
            peraAdjustment: $peraAdjustment,
        );

        $from      = Carbon::parse($request->effectivity_date_from);
        $to        = Carbon::parse($request->effectivity_date_to);
        $typeLabel = strtoupper($request->type);

        $title = $typeLabel . ' — '
            . $employee->last_name . ', ' . $employee->first_name
            . ' (' . $from->format('M d, Y') . ' – ' . $to->format('M d, Y') . ')';

        $batch = SpecialPayrollBatch::create([
            'type'                => $request->type,
            'title'               => $title,
            'year'                => $from->year,
            'month'               => $from->month,
            'effectivity_date'    => $request->effectivity_date_from,
            'period_start'        => $request->effectivity_date_from,
            'period_end'          => $request->effectivity_date_to,
            'employee_id'         => $employee->id,
            'old_basic_salary'    => $request->old_salary,
            'new_basic_salary'    => $request->new_salary,
            'differential_amount' => $result['differential'],
            'old_step'            => $request->old_step ?? null,
            'new_step'            => $request->new_step ?? null,
            'old_salary_grade'    => $request->old_salary_grade ?? null,
            'new_salary_grade'    => $request->new_salary_grade ?? null,
            'old_position'        => $request->old_position ?? null,
            'new_position'        => $request->new_position ?? null,
            'gross_amount'        => $result['gross_amount'],
            'deductions_amount'   => $result['total_deductions'],
            'pera_override'       => $peraAdjustment,
            'net_amount'          => $result['net_amount'],
            'status'              => 'draft',
            'remarks'             => $request->remarks,
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Created ' . $typeLabel . ': ' . $employee->last_name . ', ' . $employee->first_name,
            'old_value'  => null,
            'new_value'  => 'draft',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('special-payroll.nosi-nosa.show', $batch->id)
            ->with('success', $typeLabel . " record created for {$employee->last_name}, {$employee->first_name}.");
    }

    /**
     * Display a single NOSI or NOSA batch with its computed breakdown.
     *
     * The service is re-invoked from the batch's stored inputs so the view
     * always reflects the current computation logic.
     */
    public function nosiNosaShow(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->whereIn('type', ['nosi', 'nosa'])
            ->findOrFail($id);

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        return view('payroll::special-payroll.nosi-nosa-show', compact('batch', 'employee', 'result'));
    }

    /**
     * Advance a NOSI or NOSA batch through its approval workflow.
     *
     * Two-step flow:
     *   draft     → approved   (accountant only)
     *   approved  → released   (ard or chief_admin_officer only)
     */
    public function nosiNosaApprove(Request $request, int $id)
    {
        $batch = SpecialPayrollBatch::whereIn('type', ['nosi', 'nosa'])->findOrFail($id);

        $old       = $batch->status;
        $typeLabel = strtoupper($batch->type);

        if ($batch->status === 'draft') {
            $this->authorizeRole(['accountant']);
            $new    = 'approved';
            $action = 'Approved ' . $typeLabel;
        } elseif ($batch->status === 'approved') {
            $this->authorizeRole(['ard', 'chief_admin_officer']);
            $new    = 'released';
            $action = 'Released ' . $typeLabel;
        } else {
            return back()->with('error', 'This record cannot be advanced further.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $batch->update([
            'status'      => $new,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks ?? $batch->remarks,
        ]);

        PayrollAuditLog::create([
            'payroll_batch_id' => null,
            'user_id'          => Auth::id(),
            'action'           => $action . ': ' . $batch->title,
            'old_value'        => $old,
            'new_value'        => $new,
            'ip_address'       => $request->ip(),
        ]);

        $label = $new === 'approved' ? 'approved' : 'approved and released';

        return redirect()->route('special-payroll.nosi-nosa.show', $batch->id)
            ->with('success', $typeLabel . " record {$label} successfully.");
    }

    /**
     * Delete a NOSI or NOSA batch.
     *
     * Hard deletion is only permitted while the record is still in draft.
     * Approved or released records are immutable to protect the audit trail.
     */
    public function nosiNosaDestroy(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $batch = SpecialPayrollBatch::whereIn('type', ['nosi', 'nosa'])
            ->where('status', 'draft')
            ->findOrFail($id);

        $batch->delete();

        return redirect()->route('special-payroll.nosi-nosa.index')
            ->with('success', 'Record deleted.');
    }

    /**
     * Generate and download a payslip PDF for a released NOSI or NOSA batch.
     *
     * Same dedicated-DomPDF-template approach as newHirePayslip() and
     * differentialPayslip() — nosiNosaShow()'s stepper/print-CSS chrome
     * isn't DomPDF-renderable as-is. Re-invokes SalaryDifferentialService
     * from the batch's stored inputs (same as nosiNosaShow()), since NOSI
     * and NOSA both delegate to that service rather than computing inline.
     *
     * Type label uses the official DBM terminology (Notice of Step
     * Increment / Notice of Salary Adjustment) confirmed via Circular
     * Letter No. 2024-7 and National Budget Circular No. 597 — not the
     * "Notice of Salary Increase" wording nosi-nosa-show.blade.php had
     * been using for NOSI.
     *
     * Gated on status === 'released' only, same as the other two types.
     */
    public function nosiNosaPayslip(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->whereIn('type', ['nosi', 'nosa'])
            ->findOrFail($id);

        if ($batch->status !== 'released') {
            return back()->with('error', 'Payslips are only available once the batch is released.');
        }

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        $typeLabel = $batch->type === 'nosi'
            ? 'Notice of Step Increment (NOSI)'
            : 'Notice of Salary Adjustment (NOSA)';

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.nosi-nosa-payslip', compact(
            'batch', 'employee', 'result', 'typeLabel', 'signatory'
        ))
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'payslip_' . $batch->type . '_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate the "GENERAL PAYROLL" register/certification document for a
     * NOSI or NOSA batch.
     *
     * Same rationale and approach as newHireGeneralPayroll() and
     * differentialGeneralPayroll(): NOT the per-employee payslip
     * (nosiNosaPayslip()), but a dedicated DomPDF-safe replacement for what
     * window.print() on nosi-nosa-show.blade.php used to (badly) produce.
     * Re-invokes SalaryDifferentialService from the batch's stored inputs,
     * same as nosiNosaShow(), since NOSI and NOSA both delegate to that
     * service rather than computing inline. Landscape, same reasoning as
     * the differential register.
     *
     * Type label uses the same official DBM terminology nosiNosaPayslip()
     * uses (Notice of Step Increment / Notice of Salary Adjustment).
     *
     * Not gated on status === 'released', same reasoning as the other two
     * general-payroll methods.
     */
    public function nosiNosaGeneralPayroll(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->whereIn('type', ['nosi', 'nosa'])
            ->findOrFail($id);

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
            peraAdjustment:        $batch->pera_override !== null ? (float) $batch->pera_override : null,
        );

        $typeUpper = strtoupper($batch->type);
        $typeTitle = $batch->type === 'nosi'
            ? 'NOTICE OF STEP INCREMENT'
            : 'NOTICE OF SALARY ADJUSTMENT';

        $statusLabel = match ($batch->status) {
            'draft'    => 'Draft',
            'approved' => 'Approved',
            'released' => 'Released',
            default    => ucfirst($batch->status),
        };

        $period = $batch->period_start->format('M d, Y') . ' – ' . $batch->period_end->format('M d, Y');

        $signatory = Signatory::where('role_type', 'hrmo_designate')
            ->where('is_active', true)
            ->first();

        $pdf = Pdf::loadView('payroll::special-payroll.nosi-nosa-general-payroll', compact(
            'batch', 'employee', 'result', 'period', 'typeUpper', 'typeTitle', 'statusLabel', 'signatory'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'dpi'                  => 96,
        ]);

        $filename = 'general_payroll_' . $batch->type . '_'
            . str_replace([' ', ',', '.'], '_', $employee->full_name)
            . "_{$batch->year}_{$batch->month}.pdf";

        return $pdf->stream($filename);
    }

    // =====================================================================
    //  GENERIC SPECIAL PAYROLL
    // =====================================================================

    /**
     * List all generic special payroll batches.
     *
     * Supports optional filtering by year and status via query string.
     * Accessible to all payroll-related roles for visibility.
     */
    public function genericIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = SpecialPayrollBatch::with('employee')
            ->where('type', 'generic_special')
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches     = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::special-payroll.generic-index', compact('batches', 'currentYear'));
    }

    /**
     * Show the form for creating a new generic special payroll entry.
     *
     * Allows flexible special payments for bonuses, incentives, etc.
     */
    public function genericCreate()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name',
                   'position_title', 'basic_salary', 'pera']);

        return view('payroll::special-payroll.generic-create', compact('employees'));
    }

    /**
     * Compute and persist a generic special payroll batch.
     *
     * Generic special payroll allows flexible amounts for various purposes
     * like bonuses, incentives, or other ad-hoc payments.
     */
    public function genericStore(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'effectivity_date' => ['required', 'date'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $netAmount = $validated['gross_amount'] - ($validated['deductions_amount'] ?? 0);

        $batch = SpecialPayrollBatch::create([
            'type' => 'generic_special',
            'title' => $validated['title'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'effectivity_date' => $validated['effectivity_date'],
            'employee_id' => $employee->id,
            'gross_amount' => $validated['gross_amount'],
            'deductions_amount' => $validated['deductions_amount'] ?? 0,
            'net_amount' => $netAmount,
            'status' => 'draft',
            'remarks' => $validated['remarks'],
        ]);

        PayrollAuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created Generic Special Payroll: ' . $employee->last_name . ', ' . $employee->first_name,
            'old_value' => null,
            'new_value' => 'draft',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('special-payroll.generic.show', $batch->id)
            ->with('success', "Generic special payroll record created for {$employee->last_name}, {$employee->first_name}.");
    }

    /**
     * Display a single generic special payroll batch.
     */
    public function genericShow(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'generic_special')
            ->findOrFail($id);

        return view('payroll::special-payroll.generic-show', compact('batch'));
    }

    /**
     * Advance a generic special payroll batch through its approval workflow.
     *
     * Two-step flow:
     *   draft     → approved   (accountant only)
     *   approved  → released   (ard or chief_admin_officer only)
     */
    public function genericApprove(Request $request, int $id)
    {
        $batch = SpecialPayrollBatch::where('type', 'generic_special')->findOrFail($id);

        $old = $batch->status;

        if ($batch->status === 'draft') {
            $this->authorizeRole(['accountant']);
            $new = 'approved';
            $action = 'Approved Generic Special Payroll';
        } elseif ($batch->status === 'approved') {
            $this->authorizeRole(['ard', 'chief_admin_officer']);
            $new = 'released';
            $action = 'Released Generic Special Payroll';
        } else {
            return back()->with('error', 'This record cannot be advanced further.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $batch->update([
            'status' => $new,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks' => $request->remarks ?? $batch->remarks,
        ]);

        PayrollAuditLog::create([
            'payroll_batch_id' => null,
            'user_id' => Auth::id(),
            'action' => $action . ': ' . $batch->title,
            'old_value' => $old,
            'new_value' => $new,
            'ip_address' => $request->ip(),
        ]);

        $label = $new === 'approved' ? 'approved' : 'approved and released';

        return redirect()->route('special-payroll.generic.show', $batch->id)
            ->with('success', "Generic special payroll record {$label} successfully.");
    }

    /**
     * Delete a generic special payroll batch.
     *
     * Hard deletion is only permitted while the record is still in draft.
     * Approved or released records are immutable to protect the audit trail.
     */
    public function genericDestroy(int $id)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo']);

        $batch = SpecialPayrollBatch::where('type', 'generic_special')
            ->where('status', 'draft')
            ->findOrFail($id);

        $batch->delete();

        return redirect()->route('special-payroll.generic.index')
            ->with('success', 'Record deleted.');
    }

    // =====================================================================
    //  Private helpers
    // =====================================================================

    /**
     * Abort with 403 if the authenticated user does not hold any of the given roles.
     */
    private function authorizeRole(array $roles): void
    {
        // super_admin bypasses all role checks — view access to all modules
        if (Auth::user()->hasRole('super_admin')) {
            return;
        }

        if (!Auth::user()->hasAnyRole($roles)) {
            abort(403);
        }
    }

    /**
     * Resolve the monthly PERA base for an employee over a period via
     * AllowanceService::resolveForPeriod() (standing enrollment → legacy
     * employee.pera fallback → released assignment override — same
     * precedence Regular Payroll uses), falling back to employee->pera
     * directly if no PERA line resolves at all.
     *
     * Only used as a fallback for newly-hired batches created before
     * pera_resolved_amount existed; newHireStore() persists the resolved
     * figure so later reads don't need to call this.
     */
    private function resolvePeraMonthly(Employee $employee, Carbon $periodStart, Carbon $periodEnd): float
    {
        /** @var AllowanceService $allowanceService */
        $allowanceService = app(AllowanceService::class);

        $resolvedLines = $allowanceService->resolveForPeriod(
            $employee, $periodStart->year, $periodStart->month, $periodStart, $periodEnd
        );

        return $this->extractPeraMonthly($resolvedLines, $employee);
    }

    /**
     * @param  array<int, array{code:string, amount:float}>  $resolvedLines
     */
    private function extractPeraMonthly(array $resolvedLines, Employee $employee): float
    {
        $peraLine = collect($resolvedLines)->firstWhere('code', 'PERA');

        return $peraLine['amount'] ?? (float) $employee->pera;
    }
}

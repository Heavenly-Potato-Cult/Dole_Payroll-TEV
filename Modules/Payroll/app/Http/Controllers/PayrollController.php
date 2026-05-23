<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\ComputePayrollRequest;
use Modules\Payroll\Models\AttendanceSnapshot;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use Modules\Payroll\Models\PayrollAuditLog;
use App\SharedKernel\Models\Signatory;
use Modules\Payroll\Services\AttendanceService;
use Modules\Payroll\Services\PayrollComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    const STATUS_LABELS = [
        'draft'              => 'Draft',
        'computed'           => 'Computed',
        'pending_accountant' => 'Pending Accountant',
        'pending_rd'         => 'Pending RD/ARD',
        'released'           => 'Released',
        'locked'             => 'Locked',
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PayrollBatch::with('creator')
            ->withCount('entries')
            ->withSum('entries', 'gross_income')
            ->withSum('entries', 'total_deductions')
            ->withSum('entries', 'net_amount')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id');

        // Employees can only see released/locked batches
        if (! $user->hasPermissionTo(\App\SharedKernel\Enums\Permission::PAYROLL_ACCESS->value)) {
            $query->whereIn('status', ['released', 'locked']);
        }

        if ($request->filled('year'))   $query->where('period_year',  $request->year);
        if ($request->filled('month'))  $query->where('period_month', $request->month);
        if ($request->filled('status')) $query->where('status',       $request->status);

        $batches = $query->paginate(15)->withQueryString();

        // Fetch locked batches separately (ignoring status filter for the locked tab)
        $lockedQuery = PayrollBatch::with('creator')
            ->withCount('entries')
            ->withSum('entries', 'gross_income')
            ->withSum('entries', 'total_deductions')
            ->withSum('entries', 'net_amount')
            ->where('status', 'locked')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id');

        if ($request->filled('year'))  $lockedQuery->where('period_year',  $request->year);
        if ($request->filled('month')) $lockedQuery->where('period_month', $request->month);

        $lockedBatches = $lockedQuery->get();

        return view('payroll::payroll.index', compact('batches', 'lockedBatches'));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  My Payslip — Employee self-service
    //  Shows the logged-in employee's own entries from
    //  locked batches only.
    // ═══════════════════════════════════════════════════════════════════

    public function myPayslip(Request $request)
    {
        // resolveHrisEmployeeId() handles the HRIS session lookup.
        // The HRIS stores employee_no (e.g. "EMP001") in session('hris_employee_id'),
        // but payroll_entries.employee_id is the integer PK — the helper bridges that gap.
        $employeeId = $this->resolveHrisEmployeeId();

        if (! $employeeId) {
            // Fall back to the user's directly linked employee record
            $user = Auth::user();
            $employeeId = $user->employee?->id;
        }

        if (! $employeeId) {
            // No employee association at all — show empty state
            $entries = collect([]);
            return view('payroll::payroll.my-payslip', compact('entries'));
        }

        $entries = PayrollEntry::with(['batch'])
            ->whereHas('batch', fn ($q) => $q->where('status', 'locked'))
            ->where('employee_id', $employeeId)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('payroll::payroll.my-payslip', compact('entries'));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  View My Payslip — streams a single employee's own payslip as PDF.
    //
    //  Route: GET /payroll/{payroll}/my-payslip/{entry}
    //  Name:  payroll.payslip
    //
    //  Security:
    //    - Batch must be released or locked.
    //    - The entry must belong to the given batch.
    //    - An HRIS employee can only view their own entry.
    // ═══════════════════════════════════════════════════════════════════

    public function viewMyPayslip(Request $request, PayrollBatch $payroll, PayrollEntry $entry)
    {
        // Guard: batch must be released or locked
        if (! in_array($payroll->status, ['released', 'locked'])) {
            abort(403, 'Payslip is not yet available. The payroll batch has not been released.');
        }

        // Guard: entry must belong to this batch
        if ((int) $entry->payroll_batch_id !== (int) $payroll->id) {
            abort(404, 'Payslip entry not found in the specified batch.');
        }

        // Guard: HRIS employees may only view their own payslip.
        // resolveHrisEmployeeId() converts employee_no → integer PK so the
        // comparison is always integer vs integer.
        $hrisEmployeeId = $this->resolveHrisEmployeeId();
        if ($hrisEmployeeId && (int) $entry->employee_id !== $hrisEmployeeId) {
            abort(403, 'You are not authorized to view this payslip.');
        }

        // Load all needed relationships
        $entry->load(['employee.division', 'deductions.deductionType']);

        // Resolve the sibling batch (other cut-off of the same month/year).
        // Only include sibling if it is also released or locked.
        $siblingCutoff = $payroll->cutoff === '1st' ? '2nd' : '1st';
        $sibling = PayrollBatch::where('period_year',  $payroll->period_year)
                               ->where('period_month', $payroll->period_month)
                               ->where('cutoff',       $siblingCutoff)
                               ->whereIn('status', ['released', 'locked'])
                               ->first();

        // Fetch the sibling entry for this employee (may be null)
        $siblingEntry = $sibling
            ? $sibling->entries()
                      ->with('deductions.deductionType')
                      ->where('employee_id', $entry->employee_id)
                      ->first()
            : null;

        // Helper: build a keyed deduction map from an entry
        $dedMap = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        // Place entries in the correct 1st / 2nd columns
        if ($payroll->cutoff === '1st') {
            $entry1st = $entry;
            $entry2nd = $siblingEntry;
        } else {
            $entry1st = $siblingEntry;
            $entry2nd = $entry;
        }

        // Wrap in the same shape that payslip.blade.php expects
        $payslips = collect([[
            'employee' => $entry->employee,
            'entry1st' => $entry1st,
            'entry2nd' => $entry2nd,
            'ded1st'   => $dedMap($entry1st),
            'ded2nd'   => $dedMap($entry2nd),
        ]]);

        $months = [
            '', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;

        $signatory = Signatory::where('role_type', 'hrmo_designate')
                              ->where('is_active', true)
                              ->first();

        $pdf = Pdf::loadView('payroll::payroll.payslip', [
            'batch'       => $payroll,
            'payslips'    => $payslips,
            'rows'        => $this->payslipRows(),
            'periodLabel' => $periodLabel,
            'signatory'   => $signatory,
            'mode'        => 'consolidated',
        ])->setPaper('a4', 'portrait');

        $employeeName = $entry->employee
            ? str_replace(' ', '_', $entry->employee->full_name)
            : 'Employee';
        $filename = 'Payslip_' . $employeeName . '_' . str_replace(' ', '_', $periodLabel) . '.pdf';

        return $pdf->stream($filename);
    }

    public function create()
    {
        $this->authorize('create', PayrollBatch::class);

        $currentYear  = now()->year;
        $currentMonth = now()->month;
        $years        = range($currentYear - 2, $currentYear + 1);

        return view('payroll::payroll.create', compact('currentYear', 'currentMonth', 'years'));
    }

    public function store(ComputePayrollRequest $request)
    {
        $year   = (int) $request->period_year;
        $month  = (int) $request->period_month;
        $cutoff = $request->cutoff;

        $exists = PayrollBatch::where([
            'period_year'  => $year,
            'period_month' => $month,
            'cutoff'       => $cutoff,
        ])->exists();

        if ($exists) {
            return back()->withInput()
                ->with('error', "A {$cutoff} cut-off payroll batch for {$request->periodLabel()} already exists.");
        }

        $periodStart = $cutoff === '1st'
            ? \Carbon\Carbon::create($year, $month, 1)
            : \Carbon\Carbon::create($year, $month, 16);

        $periodEnd = $cutoff === '1st'
            ? \Carbon\Carbon::create($year, $month, 15)
            : \Carbon\Carbon::create($year, $month)->endOfMonth();

        $batch = PayrollBatch::create([
            'period_year'  => $year,
            'period_month' => $month,
            'cutoff'       => $cutoff,
            'period_start' => $periodStart->toDateString(),
            'period_end'   => $periodEnd->toDateString(),
            'status'       => 'draft',
            'created_by'   => Auth::id(),
        ]);

        $this->log($batch, 'created', null, 'draft');

        return redirect()->route('payroll.show', $batch)
            ->with('success', "Payroll batch created for {$request->periodLabel()}. Pull attendance first, then click Compute.");
    }

    public function show(PayrollBatch $payroll)
    {
        $payroll->load(['entries.employee', 'entries.deductions.deductionType', 'creator', 'auditLogs.user']);

        $entries       = $payroll->entries->sortBy(fn ($e) => optional($e->employee)->last_name ?? '');
        $totalGross    = $payroll->entries->sum('gross_income');
        $totalDeds     = $payroll->entries->sum('total_deductions');
        $totalNet      = $payroll->entries->sum('net_amount');
        $employeeCount = $payroll->entries->count();
        $auditLogs     = $payroll->auditLogs->sortByDesc('performed_at');

        $attendanceService = app(AttendanceService::class);
        $snapshotCount     = $attendanceService->snapshotCount($payroll);
        $correctedCount    = $attendanceService->correctedCount($payroll);
        $activeCount       = \App\SharedKernel\Models\Employee::where('status', 'active')->count();

        $snapshots = in_array($payroll->status, ['draft', 'computed'])
            ? AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
                ->with('employee:id,last_name,first_name,employee_no')
                ->orderBy('employee_id')
                ->get()
            : collect();

        return view('payroll::payroll.show', compact(
            'payroll', 'entries',
            'totalGross', 'totalDeds', 'totalNet', 'employeeCount',
            'auditLogs',
            'snapshotCount', 'correctedCount', 'activeCount', 'snapshots'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Pull Attendance
    // ═══════════════════════════════════════════════════════════════════

    public function pullAttendance(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('compute', $payroll);

        if (in_array($payroll->status, ['released', 'locked'])) {
            return back()->with('error', 'Cannot re-pull attendance for a released or locked batch.');
        }

        $result = app(AttendanceService::class)->pullForBatch($payroll);

        $message = "Attendance pulled: {$result['pulled']} employee(s) recorded.";

        if (! empty($result['errors'])) {
            return redirect()->route('payroll.show', $payroll)
                ->with('warning', "{$message} Some employees failed: " . implode('; ', $result['errors']));
        }

        $this->log($payroll, 'attendance_pulled', null, "pulled:{$result['pulled']}");

        return redirect()->route('payroll.show', $payroll)
            ->with('success', "{$message} Review the attendance records below, then click Compute.");
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Compute
    // ═══════════════════════════════════════════════════════════════════

    public function compute(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('compute', $payroll);

        $attendanceService = app(AttendanceService::class);

        if ($attendanceService->snapshotCount($payroll) === 0) {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'Attendance has not been pulled yet. Click "Pull Attendance" first.');
        }

        $attendanceMap = $attendanceService->getAttendanceForBatch($payroll);
        $result = app(PayrollComputationService::class)->computeBatch($payroll, $attendanceMap);

        if ($payroll->status === 'draft') {
            $payroll->update(['status' => 'computed']);
            $this->log($payroll, 'computed', 'draft', 'computed');
        }

        $message = "Computation complete: {$result['computed']} employee(s) processed.";

        if (! empty($result['errors'])) {
            return redirect()->route('payroll.show', $payroll)
                ->with('warning', "{$message} Errors: " . implode('; ', $result['errors']));
        }

        return redirect()->route('payroll.show', $payroll)->with('success', $message);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Pull Attendance & Compute (Combined)
    // ═══════════════════════════════════════════════════════════════════

    public function pullAndCompute(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('compute', $payroll);

        if (in_array($payroll->status, ['released', 'locked'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot process a released or locked batch.'
            ], 422);
        }

        try {
            // Step 1: Pull Attendance
            $attendanceResult = app(AttendanceService::class)->pullForBatch($payroll);
            
            if (! empty($attendanceResult['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance pull failed: ' . implode('; ', $attendanceResult['errors'])
                ], 422);
            }

            $this->log($payroll, 'attendance_pulled', null, "pulled:{$attendanceResult['pulled']}");

            // Step 2: Compute Payroll
            $attendanceMap = app(AttendanceService::class)->getAttendanceForBatch($payroll);
            $computeResult = app(PayrollComputationService::class)->computeBatch($payroll, $attendanceMap);

            if ($payroll->status === 'draft') {
                $payroll->update(['status' => 'computed']);
                $this->log($payroll, 'computed', 'draft', 'computed');
            }

            if (! empty($computeResult['errors'])) {
                return response()->json([
                    'success' => true,
                    'message' => "Process completed with warnings. Computed: {$computeResult['computed']} employees. Warnings: " . implode('; ', $computeResult['errors'])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Process completed successfully! Pulled: {$attendanceResult['pulled']} employees, Computed: {$computeResult['computed']} payroll entries."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during processing: ' . $e->getMessage()
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Approval pipeline
    // ═══════════════════════════════════════════════════════════════════

    public function submit(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('submit', $payroll);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $payroll->status;
        $payroll->update([
            'status'      => 'pending_accountant',
            'prepared_at' => now(),
            'remarks'     => $request->input('remarks'),
        ]);
        $this->log($payroll, 'Submitted for Accountant Review', $old, 'pending_accountant');

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll batch submitted to the Accountant for review.');
    }

    public function certify(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('certify', $payroll);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $payroll->status;
        $payroll->update([
            'status'      => 'pending_rd',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'remarks'     => $request->input('remarks') ?? $payroll->remarks,
        ]);
        $this->log($payroll, 'Funds Certified — Forwarded to RD/ARD', $old, 'pending_rd');

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll certified. Forwarded to RD/ARD for approval.');
    }

    public function approve(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('approve', $payroll);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $payroll->status;
        $payroll->update([
            'status'      => 'released',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'released_at' => now(),
            'remarks'     => $request->input('remarks') ?? $payroll->remarks,
        ]);
        $this->log($payroll, 'Approved & Released by RD/ARD', $old, 'released');

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll approved and released.');
    }

    public function lock(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('lock', $payroll);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $payroll->status;
        $payroll->update([
            'status'      => 'locked',
            'released_by' => Auth::id(),
            'remarks'     => $request->input('remarks') ?? $payroll->remarks,
        ]);
        $this->log($payroll, 'Locked after Disbursement', $old, 'locked');

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll batch locked. Disbursement complete.');
    }

    public function destroy(PayrollBatch $payroll)
    {
        $this->authorize('delete', $payroll);

        DB::transaction(function () use ($payroll) {
            foreach ($payroll->entries as $entry) {
                $entry->deductions()->delete();
            }
            $payroll->entries()->delete();
            $payroll->auditLogs()->delete();
            AttendanceSnapshot::where('payroll_batch_id', $payroll->id)->delete();
            $payroll->forceDelete();
        });

        return redirect()->route('payroll.index')
            ->with('success', 'Draft payroll batch deleted.');
    }

    public function verify(PayrollBatch $payroll)
    {
        $this->authorize('view', $payroll);
        $payroll->load(['entries.employee', 'entries.deductions']);

        $siblingCutoff = $payroll->cutoff === '1st' ? '2nd' : '1st';
        $siblingBatch  = PayrollBatch::with(['entries.employee', 'entries.deductions'])
            ->where('period_year',  $payroll->period_year)
            ->where('period_month', $payroll->period_month)
            ->where('cutoff',       $siblingCutoff)
            ->first();

        $siblingEntries = $siblingBatch
            ? $siblingBatch->entries->keyBy('employee_id')
            : collect();

        $verifyRows = $payroll->entries
            ->sortBy(fn ($e) => optional($e->employee)->last_name . optional($e->employee)->first_name)
            ->map(function ($entry) use ($siblingEntries) {
                $sibling    = $siblingEntries->get($entry->employee_id);
                $netCurrent = (float) $entry->net_amount;
                $netSibling = $sibling ? (float) $sibling->net_amount : null;

                $hasLbpLoan = $entry->deductions->contains(
                    fn ($d) => stripos($d->code ?? '', 'lbp') !== false
                            || stripos($d->name ?? '', 'lbp') !== false
                );

                return (object) [
                    'employee'        => $entry->employee,
                    'entry_current'   => $entry,
                    'entry_sibling'   => $sibling,
                    'net_current'     => $netCurrent,
                    'net_sibling'     => $netSibling,
                    'total_net'       => $netCurrent + ($netSibling ?? 0),
                    'has_lbp_loan'    => $hasLbpLoan,
                    'below_threshold' => $netCurrent < 5000 || ($netSibling !== null && $netSibling < 5000),
                ];
            })
            ->values();

        [$totalNet1st, $totalNet2nd] = $payroll->cutoff === '1st'
            ? [$payroll->entries->sum('net_amount'), $siblingBatch?->entries->sum('net_amount') ?? 0]
            : [$siblingBatch?->entries->sum('net_amount') ?? 0, $payroll->entries->sum('net_amount')];

        $totalCombined       = $totalNet1st + $totalNet2nd;
        $belowThresholdCount = $verifyRows->filter(fn ($r) => $r->below_threshold)->count();

        return view('payroll::payroll.verify', compact(
            'payroll', 'siblingBatch', 'verifyRows',
            'totalNet1st', 'totalNet2nd', 'totalCombined', 'belowThresholdCount'
        ));
    }

    public function forceEdit(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('forceEdit', $payroll);
        $request->validate(['remarks' => ['required', 'string', 'min:10', 'max:1000']]);

        $old = $payroll->status;
        $payroll->update(['status' => 'released', 'remarks' => $request->input('remarks')]);
        $this->log($payroll, 'Force Edit Override', $old, 'released');

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Payroll batch unlocked. Status reverted to Released for corrections.');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Payslip Generation (bulk — admin/officer use)
    //
    //  GET /payroll/{payroll}/payslips/generate
    //      ?mode      = consolidated (default) | per_batch
    //      ?entry_id  = <PayrollEntry id>  (optional — single employee only)
    // ═══════════════════════════════════════════════════════════════════

    public function generatePayslips(Request $request, PayrollBatch $payroll)
    {
        if (! in_array($payroll->status, ['released', 'locked'])) {
            abort(403, 'Payslips are only available after the batch has been released.');
        }

        $mode    = $request->input('mode', 'consolidated');
        $entryId = $request->input('entry_id');

        $siblingCutoff = $payroll->cutoff === '1st' ? '2nd' : '1st';
        $sibling = PayrollBatch::where('period_year',  $payroll->period_year)
                               ->where('period_month', $payroll->period_month)
                               ->where('cutoff',       $siblingCutoff)
                               ->first();

        $query = $payroll->entries()
                         ->with(['employee.division', 'deductions.deductionType'])
                         ->orderBy(
                             \App\SharedKernel\Models\Employee::select('last_name')
                                 ->whereColumn('employees.id', 'payroll_entries.employee_id'),
                             'asc'
                         );

        if ($entryId) {
            $query->where('id', $entryId);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            abort(404, 'No payroll entries found for the given parameters.');
        }

        $signatory = Signatory::where('role_type', 'hrmo_designate')
                              ->where('is_active', true)
                              ->first();

        $months = [
            '', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;

        $siblingEntriesById = $sibling
            ? $sibling->entries()
                       ->with('deductions.deductionType')
                       ->get()
                       ->keyBy('employee_id')
            : collect();

        $dedMap = fn ($entry) => $entry
            ? $entry->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $payslips = $entries->map(function ($entry) use ($payroll, $siblingEntriesById, $dedMap, $mode) {
            $siblingEntry = $siblingEntriesById->get($entry->employee_id);

            if ($mode === 'consolidated') {
                if ($payroll->cutoff === '1st') {
                    $entry1st = $entry;
                    $entry2nd = $siblingEntry;
                } else {
                    $entry1st = $siblingEntry;
                    $entry2nd = $entry;
                }
            } else {
                if ($payroll->cutoff === '1st') {
                    $entry1st = $entry;
                    $entry2nd = null;
                } else {
                    $entry1st = null;
                    $entry2nd = $entry;
                }
            }

            return [
                'employee' => $entry->employee,
                'entry1st' => $entry1st,
                'entry2nd' => $entry2nd,
                'ded1st'   => $dedMap($entry1st),
                'ded2nd'   => $dedMap($entry2nd),
            ];
        });

        $pdf = Pdf::loadView('payroll::payroll.payslip', [
            'batch'       => $payroll,
            'payslips'    => $payslips,
            'rows'        => $this->payslipRows(),
            'periodLabel' => $periodLabel,
            'signatory'   => $signatory,
            'mode'        => $mode,
        ])->setPaper('a4', 'portrait');

        $cutoffLabel = $mode === 'consolidated' ? 'Monthly' : ucfirst($payroll->cutoff) . 'Cutoff';
        $filename    = 'Payslips_' . str_replace(' ', '_', $periodLabel) . '_' . $cutoffLabel . '.pdf';

        return $pdf->stream($filename);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Resolve the integer employee PK from the HRIS session token.
     *
     * The HRIS passes employee_no (e.g. "EMP001") via session('hris_employee_id').
     * PayrollEntry.employee_id is the integer PK from the employees table.
     * This method bridges the two so queries always use the correct integer ID.
     *
     * Also handles the edge case where the HRIS already stores the integer PK
     * directly (e.g. after a future HRIS update), making this forward-compatible.
     *
     * @return int|null  Returns null if no HRIS session or employee not found.
     */
    private function resolveHrisEmployeeId(): ?int
    {
        $raw = session('hris_employee_id');

        if (! $raw) {
            return null;
        }

        // If the session already holds a plain integer PK, return it directly.
        // This handles a future HRIS upgrade that stores the PK instead of employee_no.
        if (is_numeric($raw)) {
            return (int) $raw;
        }

        // Otherwise it's a string employee_no like "EMP001" — resolve to the PK.
        $employee = \App\SharedKernel\Models\Employee::where('employee_no', $raw)->first();

        return $employee?->id;
    }

    private function log(PayrollBatch $batch, string $action, ?string $old, ?string $new): void
    {
        PayrollAuditLog::create([
            'payroll_batch_id' => $batch->id,
            'user_id'          => Auth::id(),
            'action'           => $action,
            'old_value'        => $old,
            'new_value'        => $new,
            'ip_address'       => request()->ip(),
        ]);
    }

    private function payslipRows(): array
    {
        return [
            // ── Earnings ─────────────────────────────────────────────────
            ['type' => 'spacer',  'label' => 'EARNINGS',              'code' => null],
            ['type' => 'income',  'label' => 'BASIC',                 'code' => null],
            ['type' => 'income',  'label' => 'ALLOWANCE',             'code' => null],

            // ── Mandatory Deductions ──────────────────────────────────────
            ['type' => 'spacer',     'label' => 'MANDATORY DEDUCTIONS', 'code' => null],
            ['type' => 'deduction',  'label' => 'GSIS — Life/Retirement', 'code' => 'GSIS_LIFE_RETIREMENT'],
            ['type' => 'deduction',  'label' => 'PhilHealth',            'code' => 'PHILHEALTH'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG I',            'code' => 'PAG_IBIG_1'],
            ['type' => 'deduction',  'label' => 'Withholding Tax',       'code' => 'WITHHOLDING_TAX'],

            // ── Loans ─────────────────────────────────────────────────────
            ['type' => 'spacer',     'label' => 'LOANS',              'code' => null],
            ['type' => 'deduction',  'label' => 'GSIS Policy Loan',   'code' => 'GSIS_POLICY'],
            ['type' => 'deduction',  'label' => 'GSIS Emergency Loan','code' => 'GSIS_EMERGENCY'],
            ['type' => 'sub',        'label' => 'GSIS Consolid. Loan','code' => 'GSIS_CONSO'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG II (MP2)',  'code' => 'HDMF_P2'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG MPL',       'code' => 'HDMF_MPL'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG Calamity',  'code' => 'HDMF_CAL'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG Housing',   'code' => 'HDMF_HOUSING'],
            ['type' => 'deduction',  'label' => 'LBP Loan',           'code' => 'LBP_LOAN'],
            ['type' => 'deduction',  'label' => 'GSIS Real Estate',   'code' => 'GSIS_REAL_ESTATE'],
            ['type' => 'deduction',  'label' => 'GSIS MPL',           'code' => 'GSIS_MPL'],
            ['type' => 'deduction',  'label' => 'GSIS CPL',           'code' => 'GSIS_CPL'],
            ['type' => 'deduction',  'label' => 'GSIS MPL Lite',      'code' => 'GSIS_MPL_LITE'],
            ['type' => 'deduction',  'label' => 'GSIS GFAL',          'code' => 'GSIS_GFAL'],
            ['type' => 'deduction',  'label' => 'GSIS HELP',          'code' => 'GSIS_HELP'],
            ['type' => 'deduction',  'label' => 'GSIS Educal Loan',   'code' => 'GSIS_EDUC'],

            // ── Others ────────────────────────────────────────────────────
            ['type' => 'spacer',     'label' => 'OTHERS',             'code' => null],
            ['type' => 'deduction',  'label' => 'CARESS — Union',     'code' => 'CARESS_UNION'],
            ['type' => 'deduction',  'label' => 'CARESS — Mortuary',  'code' => 'CARESS_MORTUARY'],
            ['type' => 'deduction',  'label' => 'MASS',               'code' => 'MASS'],
            ['type' => 'deduction',  'label' => 'Provident Fund',     'code' => 'PROVIDENT_FUND'],
            ['type' => 'deduction',  'label' => 'SSS Contribution',   'code' => 'SSS'],
            ['type' => 'deduction',  'label' => 'HMO',                'code' => 'HMO'],
            ['type' => 'deduction',  'label' => 'CARESS — CAREs',     'code' => 'CARESS_CARES'],
            ['type' => 'deduction',  'label' => 'Smart Plan Gold',    'code' => 'SMART_PLAN_GOLD'],
            ['type' => 'deduction',  'label' => 'Refund (Various)',   'code' => 'REFUND_VARIOUS'],

            // ── Totals & Net ──────────────────────────────────────────────
            ['type' => 'divider', 'label' => 'TOTAL DEDUCTIONS', 'code' => null],
            ['type' => 'net',     'label' => 'NET PAY 1-15',     'code' => null],
            ['type' => 'net',     'label' => 'NET PAY 16-31',    'code' => null],
        ];
    }
}

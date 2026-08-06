<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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
    // Phase 4: pending_hr removed from the workflow.
    // New flow: draft → computed → pending_accountant → pending_rd → released → locked
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
        $user  = Auth::user();
        $query = PayrollBatch::with('creator')
            ->withCount('entries')
            ->withSum('entries', 'gross_income')
            ->withSum('entries', 'total_deductions')
            ->withSum('entries', 'net_amount')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id');

        // Employees can only see released/locked batches
        if (! \App\SharedKernel\Services\RoleService::canAccessPayroll($user)) {
            $query->whereIn('status', ['released', 'locked']);
        }

        if ($request->filled('year'))   $query->where('period_year',  $request->year);
        if ($request->filled('month'))  $query->where('period_month', $request->month);
        if ($request->filled('status')) $query->where('status',       $request->status);

        $batches = $query->paginate(15)->withQueryString();

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
    // ═══════════════════════════════════════════════════════════════════

    public function myPayslip(Request $request)
    {
        $employeeId = $this->resolveHrisEmployeeId();

        if (! $employeeId) {
            $user       = Auth::user();
            $employeeId = $user->employee?->id;
        }

        if (! $employeeId) {
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
    //  View My Payslip — streams the logged-in employee's payslip as PDF
    //
    //  Phase 4 changes:
    //   - Removed sibling batch / cutoff lookup (no more cutoff field).
    //   - Monthly entry is the single source of truth.
    //   - Cutoff breakdown is computed on the fly via computeCutoffSplit().
    // ═══════════════════════════════════════════════════════════════════

    public function viewMyPayslip(Request $request, PayrollBatch $payroll, PayrollEntry $entry)
    {
        if (! in_array($payroll->status, ['released', 'locked'])) {
            abort(403, 'Payslip is not yet available. The payroll batch has not been released.');
        }

        if ((int) $entry->payroll_batch_id !== (int) $payroll->id) {
            abort(404, 'Payslip entry not found in the specified batch.');
        }

        $hrisEmployeeId = $this->resolveHrisEmployeeId();
        if ($hrisEmployeeId && (int) $entry->employee_id !== $hrisEmployeeId) {
            abort(403, 'You are not authorized to view this payslip.');
        }

        $entry->load(['employee.division', 'deductions.deductionType', 'allowances']);

        // Compute cutoff split on the fly from daily_logs
        $snapshot = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->where('employee_id', $entry->employee_id)
            ->first();

        $cutoffSplit = $snapshot
            ? app(PayrollComputationService::class)->computeCutoffSplit($entry, $snapshot)
            : null;

        $dedMap = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $payslips = collect([[
            'employee'    => $entry->employee,
            'entry'       => $entry,
            'cutoffSplit' => $cutoffSplit,
            'dedMap'      => $dedMap($entry),
            'rows'        => $this->payslipRows($entry),
        ]]);

        $months      = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;
        $signatory   = Signatory::where('role_type', 'hrmo_designate')->where('is_active', true)->first();

        $pdf = Pdf::loadView('payroll::payroll.payslip', [
            'batch'       => $payroll,
            'payslips'    => $payslips,
            'rows'        => $this->payslipRows($entry),
            'periodLabel' => $periodLabel,
            'signatory'   => $signatory,
            'mode'        => 'monthly',
        ])->setPaper('a4', 'portrait');

        $employeeName = $entry->employee
            ? str_replace(' ', '_', $entry->employee->full_name)
            : 'Employee';
        $filename = 'Payslip_' . $employeeName . '_' . str_replace(' ', '_', $periodLabel) . '.pdf';

        return $pdf->stream($filename);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Create / Store
    // ═══════════════════════════════════════════════════════════════════

    public function create()
    {
        $this->authorizeRole(\App\SharedKernel\Services\RoleService::getRoleGroup('payroll_create'));

        $currentYear  = now()->year;
        $currentMonth = now()->month;
        $years        = range($currentYear - 2, $currentYear + 1);

        return view('payroll::payroll.create', compact('currentYear', 'currentMonth', 'years'));
    }

    public function store(ComputePayrollRequest $request)
    {
        $year  = (int) $request->period_year;
        $month = (int) $request->period_month;

        // Phase 4: uniqueness check is now per (year, month) only — no cutoff
        $exists = PayrollBatch::where([
            'period_year'  => $year,
            'period_month' => $month,
        ])->exists();

        if ($exists) {
            return back()->withInput()
                ->with('error', "A payroll batch for {$request->periodLabel()} already exists.");
        }

        // Full-month period dates derived from the request helpers (Phase 3 ComputePayrollRequest)
        $periodStart = $request->resolvedPeriodStart();
        $periodEnd   = $request->resolvedPeriodEnd();

        $batch = PayrollBatch::create([
            'period_year'  => $year,
            'period_month' => $month,
            // 'cutoff' no longer stored — removed in Phase 1 migration
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'status'       => 'draft',
            'created_by'   => Auth::id(),
        ]);

        $this->log($batch, 'created', null, 'draft');

        return redirect()->route('payroll.show', $batch)
            ->with('success', "Payroll batch created for {$request->periodLabel()}. Pull attendance first, then click Compute.");
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Show
    // ═══════════════════════════════════════════════════════════════════

    public function show(PayrollBatch $payroll)
    {
        $payroll->load(['entries.employee', 'entries.deductions.deductionType', 'entries.allowances', 'creator', 'auditLogs.user']);

        $entries       = $payroll->entries->sortBy(fn ($e) => optional($e->employee)->last_name ?? '');
        $totalGross    = $payroll->entries->sum('gross_income');
        $totalDeds     = $payroll->entries->sum('total_deductions');
        $totalNet      = $payroll->entries->sum('net_amount');
        $employeeCount = $payroll->entries->count();
        $auditLogs     = $payroll->auditLogs->sortByDesc('performed_at');

        $registerAllowances = app(\Modules\Payroll\Services\AllowanceService::class)
            ->buildRegisterColumns($entries);
        $allowanceColumns = $registerAllowances['columns'];
        $allowanceTotals  = $registerAllowances['totals'];
        $allowanceAmounts = $registerAllowances['amountsForEntry'];

        $attendanceService = app(AttendanceService::class);
        $snapshotCount     = $attendanceService->snapshotCount($payroll);
        $correctedCount    = $attendanceService->correctedCount($payroll);
        $activeCount       = \App\SharedKernel\Models\Employee::where('status', 'active')
            ->where('is_excluded', false)
            ->count();

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
            'snapshotCount', 'correctedCount', 'activeCount', 'snapshots',
            'allowanceColumns', 'allowanceTotals', 'allowanceAmounts'
        ));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Pull Attendance
    // ═══════════════════════════════════════════════════════════════════

    public function pullAttendance(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('compute', $payroll);

        if (in_array($payroll->status, ['released', 'locked'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot re-pull attendance for a released or locked batch.'
                ], 403);
            }
            return back()->with('error', 'Cannot re-pull attendance for a released or locked batch.');
        }

        $result = app(AttendanceService::class)->pullForBatch($payroll);

        $message = "Attendance pulled: {$result['pulled']} employee(s) recorded.";

        if (! empty($result['errors'])) {
            $fullMessage = "{$message} Some employees failed: " . implode('; ', $result['errors']);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $fullMessage
                ]);
            }
            return redirect()->route('payroll.show', $payroll)
                ->with('warning', $fullMessage);
        }

        $this->log($payroll, 'attendance_pulled', null, "pulled:{$result['pulled']}");

        $successMessage = "{$message} Review the attendance records below, then click Compute.";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', $successMessage);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Edit Attendance — NEW in Phase 4
    //
    //  GET  /payroll/{payroll}/attendance/{snapshot}/edit
    //  POST /payroll/{payroll}/attendance/{snapshot}
    //
    //  Allows the payroll officer to correct one employee's snapshot
    //  fields (days_present, lwop_days, late_minutes, undertime_minutes,
    //  leave_credits) before running Compute.
    //  Only allowed while the batch is in draft or computed status.
    // ═══════════════════════════════════════════════════════════════════

    public function editAttendance(Request $request, PayrollBatch $payroll, AttendanceSnapshot $snapshot)
    {
        $this->authorize('compute', $payroll);

        if (! in_array($payroll->status, ['draft', 'computed'])) {
            abort(403, 'Attendance can only be edited while the batch is in Draft or Computed status.');
        }

        // Ensure the snapshot belongs to this batch
        if ((int) $snapshot->payroll_batch_id !== (int) $payroll->id) {
            abort(404);
        }

        $snapshot->load('employee:id,last_name,first_name,employee_no');

        return view('payroll::payroll.attendance-edit', compact('payroll', 'snapshot'));
    }

    public function updateAttendance(Request $request, PayrollBatch $payroll, AttendanceSnapshot $snapshot)
    {
        $this->authorize('compute', $payroll);

        if (! in_array($payroll->status, ['draft', 'computed'])) {
            abort(403, 'Attendance can only be edited while the batch is in Draft or Computed status.');
        }

        if ((int) $snapshot->payroll_batch_id !== (int) $payroll->id) {
            abort(404);
        }

        $data = $request->validate([
            'days_present'      => ['required', 'numeric', 'min:0', 'max:31'],
            'lwop_days'         => ['required', 'numeric', 'min:0', 'max:31'],
            'late_minutes'      => ['required', 'integer', 'min:0'],
            'undertime_minutes' => ['required', 'integer', 'min:0'],
            'leave_credits'     => ['required', 'numeric', 'min:0'],
            'correction_note'   => ['required', 'string', 'min:5', 'max:500'],
        ]);

        app(AttendanceService::class)->correctSnapshot($snapshot, $data, Auth::id());

        $this->log(
            $payroll,
            'attendance_corrected',
            null,
            "Employee #{$snapshot->employee_id}: {$data['correction_note']}"
        );

        return redirect()->route('payroll.show', $payroll)
            ->with('success', 'Attendance record updated. Run Compute again to reflect the changes.');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Compute
    // ═══════════════════════════════════════════════════════════════════

    public function compute(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('compute', $payroll);

        $attendanceService = app(AttendanceService::class);

        if ($attendanceService->snapshotCount($payroll) === 0) {
            $message = 'Attendance has not been pulled yet. Click "Pull Attendance" first.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }
            return redirect()->route('payroll.show', $payroll)
                ->with('error', $message);
        }

        // Phase 8 — Compute options modal. All flags default false; if all
        // are false, computeEntry() computes base salary only and carries
        // over every other component from the last persisted values.
        $options = [
            'apply_attendance' => $request->boolean('apply_attendance'),
            'apply_deductions' => $request->boolean('apply_deductions'),
            'apply_allowances' => $request->boolean('apply_allowances'),
            'apply_lwop'       => $request->boolean('apply_lwop'),
            'force'            => $request->boolean('force'),
        ];

        $attendanceMap = $attendanceService->getAttendanceForBatch($payroll);
        $result        = app(PayrollComputationService::class)->computeBatch($payroll, $attendanceMap, $options);

        if ($payroll->status === 'draft') {
            $payroll->update(['status' => 'computed']);
            $this->log($payroll, 'computed', 'draft', 'computed');
        }

        $appliedLabels = collect([
            'apply_attendance' => 'Attendance',
            'apply_deductions' => 'Deductions',
            'apply_allowances' => 'Allowances',
            'apply_lwop'       => 'LWOP',
        ])->filter(fn ($label, $key) => $options[$key])->values();

        $appliedNote = $appliedLabels->isEmpty()
            ? ' (no components re-applied this pass — basic salary recomputed, everything else carried over from the last compute)'
            : ' (applied this pass: ' . $appliedLabels->implode(', ') . ' — unselected components carried over unchanged)';

        $message = "Computation complete: {$result['computed']} employee(s) processed.{$appliedNote}";

        $skippedCount = (int) ($result['skipped'] ?? 0);
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} employee(s) skipped — manually overridden. Use Force Re-compute to override.";
        }

        $this->log(
            $payroll,
            'computed',
            null,
            "options:" . json_encode($options) . " computed:{$result['computed']} skipped:{$skippedCount}"
        );

        if (! empty($result['errors'])) {
            $fullMessage = "{$message} " . ($skippedCount > 0 ? 'Details: ' : 'Errors: ') . implode('; ', $result['errors']);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $fullMessage,
                    'skipped' => $skippedCount,
                ]);
            }
            return redirect()->route('payroll.show', $payroll)
                ->with('warning', $fullMessage);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'skipped' => $skippedCount,
            ]);
        }

        return redirect()->route('payroll.show', $payroll)->with('success', $message);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  pullAndCompute() REMOVED — Phase 4
    //
    //  The combined pull-and-compute button is gone from the UI (Phase 5).
    //  Pull and Compute are now always two separate steps so HR can review
    //  attendance before computation runs.
    // ═══════════════════════════════════════════════════════════════════

    // ═══════════════════════════════════════════════════════════════════
    //  Approval pipeline
    //  Phase 4: pending_hr step removed.
    //  submit() now goes directly draft/computed → pending_accountant.
    //  hrApprove() removed entirely.
    // ═══════════════════════════════════════════════════════════════════

    public function submit(Request $request, PayrollBatch $payroll)
    {
        $this->authorize('submit', $payroll);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        // Guard: must be computed before submitting
        if ($payroll->status !== 'computed') {
            $message = 'Only a computed batch can be submitted for review.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ]);
            }
            return back()->with('error', $message);
        }

        // Soft gate (was a hard block) — every active entry is still checked
        // against the required compute components (attendance, deductions,
        // allowances, lwop) applied at least once across its compute history.
        // As of 2026-08-06 this no longer blocks submission: the client
        // wants incomplete batches submittable, but the gap is preserved in
        // the audit log for DOLE sign-off traceability instead of being
        // silently dropped.
        $incomplete = $payroll->entries()
            ->with('employee:id,last_name,first_name')
            ->get()
            ->map(fn ($entry) => [
                'entry'   => $entry,
                'missing' => $entry->missingRequiredComponents(),
            ])
            ->filter(fn ($row) => ! empty($row['missing']));

        $gapWarning = null;

        if ($incomplete->isNotEmpty()) {
            $sample = $incomplete->take(5)->map(function ($row) {
                $name = $row['entry']->employee->full_name ?? "Entry #{$row['entry']->id}";
                return "{$name} (missing: " . implode(', ', $row['missing']) . ")";
            })->implode('; ');

            $remaining = $incomplete->count() - 5;
            $gapWarning = "Submitted with {$incomplete->count()} employee(s) missing at least one required component: {$sample}"
                . ($remaining > 0 ? " and {$remaining} more." : '.');

            // Logged separately from the status-transition entry below so the
            // audit trail shows the gap as its own event, not buried in the
            // transition note.
            $this->log($payroll, 'Submitted with incomplete components', null, $gapWarning);
        }

        $old = $payroll->status;

        // Phase 4: skip pending_hr entirely — goes straight to accountant
        $payroll->update([
            'status'      => 'pending_accountant',
            'prepared_at' => now(),
            'remarks'     => $request->input('remarks'),
        ]);
        $this->log($payroll, 'Submitted — Forwarded to Accountant', $old, 'pending_accountant');

        $message = 'Payroll batch submitted. Forwarded to Accountant for certification.';
        if ($gapWarning) {
            $message .= " Note: {$gapWarning} Review the audit log before certification.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'          => true,
                'message'          => $message,
                'has_gaps'         => (bool) $gapWarning,
                'incomplete_count' => $incomplete->count(),
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with($gapWarning ? 'warning' : 'success', $message);
    }

    // hrApprove() REMOVED — Phase 4
    // HR approval step is no longer part of the workflow.
    // The hr_approved_by / hr_approved_at columns are kept in the DB
    // for historical records but are not written by any new workflow.

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

        $message = 'Payroll certified. Forwarded to RD/ARD for approval.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', $message);
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

        $message = 'Payroll approved and released.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', $message);
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

        $message = 'Payroll batch locked. Disbursement complete.';
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('payroll.show', $payroll)
            ->with('success', $message);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Destroy
    // ═══════════════════════════════════════════════════════════════════

    public function destroy(PayrollBatch $payroll)
    {
        $this->authorize('delete', $payroll);

        DB::transaction(function () use ($payroll) {
            foreach ($payroll->entries as $entry) {
                $entry->deductions()->delete();
                // Goal 6: delete is no longer draft-only, so entries reaching
                // here may already have synced allowances (from Compute) —
                // clean those up too or they'd be orphaned.
                $entry->allowances()->delete();
            }
            $payroll->entries()->delete();
            $payroll->auditLogs()->delete();
            AttendanceSnapshot::where('payroll_batch_id', $payroll->id)->delete();
            $payroll->forceDelete();
        });

        return redirect()->route('payroll.index')
            ->with('success', 'Draft payroll batch deleted.');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Verify
    //  Phase 4: removed sibling/cutoff lookup — batch is now monthly-only.
    //  Shows the single monthly batch with net totals per employee.
    // ═══════════════════════════════════════════════════════════════════

    public function verify(PayrollBatch $payroll)
    {
        $this->authorize('view', $payroll);
        $payroll->load(['entries.employee', 'entries.deductions']);

        // Phase 4: no sibling batch exists; cutoff split is done on the fly
        // from daily_logs via computeCutoffSplit() if needed by the view.
        $computationService = app(PayrollComputationService::class);
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->get()
            ->keyBy('employee_id');

        $verifyRows = $payroll->entries
            ->sortBy(fn ($e) => optional($e->employee)->last_name . optional($e->employee)->first_name)
            ->map(function ($entry) use ($snapshots, $computationService) {
                $snapshot    = $snapshots->get($entry->employee_id);
                $netMonthly  = (float) $entry->net_amount;
                $cutoffSplit = $snapshot
                    ? $computationService->computeCutoffSplit($entry, $snapshot)
                    : null;

                $net1st = $cutoffSplit['first_cutoff']['gross_income']  ?? ($netMonthly / 2);
                $net2nd = $cutoffSplit['second_cutoff']['gross_income'] ?? ($netMonthly / 2);

                $hasLbpLoan = $entry->deductions->contains(
                    fn ($d) => stripos($d->code ?? '', 'lbp') !== false
                            || stripos($d->name ?? '', 'lbp') !== false
                );

                return (object) [
                    'employee'        => $entry->employee,
                    'entry_current'   => $entry,
                    'entry_sibling'   => null,      // no sibling in monthly model
                    'net_current'     => $netMonthly,
                    'net_sibling'     => null,
                    'net_1st'         => $net1st,
                    'net_2nd'         => $net2nd,
                    'total_net'       => $netMonthly,
                    'has_lbp_loan'    => $hasLbpLoan,
                    'below_threshold' => $net1st < 5000 || $net2nd < 5000,
                ];
            })
            ->values();

        $totalNetMonthly     = $payroll->entries->sum('net_amount');
        $belowThresholdCount = $verifyRows->filter(fn ($r) => $r->below_threshold)->count();

        return view('payroll::payroll.verify', compact(
            'payroll', 'verifyRows',
            'totalNetMonthly', 'belowThresholdCount'
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
    //  Phase 4 changes:
    //   - No more sibling batch / cutoff lookup.
    //   - mode is always 'monthly' — cutoff breakdown computed on the fly.
    //   - ?entry_id still supported for single-employee generation.
    //
    //  GET /payroll/{payroll}/payslips/generate
    //      ?entry_id = <PayrollEntry id>  (optional)
    // ═══════════════════════════════════════════════════════════════════

    public function generatePayslips(Request $request, PayrollBatch $payroll)
    {
        if (! in_array($payroll->status, ['released', 'locked'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payslips are only available after the batch has been released.'
                ], 403);
            }
            abort(403, 'Payslips are only available after the batch has been released.');
        }

        $entryId = $request->input('entry_id');
        $isAjax = $request->expectsJson();

        $query = $payroll->entries()
            ->with(['employee.division', 'deductions.deductionType', 'allowances'])
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
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payroll entries found for the given parameters.'
                ], 404);
            }
            abort(404, 'No payroll entries found for the given parameters.');
        }

        // If single employee, generate single PDF directly
        if ($entryId) {
            if ($isAjax) {
                return $this->generateSinglePayslipPdfAjax($payroll, $entries->first());
            }
            return $this->generateSinglePayslipPdf($payroll, $entries->first());
        }

        // For multiple employees, process in chunks to avoid memory exhaustion
        // Generate multiple PDFs and return as ZIP
        if ($isAjax) {
            return $this->generateChunkedPayslipsZipAjax($payroll, $entries);
        }
        return $this->generateChunkedPayslipsZip($payroll, $entries);
    }

    private function generateSinglePayslipPdf(PayrollBatch $payroll, $entry)
    {
        // Pre-load snapshots for cutoff split computation
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->get()
            ->keyBy('employee_id');

        $computationService = app(PayrollComputationService::class);
        $dedMap             = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $snapshot    = $snapshots->get($entry->employee_id);
        $cutoffSplit = $snapshot
            ? $computationService->computeCutoffSplit($entry, $snapshot)
            : null;

        $payslips = collect([[
            'employee'    => $entry->employee,
            'entry'       => $entry,
            'cutoffSplit' => $cutoffSplit,
            'dedMap'      => $dedMap($entry),
            'rows'        => $this->payslipRows($entry),
        ]]);

        $months      = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;
        $signatory   = Signatory::where('role_type', 'hrmo_designate')->where('is_active', true)->first();

        $pdf = Pdf::loadView('payroll::payroll.payslip', [
            'batch'       => $payroll,
            'payslips'    => $payslips,
            'rows'        => $this->payslipRows($entry),
            'periodLabel' => $periodLabel,
            'signatory'   => $signatory,
            'mode'        => 'monthly',
        ])->setPaper('a4', 'portrait');

        $employeeName = $entry->employee
            ? str_replace(' ', '_', $entry->employee->full_name)
            : 'Employee';
        $filename = 'Payslip_' . $employeeName . '_' . str_replace(' ', '_', $periodLabel) . '.pdf';

        return $pdf->stream($filename);
    }

    private function generateSinglePayslipPdfAjax(PayrollBatch $payroll, $entry)
    {
        // Pre-load snapshots for cutoff split computation
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->get()
            ->keyBy('employee_id');

        $computationService = app(PayrollComputationService::class);
        $dedMap             = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $snapshot    = $snapshots->get($entry->employee_id);
        $cutoffSplit = $snapshot
            ? $computationService->computeCutoffSplit($entry, $snapshot)
            : null;

        $payslips = collect([[
            'employee'    => $entry->employee,
            'entry'       => $entry,
            'cutoffSplit' => $cutoffSplit,
            'dedMap'      => $dedMap($entry),
            'rows'        => $this->payslipRows($entry),
        ]]);

        $months      = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;
        $signatory   = Signatory::where('role_type', 'hrmo_designate')->where('is_active', true)->first();

        $pdf = Pdf::loadView('payroll::payroll.payslip', [
            'batch'       => $payroll,
            'payslips'    => $payslips,
            'rows'        => $this->payslipRows($entry),
            'periodLabel' => $periodLabel,
            'signatory'   => $signatory,
            'mode'        => 'monthly',
        ])->setPaper('a4', 'portrait');

        $employeeName = $entry->employee
            ? str_replace(' ', '_', $entry->employee->full_name)
            : 'Employee';
        $filename = 'Payslip_' . $employeeName . '_' . str_replace(' ', '_', $periodLabel) . '.pdf';

        // Get PDF content as string
        $pdfContent = $pdf->output();

        // Return JSON with file content as base64
        return response()->json([
            'success' => true,
            'file_content' => base64_encode($pdfContent),
            'filename' => $filename,
            'content_type' => 'application/pdf'
        ]);
    }

    private function generateChunkedPayslipsZip(PayrollBatch $payroll, $entries)
    {
        // Increase memory limit for this operation
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $chunkSize = 20; // Process 20 employees per PDF to avoid memory issues
        $chunks = $entries->chunk($chunkSize);

        // Pre-load all snapshots for cutoff split computation
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->get()
            ->keyBy('employee_id');

        $computationService = app(PayrollComputationService::class);
        $dedMap             = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $months      = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;
        $signatory   = Signatory::where('role_type', 'hrmo_designate')->where('is_active', true)->first();

        // Create temporary directory for PDFs
        $tempDir = storage_path('app/temp/payslips_' . $payroll->id . '_' . time());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFiles = [];

        foreach ($chunks as $index => $chunk) {
            // Clear memory before processing each chunk
            gc_collect_cycles();

            $payslips = $chunk->map(function ($entry) use ($snapshots, $computationService, $dedMap) {
                $snapshot    = $snapshots->get($entry->employee_id);
                $cutoffSplit = $snapshot
                    ? $computationService->computeCutoffSplit($entry, $snapshot)
                    : null;

                return [
                    'employee'    => $entry->employee,
                    'entry'       => $entry,
                    'cutoffSplit' => $cutoffSplit,
                    'dedMap'      => $dedMap($entry),
                    'rows'        => $this->payslipRows($entry),
                ];
            });

            $pdf = Pdf::loadView('payroll::payroll.payslip', [
                'batch'       => $payroll,
                'payslips'    => $payslips,
                'rows'        => [],
                'periodLabel' => $periodLabel,
                'signatory'   => $signatory,
                'mode'        => 'monthly',
            ])->setPaper('a4', 'portrait');

            $chunkFilename = $tempDir . '/Payslips_Part' . ($index + 1) . '.pdf';
            $pdf->save($chunkFilename);
            $pdfFiles[] = $chunkFilename;

            // Free memory
            unset($pdf);
            unset($payslips);
        }

        // Create ZIP file
        $zipFilename = $tempDir . '/Payslips_' . str_replace(' ', '_', $periodLabel) . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFilename, \ZipArchive::CREATE) === true) {
            foreach ($pdfFiles as $pdfFile) {
                $zip->addFile($pdfFile, basename($pdfFile));
            }
            $zip->close();
        }

        // Stream the ZIP file
        return response()->download($zipFilename, 'Payslips_' . str_replace(' ', '_', $periodLabel) . '.zip')->deleteFileAfterSend(true);
    }

    private function generateChunkedPayslipsZipAjax(PayrollBatch $payroll, $entries)
    {
        // Increase memory limit for this operation
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $chunkSize = 20; // Process 20 employees per PDF to avoid memory issues
        $chunks = $entries->chunk($chunkSize);

        // Pre-load all snapshots for cutoff split computation
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $payroll->id)
            ->get()
            ->keyBy('employee_id');

        $computationService = app(PayrollComputationService::class);
        $dedMap             = fn ($e) => $e
            ? $e->deductions->keyBy(fn ($d) => $d->deductionType->code ?? $d->name)
            : collect();

        $months      = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabel = ($months[$payroll->period_month] ?? '') . ' ' . $payroll->period_year;
        $signatory   = Signatory::where('role_type', 'hrmo_designate')->where('is_active', true)->first();

        // Create temporary directory for PDFs
        $tempDir = storage_path('app/temp/payslips_' . $payroll->id . '_' . time());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFiles = [];

        foreach ($chunks as $index => $chunk) {
            // Clear memory before processing each chunk
            gc_collect_cycles();

            $payslips = $chunk->map(function ($entry) use ($snapshots, $computationService, $dedMap) {
                $snapshot    = $snapshots->get($entry->employee_id);
                $cutoffSplit = $snapshot
                    ? $computationService->computeCutoffSplit($entry, $snapshot)
                    : null;

                return [
                    'employee'    => $entry->employee,
                    'entry'       => $entry,
                    'cutoffSplit' => $cutoffSplit,
                    'dedMap'      => $dedMap($entry),
                    'rows'        => $this->payslipRows($entry),
                ];
            });

            $pdf = Pdf::loadView('payroll::payroll.payslip', [
                'batch'       => $payroll,
                'payslips'    => $payslips,
                'rows'        => [],
                'periodLabel' => $periodLabel,
                'signatory'   => $signatory,
                'mode'        => 'monthly',
            ])->setPaper('a4', 'portrait');

            $chunkFilename = $tempDir . '/Payslips_Part' . ($index + 1) . '.pdf';
            $pdf->save($chunkFilename);
            $pdfFiles[] = $chunkFilename;

            // Free memory
            unset($pdf);
            unset($payslips);
        }

        // Create ZIP file
        $zipFilename = $tempDir . '/Payslips_' . str_replace(' ', '_', $periodLabel) . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFilename, \ZipArchive::CREATE) === true) {
            foreach ($pdfFiles as $pdfFile) {
                $zip->addFile($pdfFile, basename($pdfFile));
            }
            $zip->close();
        }

        // Read ZIP file content
        $zipContent = file_get_contents($zipFilename);

        // Clean up temp directory
        foreach ($pdfFiles as $pdfFile) {
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }
        }
        if (file_exists($zipFilename)) {
            unlink($zipFilename);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }

        // Return JSON with file content as base64
        return response()->json([
            'success' => true,
            'file_content' => base64_encode($zipContent),
            'filename' => 'Payslips_' . str_replace(' ', '_', $periodLabel) . '.zip',
            'content_type' => 'application/zip'
        ]);
    }

    public function downloadPayslip(Request $request, $file)
    {
        // Sanitize filename to prevent directory traversal
        $file = basename($file);

        // Search for the file in temp directories
        $tempDirs = glob(storage_path('app/temp/payslips_*'));
        $filePath = null;

        foreach ($tempDirs as $dir) {
            $potentialPath = $dir . '/' . $file;
            if (file_exists($potentialPath)) {
                $filePath = $potentialPath;
                break;
            }
        }

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, 'File not found or has expired.');
        }

        // Determine content type
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentType = match($extension) {
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };

        return response()->download($filePath, $file, ['Content-Type' => $contentType])->deleteFileAfterSend(true);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Resolve the integer employee PK from the HRIS session token.
     */
    private function resolveHrisEmployeeId(): ?int
    {
        $raw = session('hris_employee_id');

        if (! $raw) {
            return null;
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        $employee = \App\SharedKernel\Models\Employee::where('employee_no', $raw)->first();

        return $employee?->id;
    }

    private function authorizeRole(array $roles): void
    {
        if (! Auth::user()->hasAnyRole($roles)) {
            abort(403);
        }
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

    private function payslipRows(?\Modules\Payroll\Models\PayrollEntry $entry = null): array
    {
        $deductionRows = [
            ['type' => 'spacer',     'label' => 'MANDATORY DEDUCTIONS',    'code' => null],
            ['type' => 'deduction',  'label' => 'GSIS — Life/Retirement',  'code' => 'GSIS_LIFE_RETIREMENT'],
            ['type' => 'deduction',  'label' => 'PhilHealth',               'code' => 'PHILHEALTH'],
            ['type' => 'deduction',  'label' => 'Pag-IBIG I',               'code' => 'PAG_IBIG_1'],
            ['type' => 'deduction',  'label' => 'Withholding Tax',          'code' => 'WITHHOLDING_TAX'],

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
            ['type' => 'net',     'label' => 'NET PAY 1–15',     'code' => null],
            ['type' => 'net',     'label' => 'NET PAY 16–31',    'code' => null],
            ['type' => 'net',     'label' => 'TOTAL NET PAY',    'code' => null],
        ];

        return \Modules\Payroll\Support\PayslipAllowanceRows::merge($deductionRows, $entry);
    }
}

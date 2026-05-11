<?php

namespace Modules\Tev\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Tev\Http\Requests\StoreTevRequest;
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\OfficeOrder;
use Modules\Payroll\Models\PayrollAuditLog;
use Modules\Tev\Models\PerDiemRate;
use Modules\Tev\Models\TevApprovalLog;
use Modules\Tev\Models\TevCertification;
use Modules\Tev\Models\TevItineraryLine;
use Modules\Tev\Models\TevRequest;
use Modules\Tev\Services\TevComputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class TevController extends Controller
{
    public function __construct(private TevComputationService $tevService) {}

    // =====================================================================
    //  DASHBOARD
    // =====================================================================

    /**
     * TEV Dashboard - role-based routing.
     * Officers get comprehensive dashboard, employees get redirected to requests.
     */
    public function dashboard()
    {
        /** @var User $user */
        $user = Auth::user();

        // Officers get the comprehensive dashboard
        if ($user->hasAnyRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier', 'payroll_officer', 'super_admin'])) {
            return $this->officerDashboard();
        } else {
            // Employees go directly to their requests page (like my-payslip)
            return redirect()->route('tev.requests.index');
        }
    }

    /**
     * TEV Employee Dashboard - personalized view for regular employees.
     * Shows their own TEV requests and basic system status.
     */
    public function employeeDashboard()
    {
        /** @var User $user */
        $user = Auth::user();
        $userId = $user->id;
        $employeeId = $this->resolveHrisEmployeeId();

        // Count of pending TEV requests (system-wide - for info only)
        $pendingRequests = TevRequest::where('status', 'submitted')->count();

        // Count of my requests pending approval
        $pendingMyApproval = 0;
        if ($employeeId) {
            $pendingMyApproval = TevRequest::where('employee_id', $employeeId)
                ->whereNotIn('status', ['released', 'reimbursed', 'cancelled'])
                ->count();
        }

        // Count of my requests this month (submitted by me)
        $myRequestsThisMonth = TevRequest::where('submitted_by', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // 5 most recent MY TEV requests only
        $recentRequests = TevRequest::with(['employee', 'officeOrder'])
            ->where('submitted_by', $userId)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('tev::dashboard', compact(
            'pendingRequests',
            'pendingMyApproval',
            'myRequestsThisMonth',
            'recentRequests'
        ));
    }

    /**
     * TEV Officer Dashboard - comprehensive view for officers and admins.
     * Shows TEV pipeline, queue counts, and system-wide metrics.
     */
    public function officerDashboard()
    {
        /** @var User $user */
        $user = Auth::user();

        // ----------------------------------------------------------------
        // Shared context
        // ----------------------------------------------------------------
        $totalEmployees = \App\SharedKernel\Models\Employee::where('status', 'active')->count();
        $currentMonth   = now()->format('F Y');

        // Total TEVs filed this month
        $tevThisMonth = TevRequest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // ----------------------------------------------------------------
        // Role-scoped pending counts for TEV
        // ----------------------------------------------------------------
        $pendingTev = 0;
        $pendingLiquidation = 0;
        $pendingApprovals = 0;

        if ($user->hasRole('super_admin')) {
            $pendingTev = TevRequest::whereNotIn('status', ['released', 'reimbursed', 'cancelled'])->count();
        } elseif ($user->hasRole('accountant')) {
            $pendingTev = TevRequest::where('status', 'submitted')->count();
        } elseif ($user->hasAnyRole(['ard', 'chief_admin_officer'])) {
            $pendingTev = TevRequest::where('status', 'accountant_certified')->count();
        } elseif ($user->hasRole('cashier')) {
            $pendingTev = TevRequest::where('status', 'rd_approved')->count();
            $pendingLiquidation = TevRequest::where('status', 'liquidation_filed')->count();
        } elseif ($user->hasRole('budget_officer')) {
            $pendingTev = TevRequest::where('status', 'submitted')->count();
        } elseif ($user->hasRole('hrmo')) {
            // HRMO monitors cash advances that need liquidation
            $pendingTev = TevRequest::where('status', 'cashier_released')
                ->where('track', 'cash_advance')
                ->count();
        }

        $pendingApprovals = $pendingTev + $pendingLiquidation;

        // ----------------------------------------------------------------
        // TEV Queue counts for pipeline overview
        // ----------------------------------------------------------------
        $tevSubmitted    = TevRequest::where('status', 'submitted')->count();
        $tevCertified    = TevRequest::where('status', 'accountant_certified')->count();
        $tevRdApproved   = TevRequest::where('status', 'rd_approved')->count();
        $tevLiqFiled     = TevRequest::where('status', 'liquidation_filed')->count();
        $tevCashReleased = TevRequest::where('status', 'cashier_released')->where('track', 'cash_advance')->count();
        $tevLiquidated   = TevRequest::where('status', 'liquidated')->count();
        $tevReimbursement = TevRequest::where('track', 'reimbursement')->count();

        // ----------------------------------------------------------------
        // Recent TEV activity (all requests for officers)
        // ----------------------------------------------------------------
        $recentTev = TevRequest::with(['employee', 'officeOrder'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('tev::officer-dashboard', compact(
            'totalEmployees',
            'currentMonth',
            'pendingApprovals',
            'pendingTev',
            'pendingLiquidation',
            'tevThisMonth',
            'tevSubmitted',
            'tevCertified',
            'tevRdApproved',
            'tevLiqFiled',
            'tevCashReleased',
            'tevLiquidated',
            'tevReimbursement',
            'recentTev'
        ));
    }

    // =====================================================================
    //  INDEX
    // =====================================================================

    /**
     * List all TEV requests with optional filtering by track, status, and year.
     *
     * - Officers: Can see all requests
     * - Employees: Can only see their own requests
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $isEmployee = !$user->hasAnyRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier', 'payroll_officer', 'super_admin']);
        
        // Base query
        $baseQuery = TevRequest::with(['employee', 'officeOrder'])->orderByDesc('id');
        
        // Employees can only see their own requests
        if ($isEmployee) {
            $baseQuery->where('employee_id', $this->resolveHrisEmployeeId());
        }

        // Separate liquidated TEVs (completed)
        $liquidatedQuery = clone $baseQuery;
        $liquidatedQuery->whereIn('status', ['reimbursed', 'liquidated']);
        
        // Apply filters to liquidated
        if ($request->filled('track'))  $liquidatedQuery->where('track', $request->track);
        if ($request->filled('year'))   $liquidatedQuery->whereYear('travel_date_start', $request->year);
        
        $liquidatedRequests = $liquidatedQuery->paginate(20, ['*'], 'liquidated_page')->withQueryString();

        // Separate in-process TEVs (not completed)
        $inProcessQuery = clone $baseQuery;
        $inProcessQuery->whereNotIn('status', ['reimbursed', 'liquidated']);
        
        // Apply filters to in-process
        if ($request->filled('process_track'))  $inProcessQuery->where('track', $request->process_track);
        if ($request->filled('process_status')) $inProcessQuery->where('status', $request->process_status);
        if ($request->filled('year'))         $inProcessQuery->whereYear('travel_date_start', $request->year);
        
        $inProcessRequests = $inProcessQuery->paginate(20, ['*'], 'process_page')->withQueryString();

        $currentYear = now()->year;
        $activeTab = $request->get('tab', 'process'); // Default to process tab

        // Use employee-specific view for employees, regular view for officers
        if ($isEmployee) {
            return view('tev::employee-requests', compact('liquidatedRequests', 'inProcessRequests', 'currentYear', 'isEmployee', 'activeTab'));
        }

        return view('tev::index', compact('liquidatedRequests', 'inProcessRequests', 'currentYear', 'isEmployee', 'activeTab'));
    }

    // =====================================================================
    //  CREATE / STORE
    // =====================================================================

    /**
     * Show the form for filing a new TEV request.
     *
     * - HRMO: Can file TEVs for any employee with approved office orders
     * - Employees: Can file TEVs for themselves (no office order required)
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        $isEmployee = !$user->hasAnyRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier', 'super_admin']);

        $approvedOrdersQuery = OfficeOrder::with('employee')
            ->where('status', 'approved');

        if ($isEmployee) {
            $employeeId = $this->resolveHrisEmployeeId();
            if ($employeeId) {
                $approvedOrdersQuery->where('employee_id', $employeeId);
            } else {
                $approvedOrders = collect();
                return view('tev::create', compact('approvedOrders'));
            }
        }

        $approvedOrders = $approvedOrdersQuery->orderByDesc('id')->get();

        $perDiemRates = PerDiemRate::all()->groupBy('travel_type');

        return view('tev::create', compact('approvedOrders', 'perDiemRates', 'isEmployee'));
    }

    /**
     * Persist a new TEV request and auto-submit it to the accountant queue.
     *
     * - HRMO: Files TEVs on behalf of employees using office orders
     * - Employees: File TEVs for themselves directly
     */
    public function store(StoreTevRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $isEmployee = !$user->hasAnyRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier', 'payroll_officer', 'super_admin']);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $user, $isEmployee) {
            // Determine employee_id
            $employeeId = $isEmployee
                ? $this->resolveHrisEmployeeId() // Employee filing for themselves
                : OfficeOrder::findOrFail($validated['office_order_id'])->employee_id; // HRMO filing for employee

            $officeOrderId = $validated['office_order_id'];

            $tev = TevRequest::create([
                'tev_no'               => $this->tevService->generateTevNo(),
                'office_order_id'      => $officeOrderId,
                'employee_id'          => $employeeId,
                'track'                => $validated['track'],
                'purpose'              => $validated['purpose'],
                'destination'          => $validated['destination'],
                'travel_type'          => $validated['travel_type'],
                'travel_date_start'    => $validated['travel_date_start'],
                'travel_date_end'      => $validated['travel_date_end'],
                'total_other_expenses' => 0,
                // Auto-submitted: enters 'submitted' status immediately
                'status'               => 'submitted',
                'submitted_by'         => Auth::id(),
                'submitted_at'         => now(),
                'remarks'              => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['lines'] as $line) {
                TevItineraryLine::create([
                    'tev_request_id'      => $tev->id,
                    'travel_date'         => $line['travel_date'],
                    'origin'              => $line['origin'],
                    'destination'         => $line['destination'],
                    'departure_time'      => $line['departure_time'] ?? null,
                    'arrival_time'        => $line['arrival_time'] ?? null,
                    'mode_of_transport'   => $line['mode_of_transport'],
                    'transportation_cost' => $line['transportation_cost'],
                    'per_diem_amount'     => $line['per_diem_amount'],
                    'is_half_day'         => !empty($line['is_half_day']),
                    'remarks'             => $line['remarks'] ?? null,
                ]);
            }

            $this->tevService->computeTotals($tev);

            // Record the auto-submission as the first entry in the approval timeline
            TevApprovalLog::create([
                'tev_request_id' => $tev->id,
                'user_id'        => Auth::id(),
                'step'           => 'submitted',
                'action'         => 'approved',
                'remarks'        => $isEmployee ? 'Auto-submitted on creation by Employee.' : 'Auto-submitted on creation by HRMO.',
                'ip_address'     => $request->ip(),
            ]);

            PayrollAuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'Created & Submitted TEV: ' . $tev->tev_no,
                'old_value'  => null,
                'new_value'  => 'submitted',
                'ip_address' => $request->ip(),
            ]);

            $this->lastCreatedId = $tev->id;
        });

        return redirect()->route('tev.requests.show', $this->lastCreatedId)
            ->with('success', 'TEV created and submitted to Accountant for review.');
    }

    // Holds the ID of the record created inside the transaction so the
    // redirect after store() can reference it outside the closure scope.
    private int $lastCreatedId;

    // =====================================================================
    //  SHOW
    // =====================================================================

    /**
     * Display a single TEV request with its full approval timeline.
     *
     * - Officers: Can view any TEV request
     * - Employees: Can only view their own requests
     */
    public function show(int $id)
    {
        /** @var User $user */
        $user = Auth::user();
        $isEmployee = !$user->hasAnyRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier', 'payroll_officer', 'super_admin']);

        $tev = TevRequest::with([
            'employee',
            'officeOrder',
            'itineraryLines',
            'approvalLogs' => fn($q) => $q->with('user')->orderBy('performed_at'),
            'certification.certifier',
        ])->findOrFail($id);

        // Employees can only view their own requests
        if ($isEmployee && $tev->employee_id !== $this->resolveHrisEmployeeId()) {
            abort(403, 'You can only view your own TEV requests.');
        }

        [$canApprove, $nextAction] = $this->resolveApproval($tev);

        return view('tev::show', compact('tev', 'canApprove', 'nextAction', 'isEmployee'));
    }

    // =====================================================================
    //  SUBMIT (legacy — disabled)
    // =====================================================================

    /**
     * Manual submission is no longer part of the TEV workflow.
     *
     * TEVs are auto-submitted on creation by HRMO. This method is retained
     * only to prevent a 404 if a stale link is followed, and always returns
     * 410 Gone to signal that the endpoint has been intentionally retired.
     */
    public function submit(Request $request, int $tevRequest)
    {
        abort(410, 'Manual submission is no longer required. TEVs are automatically submitted on creation.');
    }

    // =====================================================================
    //  APPROVE
    // =====================================================================

    /**
     * Advance a TEV through its role-based approval workflow.
     *
     * The next status and audit label are resolved by resolveTransition(),
     * which enforces that the current user's role matches the expected step.
     * When a cashier releases a cash advance, the grand total is also
     * recorded as the cash_advance_amount for later liquidation reconciliation.
     */
    public function approve(Request $request, int $tevRequest)
    {
        $tev = TevRequest::findOrFail($tevRequest);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        [$newStatus, $stepLabel] = $this->resolveTransition($tev);
        $old = $tev->status;

        $updateData = ['status' => $newStatus];

        // Record the grand total as the advance amount when the cashier releases a CA,
        // so the liquidation step has a fixed baseline to reconcile against.
        if ($newStatus === 'cashier_released') {
            $updateData['cash_advance_amount'] = $tev->grand_total;
        }

        $tev->update($updateData);

        TevApprovalLog::create([
            'tev_request_id' => $tev->id,
            'user_id'        => Auth::id(),
            'step'           => $newStatus,
            'action'         => 'approved',
            'remarks'        => $request->remarks,
            'ip_address'     => $request->ip(),
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $stepLabel . ': ' . $tev->tev_no,
            'old_value'  => $old,
            'new_value'  => $newStatus,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tev.requests.show', $tev->id)
            ->with('success', 'TEV ' . $stepLabel . ' successfully.');
    }

    // =====================================================================
    //  REJECT
    // =====================================================================

    /**
     * Reject a TEV at the current user's responsible step.
     *
     * Each role may only reject at the step they own — this mirrors the
     * $canReject logic in the Blade view and must be kept in sync with it.
     * A rejection reason is mandatory to maintain a meaningful audit trail.
     */
    public function reject(Request $request, int $tevRequest)
    {
        $tev = TevRequest::findOrFail($tevRequest);

        $request->validate(
            ['remarks' => ['required', 'string', 'max:500']],
            ['remarks.required' => 'A reason is required when rejecting a TEV.']
        );

        /** @var User $user */
        $user = Auth::user();

        $authorized = (
            ($tev->status === 'submitted'            && $user->hasAnyRole(['accountant'])) ||
            ($tev->status === 'accountant_certified' && $user->hasAnyRole(['ard', 'chief_admin_officer'])) ||
            ($tev->status === 'rd_approved'          && $user->hasAnyRole(['cashier']))
        );

        if (!$authorized) {
            abort(403, 'You are not authorized to reject this TEV at its current status.');
        }

        $old = $tev->status;
        $tev->update(['status' => 'rejected']);

        TevApprovalLog::create([
            'tev_request_id' => $tev->id,
            'user_id'        => Auth::id(),
            'step'           => 'rejected',
            'action'         => 'rejected',
            'remarks'        => $request->remarks,
            'ip_address'     => $request->ip(),
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Rejected TEV: ' . $tev->tev_no,
            'old_value'  => $old,
            'new_value'  => 'rejected',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tev.requests.show', $tev->id)
            ->with('error', 'TEV has been rejected.');
    }

    // =====================================================================
    //  CERTIFY
    // =====================================================================

    /**
     * Save post-travel certification details for a TEV.
     *
     * Certification confirms that travel actually took place and captures
     * supporting details (agency visited, Annex A amounts, etc.). It is
     * available once the TEV reaches rd_approved or any later status, since
     * the employee will have returned from travel by that point.
     * updateOrCreate is used so the form can be re-submitted to correct
     * certification details without creating duplicate records.
     */
    public function certify(Request $request, int $tevRequest)
    {
        $this->authorizeRole(['hrmo', 'accountant']);

        $tev = TevRequest::findOrFail($tevRequest);

        $certifiableStatuses = ['rd_approved', 'cashier_released', 'reimbursed', 'liquidation_filed', 'liquidated'];
        if (!in_array($tev->status, $certifiableStatuses)) {
            return back()->with('error', 'TEV must be at rd_approved or later to certify.');
        }

        $data = $request->validate([
            'travel_completed'    => ['nullable', 'boolean'],
            'date_returned'       => ['nullable', 'date'],
            'place_reported_back' => ['nullable', 'string', 'max:100'],
            'annex_a_amount'      => ['nullable', 'numeric', 'min:0'],
            'annex_a_particulars' => ['nullable', 'string'],
            'agency_visited'      => ['nullable', 'string', 'max:255'],
            'appearance_date'     => ['nullable', 'date'],
            'contact_person'      => ['nullable', 'string', 'max:255'],
        ]);

        TevCertification::updateOrCreate(
            ['tev_request_id' => $tev->id],
            array_merge($data, [
                'certified_by'     => Auth::id(),
                'certified_at'     => now(),
                'travel_completed' => !empty($data['travel_completed']),
            ])
        );

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Certified TEV: ' . $tev->tev_no,
            'old_value'  => null,
            'new_value'  => 'certified',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tev.requests.show', $tev->id)
            ->with('success', 'TEV certification saved.');
    }

    // =====================================================================
    //  LIQUIDATION
    // =====================================================================

    /**
     * File actual expenses against a released cash advance TEV.
     *
     * Only applies to cash_advance track TEVs after the cashier has released
     * the advance. The balance_due is derived as advance minus actual spend:
     * a positive value means the employee owes a refund; a negative value
     * means DOLE owes the employee an additional payment.
     * Both the employee themselves and HRMO staff may file on their behalf.
     */
    public function fileLiquidation(Request $request, int $tevRequest)
    {
        $tev  = TevRequest::findOrFail($tevRequest);
        /** @var User $user */
        $user = Auth::user();

        if ($tev->track !== 'cash_advance') {
            return back()->with('error', 'Liquidation only applies to Cash Advance TEVs.');
        }

        if ($tev->status !== 'cashier_released') {
            return back()->with('error', 'Liquidation can only be filed after the cash advance is released.');
        }

        // Check if user is the employee who owns this TEV (for HRIS users, check session)
        $isOwner = false;
        if ($tev->employee) {
            $isOwner = ($tev->employee->user_id === $user->id) ||
                       ($tev->employee_id === $this->resolveHrisEmployeeId());
        }
        $isStaff = $user->hasAnyRole(['hrmo']);

        if (!$isOwner && !$isStaff) {
            abort(403, 'You are not authorized to file liquidation for this TEV.');
        }

        $data = $request->validate([
            'actual_amount' => ['required', 'numeric', 'min:0'],
            'remarks'       => ['nullable', 'string', 'max:500'],
        ], [
            'actual_amount.required' => 'Actual amount spent is required.',
            'actual_amount.numeric'  => 'Actual amount must be a valid number.',
            'actual_amount.min'      => 'Actual amount cannot be negative.',
        ]);

        $actualAmount  = (float) $data['actual_amount'];
        $advanceAmount = (float) ($tev->cash_advance_amount ?? $tev->grand_total);

        // Positive = employee owes a refund; negative = DOLE owes employee additional payment
        $balanceDue = round($advanceAmount - $actualAmount, 2);

        $tev->update([
            'status'              => 'liquidation_filed',
            'cash_advance_amount' => $advanceAmount,
            'balance_due'         => $balanceDue,
        ]);

        TevApprovalLog::create([
            'tev_request_id' => $tev->id,
            'user_id'        => Auth::id(),
            'step'           => 'liquidation_filed',
            'action'         => 'approved',
            'remarks'        => $data['remarks']
                ?? 'Liquidation filed. Actual amount: ₱' . number_format($actualAmount, 2)
                . '. Balance due: ₱' . number_format(abs($balanceDue), 2)
                . ($balanceDue > 0 ? ' (to refund)' : ($balanceDue < 0 ? ' (to claim)' : ' (settled)')),
            'ip_address'     => $request->ip(),
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Filed Liquidation for TEV: ' . $tev->tev_no,
            'old_value'  => 'cashier_released',
            'new_value'  => 'liquidation_filed',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tev.requests.show', $tev->id)
            ->with('success', 'Liquidation filed successfully. Awaiting cashier approval.');
    }

    /**
     * Finalise a liquidation filing as the cashier.
     *
     * This is the terminal step for cash advance TEVs. The cashier confirms
     * the actual expenses are correct and moves the record to 'liquidated'.
     */
    public function approveLiquidation(Request $request, int $tevRequest)
    {
        $this->authorizeRole(['cashier']);

        $tev = TevRequest::findOrFail($tevRequest);

        if ($tev->track !== 'cash_advance') {
            return back()->with('error', 'Liquidation only applies to Cash Advance TEVs.');
        }

        if ($tev->status !== 'liquidation_filed') {
            return back()->with('error', 'TEV must be in liquidation_filed status to approve.');
        }

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $tev->update(['status' => 'liquidated']);

        TevApprovalLog::create([
            'tev_request_id' => $tev->id,
            'user_id'        => Auth::id(),
            'step'           => 'liquidated',
            'action'         => 'approved',
            'remarks'        => $data['remarks'] ?? null,
            'ip_address'     => $request->ip(),
        ]);

        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Approved Liquidation for TEV: ' . $tev->tev_no,
            'old_value'  => 'liquidation_filed',
            'new_value'  => 'liquidated',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('tev.requests.show', $tev->id)
            ->with('success', 'Liquidation approved. TEV is now fully liquidated.');
    }

    // =====================================================================
    //  DESTROY (not permitted)
    // =====================================================================

    /**
     * TEV records may not be deleted.
     *
     * Once filed, a TEV is part of the financial audit trail and must be
     * retained. Rejection or liquidation are the appropriate terminal states.
     */
    public function destroy(int $id)
    {
        abort(405);
    }

    // =====================================================================
    //  Private helpers
    // =====================================================================

    /**
     * Determine whether the current user can act on a TEV, and what the
     * action label should be for the button in the view.
     *
     * Returns [bool $canApprove, string $nextAction]. The map covers every
     * status that has a pending approval step; any other status returns
     * [false, ''] so the view knows to hide the action button.
     */
    private function resolveApproval(TevRequest $tev): array
    {
        /** @var User $user */
        $user   = Auth::user();
        $status = $tev->status;

        $map = [
            'submitted'            => [['accountant'],                 'Certify (Accountant)'],
            'accountant_certified' => [['ard', 'chief_admin_officer'], 'RD Approve'],
            'rd_approved'          => [['cashier'],                    $tev->track === 'cash_advance' ? 'Release Cash Advance' : 'Mark Reimbursed'],
            'liquidation_filed'    => [['cashier'],                    'Approve Liquidation'],
        ];

        if (!isset($map[$status])) {
            return [false, ''];
        }

        [$roles, $label] = $map[$status];
        return [$user->hasAnyRole($roles), $label];
    }

    /**
     * Resolve the next status and audit label for the current user's approval step.
     *
     * Aborts with 403 if the user's role does not match the expected step for
     * the TEV's current status, ensuring the transition map is the single
     * source of truth for workflow progression.
     */
    private function resolveTransition(TevRequest $tev): array
    {
        /** @var User $user */
        $user   = Auth::user();
        $status = $tev->status;

        if ($status === 'submitted' && $user->hasAnyRole(['accountant'])) {
            return ['accountant_certified', 'Accountant Certified'];
        }
        if ($status === 'accountant_certified' && $user->hasAnyRole(['ard', 'chief_admin_officer'])) {
            return ['rd_approved', 'RD Approved'];
        }
        if ($status === 'rd_approved' && $user->hasAnyRole(['cashier'])) {
            $newStatus = $tev->track === 'cash_advance' ? 'cashier_released' : 'reimbursed';
            $label     = $tev->track === 'cash_advance' ? 'Cash Advance Released' : 'Reimbursed';
            return [$newStatus, $label];
        }

        abort(403, 'You are not authorized to approve this TEV at its current status.');
    }

    /**
     * Abort with 403 if the authenticated user does not hold any of the given roles.
     */
    private function authorizeRole(array $roles): void
    {
        // super_admin bypasses all role checks — view access to all modules
        /** @var User $user */
        $user = Auth::user();
        if ($user->hasRole('super_admin')) {
            return;
        }

        if (!$user->hasAnyRole($roles)) {
            abort(403);
        }
    }

    /**
     * Resolve the integer employee PK from the HRIS session token.
     *
     * The HRIS passes employee_no (e.g. "EMP001") via session('hris_employee_id').
     * TevRequest.employee_id is the integer PK from the employees table.
     * This method bridges the two so queries always use the correct integer ID.
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
}

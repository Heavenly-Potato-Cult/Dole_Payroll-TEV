<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollBatch;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Main dashboard view.
     *
     * The dashboard is role-aware: each role sees pending counts scoped
     * only to the queue they are responsible for acting on. Shared stats
     * (employee count, recent activity, charts) are visible to everyone.
     *
     * Roles and their queues:
     *   super_admin          → all pending items across all queues (view-only)
     *   payroll_officer      → draft/computed payroll batches
     *   hrmo                 → same as above + CA-track TEVs needing liquidation
     *   accountant           → payroll pending certification + submitted TEVs
     *   ard/chief_admin      → payroll pending RD approval + accountant-certified TEVs
     *   cashier              → RD-approved TEVs to release + filed liquidations to process
     *   budget_officer       → submitted TEVs (read-only reference, no action)
     */
    public function index()
    {
        $user = Auth::user();

        // ----------------------------------------------------------------
        // Shared context
        // Displayed on all dashboard variants regardless of role.
        // ----------------------------------------------------------------

        $totalEmployees = Employee::where('status', 'active')->count();
        $currentCutoff  = (now()->day <= 15) ? '1st' : '2nd';
        $currentMonth   = now()->format('F Y');



        // ----------------------------------------------------------------
        // Role-scoped pending counts
        //
        // Counts are intentionally narrow: each variable reflects only
        // what the current user can act on, not the full system state.
        // This drives the "Pending Approvals" badge on the stat card.
        // ----------------------------------------------------------------

        $pendingPayroll     = 0;
        $pendingTev         = 0;
        $pendingLiquidation = 0; // cashier-only: TEVs with filed liquidations to process



        // Determine pending payroll count based on user permissions
        if ($user->can(\App\SharedKernel\Enums\Permission::ADMINISTRATION_ACCESS->value)) {
            // Super admin sees everything across all queues (view-only context)
            $pendingPayroll = PayrollBatch::whereIn('status', ['draft', 'computed', 'pending_accountant', 'pending_rd'])->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::PAYROLL_CREATE->value)) {
            $pendingPayroll = PayrollBatch::whereIn('status', ['draft', 'computed'])->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::EMPLOYEES_MANAGE->value)) {
            $pendingPayroll = PayrollBatch::whereIn('status', ['draft', 'computed'])->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::PAYROLL_CERTIFY->value)) {
            $pendingPayroll = PayrollBatch::where('status', 'pending_accountant')->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::PAYROLL_APPROVE->value)) {
            $pendingPayroll = PayrollBatch::where('status', 'pending_rd')->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::PAYROLL_LOCK->value)) {
            // Cashier can see payroll batches for release processing
            $pendingPayroll = PayrollBatch::where('status', 'released')->count();
        } elseif ($user->can(\App\SharedKernel\Enums\Permission::TEV_ACCESS->value)) {
            // Budget officer has no approval action for payroll
            $pendingPayroll = 0;
        }

        // Single badge total shown on the dashboard stat card header
        $pendingApprovals = $pendingPayroll;



        // ----------------------------------------------------------------
        // Recent activity feeds
        // Latest 5 records for the dashboard tables. Role-based visibility
        // is handled in the Blade view, not here.
        // ----------------------------------------------------------------

        $recentPayroll = PayrollBatch::with('creator')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // ----------------------------------------------------------------
        // Chart datasets
        // Pre-aggregated for the dashboard charts. Keeping this in the
        // controller avoids raw queries leaking into Blade templates.
        // ----------------------------------------------------------------

        // Payroll pipeline distribution - ordered to match the workflow stages
        $statusOrder = ['draft', 'computed', 'pending_accountant', 'pending_rd', 'released', 'locked'];
        $rawCounts   = PayrollBatch::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Fill in zero for any status not yet present so the chart always
        // renders a complete pipeline, even on a fresh or sparse dataset
        $payrollStatusData = [];
        foreach ($statusOrder as $s) {
            $payrollStatusData[$s] = $rawCounts[$s] ?? 0;
        }

        return view('payroll::dashboard.index', compact(
            'totalEmployees',
            'currentCutoff',
            'currentMonth',
            'pendingApprovals',
            'pendingPayroll',
            'pendingTev',
            'pendingLiquidation',
            'recentPayroll',
            'payrollStatusData',
        ));
    }
}

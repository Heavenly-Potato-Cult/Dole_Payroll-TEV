<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\SpecialPayrollBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Special payroll module types, in the order they should display.
     * 'step_increment' is included for completeness (see SpecialPayrollBatch
     * docblock) but has no dedicated controller/route path today — NOSI
     * ("Notice of Step Increment") is what currently covers that workflow.
     * Any 'step_increment' rows will count correctly but won't get a link.
     */
    private const SPECIAL_TYPE_LABELS = [
        'newly_hired'         => 'Newly Hired',
        'salary_differential' => 'Salary Differential',
        'nosi'                => 'NOSI',
        'nosa'                => 'NOSA',
        'step_increment'      => 'Step Increment',
        'generic_special'     => 'Generic Special',
    ];

    /**
     * type -> named route for a single batch's "show" page.
     * NOTE: 'special-payroll.generic.*' routes are not currently registered
     * in web.php even though SpecialPayrollController has genericIndex/
     * genericShow/genericApprove methods. We guard with Route::has() below
     * so this doesn't throw until those routes are added.
     */
    private const SPECIAL_TYPE_SHOW_ROUTES = [
        'newly_hired'         => 'special-payroll.newly-hired.show',
        'salary_differential' => 'special-payroll.differential.show',
        'nosi'                => 'special-payroll.nosi-nosa.show',
        'nosa'                => 'special-payroll.nosi-nosa.show',
        'generic_special'     => 'special-payroll.generic.show',
    ];

    /**
     * type -> named route for the module's index/list page.
     */
    private const SPECIAL_TYPE_INDEX_ROUTES = [
        'newly_hired'         => 'special-payroll.newly-hired.index',
        'salary_differential' => 'special-payroll.differential.index',
        'nosi'                => 'special-payroll.nosi-nosa.index',
        'nosa'                => 'special-payroll.nosi-nosa.index',
        'generic_special'     => 'special-payroll.generic.index',
    ];

    /**
     * Main dashboard view.
     *
     * The dashboard is role-aware: each role sees pending counts scoped
     * only to the queue they are responsible for acting on. Shared stats
     * (employee count, recent activity, charts) are visible to everyone.
     *
     * Roles and their queues (regular payroll / special payroll):
     *   super_admin          → all pending items across all queues (view-only)
     *   payroll_officer      → draft/computed payroll batches / draft special batches
     *   hrmo                 → same as payroll_officer + CA-track TEVs needing liquidation
     *   accountant           → payroll pending certification + submitted TEVs
     *                          / draft special batches (accountant certifies draft→approved)
     *   ard/chief_admin      → payroll pending RD approval + accountant-certified TEVs
     *                          / approved special batches (ARD releases approved→released)
     *   cashier              → RD-approved TEVs to release + filed liquidations to process
     *                          (no special-payroll action queue today)
     *   budget_officer       → submitted TEVs (read-only reference, no action)
     *
     * Special payroll (SpecialPayrollBatch) only has draft → approved →
     * released — it does NOT share PayrollBatch's 6-state pipeline, so it
     * gets its own status-to-queue mapping per role rather than reusing
     * the regular-payroll one.
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
        // $pendingPayroll stays a scalar (regular payroll only) — several
        // existing Blade blocks already read it as an int and shouldn't
        // need to change. $pendingSpecialByType is new: a per-type
        // breakdown of special-payroll batches pending *this user's*
        // action, keyed the same way regardless of role.
        // ----------------------------------------------------------------

        $pendingPayroll       = 0;
        $pendingTev           = 0;
        $pendingLiquidation   = 0; // cashier-only: TEVs with filed liquidations to process
        $pendingSpecialByType = array_fill_keys(array_keys(self::SPECIAL_TYPE_LABELS), 0);



        if ($user->hasRole('super_admin')) {
            // Super admin sees everything across all queues (view-only context)
            $pendingPayroll       = PayrollBatch::whereIn('status', ['draft', 'computed', 'pending_accountant', 'pending_rd'])->count();
            $pendingSpecialByType = $this->specialCountsByType(['draft', 'approved']);

        } elseif ($user->hasRole('payroll_officer') || $user->hasAnyRole(['hrmo'])) {
            $pendingPayroll       = PayrollBatch::whereIn('status', ['draft', 'computed'])->count();
            $pendingSpecialByType = $this->specialCountsByType(['draft']);

        } elseif ($user->hasRole('accountant')) {
            $pendingPayroll       = PayrollBatch::where('status', 'pending_accountant')->count();
            // Accountant certifies special-payroll batches draft -> approved
            $pendingSpecialByType = $this->specialCountsByType(['draft']);

        } elseif ($user->hasAnyRole(['ard', 'chief_admin_officer'])) {
            $pendingPayroll       = PayrollBatch::where('status', 'pending_rd')->count();
            // ARD/Chief Admin releases special-payroll batches approved -> released
            $pendingSpecialByType = $this->specialCountsByType(['approved']);

        } elseif ($user->hasRole('cashier')) {
            // Cashier can see payroll batches for release processing.
            // No special-payroll action queue for this role today.
            $pendingPayroll = PayrollBatch::where('status', 'released')->count();

        } elseif ($user->hasRole('budget_officer')) {
            // Budget officer has no approval action for payroll or special payroll
            $pendingPayroll = 0;
        }

        $pendingSpecialTotal = array_sum($pendingSpecialByType);

        // Single badge total shown on the dashboard stat card header
        $pendingApprovals = $pendingPayroll + $pendingSpecialTotal;



        // ----------------------------------------------------------------
        // Special payroll totals (all statuses, per type)
        // Reference numbers for module-breakdown tables — distinct from
        // $pendingSpecialByType, which is scoped to the current user's queue.
        // ----------------------------------------------------------------

        $specialTotalsByType = array_merge(
            array_fill_keys(array_keys(self::SPECIAL_TYPE_LABELS), 0),
            SpecialPayrollBatch::selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray()
        );



        // ----------------------------------------------------------------
        // Recent activity feeds
        // Latest records for the dashboard tables. Role-based visibility
        // is handled in the Blade view, not here.
        //
        // $recentPayroll is kept as-is (existing Blade blocks depend on its
        // PayrollBatch-specific columns). $recentActivity is new: a
        // normalized, merged feed across both models for any view that
        // wants a single unified list instead of two separate tables.
        // ----------------------------------------------------------------

        $recentPayroll = PayrollBatch::with('creator')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentSpecial = SpecialPayrollBatch::with('employee')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentActivity = $this->buildRecentActivityFeed($recentPayroll, $recentSpecial);

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

        // Special payroll pipeline distribution - separate 3-stage chart,
        // since SpecialPayrollBatch's status set doesn't map onto the
        // regular-payroll one (see class docblock above).
        $specialStatusOrder = ['draft', 'approved', 'released'];
        $rawSpecialCounts   = SpecialPayrollBatch::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $specialStatusData = [];
        foreach ($specialStatusOrder as $s) {
            $specialStatusData[$s] = $rawSpecialCounts[$s] ?? 0;
        }

        // Display labels for special-payroll types, passed through so the
        // Blade view never has to reach into the controller's constants.
        $specialTypeLabels      = self::SPECIAL_TYPE_LABELS;
        $specialTypeIndexRoutes = self::SPECIAL_TYPE_INDEX_ROUTES;

        return view('payroll::dashboard.index', compact(
            'totalEmployees',
            'currentCutoff',
            'currentMonth',
            'pendingApprovals',
            'pendingPayroll',
            'pendingTev',
            'pendingLiquidation',
            'pendingSpecialByType',
            'pendingSpecialTotal',
            'specialTotalsByType',
            'specialTypeLabels',
            'specialTypeIndexRoutes',
            'recentPayroll',
            'recentActivity',
            'payrollStatusData',
            'specialStatusData',
        ));
    }

    /**
     * Count SpecialPayrollBatch rows in the given statuses, grouped by type,
     * with every known type present (zero-filled) so callers never need to
     * guard against a missing array key.
     */
    private function specialCountsByType(array $statuses): array
    {
        $counts = SpecialPayrollBatch::whereIn('status', $statuses)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return array_merge(array_fill_keys(array_keys(self::SPECIAL_TYPE_LABELS), 0), $counts);
    }

    /**
     * Merge PayrollBatch and SpecialPayrollBatch recent records into one
     * normalized, chronologically sorted feed. The two models have
     * different columns (period_month/period_year/cutoff vs.
     * type/title/year/month/effectivity_date), so rather than forcing a
     * single Eloquent collection, each row is flattened into a common
     * shape the Blade view can render with one @foreach.
     */
    private function buildRecentActivityFeed($recentPayroll, $recentSpecial): array
    {
        $items = [];

        foreach ($recentPayroll as $batch) {
            $items[] = [
                'module'     => 'regular',
                'title'      => Carbon::create()->month($batch->period_month)->format('M') . ' ' . $batch->period_year,
                'subtitle'   => ($batch->cutoff === '1st' ? '1st (1–15)' : '2nd (16–end)')
                                . ($batch->creator ? ' — ' . $batch->creator->name : ''),
                'status'     => $batch->status,
                'route'      => route('payroll.show', $batch->id),
                'created_at' => $batch->created_at,
            ];
        }

        foreach ($recentSpecial as $batch) {
            $showRoute = self::SPECIAL_TYPE_SHOW_ROUTES[$batch->type] ?? null;

            $items[] = [
                'module'     => $batch->type,
                'title'      => self::SPECIAL_TYPE_LABELS[$batch->type] ?? ucwords(str_replace('_', ' ', $batch->type)),
                'subtitle'   => $batch->employee
                                    ? $batch->employee->last_name . ', ' . $batch->employee->first_name
                                    : ($batch->title ?? '—'),
                'status'     => $batch->status,
                // Guarded: 'special-payroll.generic.*' routes aren't
                // registered in web.php yet, so this stays null (no link)
                // until that's added, instead of throwing.
                'route'      => ($showRoute && Route::has($showRoute))
                                    ? route($showRoute, $batch->id)
                                    : null,
                'created_at' => $batch->created_at,
            ];
        }

        usort($items, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return array_slice($items, 0, 8);
    }
}

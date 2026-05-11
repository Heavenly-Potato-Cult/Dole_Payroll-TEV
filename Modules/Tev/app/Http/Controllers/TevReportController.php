<?php

namespace Modules\Tev\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Tev\Exports\TevRegisterExport;
use App\SharedKernel\Models\Employee;
use Modules\Tev\Models\TevRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class TevReportController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  TEV — Itinerary of Travel (Appendix A)
    //  GET /reports/tev/{tevRequest}/itinerary
    // ─────────────────────────────────────────────────────────────────────────
    public function tevItinerary(int $tevRequest)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'cashier', 'chief_admin_officer']);

        $tev = TevRequest::with([
            'itineraryLines',
            'employee.division',
            'officeOrder',
            'certification',
        ])->findOrFail($tevRequest);

        return view('tev::reports.tev-itinerary', compact('tev'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TEV — Certification of Travel Completed
    //  GET /reports/tev/{tevRequest}/travel-completed
    // ─────────────────────────────────────────────────────────────────────────
    public function tevTravelCompleted(int $tevRequest)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'cashier', 'chief_admin_officer']);

        $tev = TevRequest::with([
            'itineraryLines',
            'employee.division',
            'officeOrder',
            'certification',
        ])->findOrFail($tevRequest);

        return view('tev::reports.tev-travel-completed', compact('tev'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TEV — Annex A: Expenses Not Requiring Receipts
    //  GET /reports/tev/{tevRequest}/annex-a
    // ─────────────────────────────────────────────────────────────────────────
    public function tevAnnexA(int $tevRequest)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'cashier', 'chief_admin_officer']);

        $tev = TevRequest::with([
            'itineraryLines',
            'employee.division',
            'officeOrder',
            'certification',
        ])->findOrFail($tevRequest);

        return view('tev::reports.tev-annex-a', compact('tev'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TEV — Liquidation / Disbursement Voucher  (printable HTML page)
    //  GET /reports/tev/{tevRequest}/liquidation-dv
    // ─────────────────────────────────────────────────────────────────────────
    public function tevLiquidationDv(int $tevRequest)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'cashier', 'chief_admin_officer']);

        $tev = TevRequest::with([
            'itineraryLines',
            'employee.division',
            'officeOrder',
            'certification',
            'approvalLogs' => fn($q) => $q->with('user')->orderBy('performed_at'),
        ])->findOrFail($tevRequest);

        if ($tev->track !== 'cash_advance') {
            abort(404, 'Liquidation DV is only available for Cash Advance TEVs.');
        }

        if (!in_array($tev->status, ['liquidation_filed', 'liquidated'])) {
            abort(404, 'Liquidation has not been filed for this TEV yet.');
        }

        return view('tev::reports.tev-liquidation-dv', compact('tev'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TEV Register (HTML)
    //  GET /reports/tev-register
    // ─────────────────────────────────────────────────────────────────────────
    public function tevRegister(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = TevRequest::with(['employee.division', 'officeOrder'])
            ->orderByDesc('travel_date_start');

        if ($request->filled('year')) {
            $query->whereYear('travel_date_start', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('travel_date_start', $request->month);
        }
        if ($request->filled('track')) {
            $query->where('track', $request->track);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $tevRequests = $query->paginate(30)->withQueryString();
        $grandTotal  = $query->getQuery()->clone()->sum('grand_total');
        $employees   = Employee::orderBy('last_name')->get(['id', 'last_name', 'first_name']);
        $currentYear = now()->year;
        $filters     = $request->only(['year', 'month', 'track', 'status', 'employee_id']);

        return view('tev::reports.tev-register', compact(
            'tevRequests', 'grandTotal', 'employees', 'currentYear', 'filters'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TEV Register Excel Export
    //  GET /reports/tev-register/export
    // ─────────────────────────────────────────────────────────────────────────
    public function tevRegisterExport(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $filters = $request->only(['year', 'month', 'track', 'status', 'employee_id']);

        return Excel::download(
            new TevRegisterExport($filters),
            'TEV-Register-' . now()->format('Ymd') . '.xlsx'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Employee TEV History
    //  GET /employees/{employee}/tev-history
    // ─────────────────────────────────────────────────────────────────────────
    public function employeeTevHistory(Request $request, int $employee)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $emp = Employee::with('division')->findOrFail($employee);

        $tevRequests = TevRequest::with('officeOrder')
            ->where('employee_id', $emp->id)
            ->orderByDesc('travel_date_start')
            ->paginate(20)
            ->withQueryString();

        return view('tev::reports.tev-history', compact('emp', 'tevRequests'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────
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
}

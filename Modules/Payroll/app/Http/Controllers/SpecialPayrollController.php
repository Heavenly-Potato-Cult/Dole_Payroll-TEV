<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\StoreSpecialPayrollRequest;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollAuditLog;
use Modules\Payroll\Models\SpecialPayrollBatch;
use Modules\Payroll\Services\NewlyHiredPayrollService;
use Modules\Payroll\Services\SalaryDifferentialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->authorize('viewAny', SpecialPayrollBatch::class);

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
        $this->authorize('create', SpecialPayrollBatch::class);

        $sixMonthsAgo = now()->subMonths(6);

        $employees = Employee::where('status', 'active')
            ->where(function($query) use ($sixMonthsAgo) {
                $query->where('hire_date', '>=', $sixMonthsAgo)
                      ->orWhereNull('hire_date');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name',
                   'position_title', 'basic_salary', 'pera']);

        return view('payroll::special-payroll.newly-hired-create', compact('employees'));
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

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $request->effectivity_date,
            cutoff_start:      $request->cutoff_start,
            cutoff_end:        $request->cutoff_end,
            lwop_days:         (int) ($request->lwop_days ?? 0),
            tardiness_minutes: 0
        );

        $cutoffStart = Carbon::parse($request->cutoff_start);
        $effectivity = Carbon::parse($request->effectivity_date);
        $payrollType = $request->payroll_type;

        $typeLabel = $payrollType === 'transferee' ? 'Transferee' : 'Newly Hired';
        $title = 'Pro-Rated Payroll — ' . $typeLabel . ' — '
            . $employee->last_name . ', ' . $employee->first_name
            . ' (' . $effectivity->format('M d, Y') . ')';

        $batch = SpecialPayrollBatch::create([
            'type'              => $payrollType,
            'title'             => $title,
            'year'              => $cutoffStart->year,
            'month'             => $cutoffStart->month,
            'effectivity_date'  => $request->effectivity_date,
            'period_start'      => $request->cutoff_start,
            'period_end'        => $request->cutoff_end,
            'employee_id'       => $employee->id,
            'pro_rated_days'    => $result['working_days'],
            'gross_amount'      => $result['net_earned'],
            'deductions_amount' => $result['total_deductions'],
            'net_amount'        => $result['net_amount'],
            'status'            => 'draft',
            'remarks'           => $request->remarks,
        ]);

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
        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'newly_hired')
            ->findOrFail($id);
        $this->authorize('view', $batch);

        $employee = $batch->employee;

        /** @var NewlyHiredPayrollService $service */
        $service = app(NewlyHiredPayrollService::class);

        $result = $service->compute(
            employee:          $employee,
            effectivity_date:  $batch->effectivity_date->toDateString(),
            cutoff_start:      $batch->period_start->toDateString(),
            cutoff_end:        $batch->period_end->toDateString(),
            lwop_days:         0,
            tardiness_minutes: 0
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
            $this->authorize('certify', $batch);
            $new    = 'approved';
            $action = 'Approved Newly Hired Payroll';
        } elseif ($batch->status === 'approved') {
            $this->authorize('approve', $batch);
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
        $batch = SpecialPayrollBatch::where('type', 'newly_hired')
            ->where('status', 'draft')
            ->findOrFail($id);
        $this->authorize('delete', $batch);

        $batch->delete();

        return redirect()->route('special-payroll.newly-hired.index')
            ->with('success', 'Payroll record deleted.');
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
        $this->authorize('viewAny', SpecialPayrollBatch::class);

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
        $this->authorize('create', SpecialPayrollBatch::class);

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
        $this->authorize('create', SpecialPayrollBatch::class);

        $request->validate([
            'employee_id'          => ['required', 'integer', 'exists:employees,id'],
            'effectivity_date_from'=> ['required', 'date'],
            'effectivity_date_to'  => ['required', 'date', 'after_or_equal:effectivity_date_from'],
            'old_salary'           => ['required', 'numeric', 'min:0'],
            'new_salary'           => ['required', 'numeric', 'gt:old_salary'],
            'remarks'              => ['nullable', 'string', 'max:1000'],
        ], [
            'new_salary.gt' => 'New salary must be greater than the old salary.',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $request->effectivity_date_from,
            effectivity_date_to:   $request->effectivity_date_to,
            old_salary:            (float) $request->old_salary,
            new_salary:            (float) $request->new_salary,
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
            'gross_amount'        => $result['total_earned'],
            'deductions_amount'   => $result['total_deductions'],
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
        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->where('type', 'salary_differential')
            ->findOrFail($id);
        $this->authorize('view', $batch);

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
            $this->authorize('certify', $batch);
            $new    = 'approved';
            $action = 'Approved Salary Differential';
        } elseif ($batch->status === 'approved') {
            $this->authorize('approve', $batch);
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
        $batch = SpecialPayrollBatch::where('type', 'salary_differential')
            ->where('status', 'draft')
            ->findOrFail($id);
        $this->authorize('delete', $batch);

        $batch->delete();

        return redirect()->route('special-payroll.differential.index')
            ->with('success', 'Record deleted.');
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
        $this->authorize('viewAny', SpecialPayrollBatch::class);

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
        $this->authorize('create', SpecialPayrollBatch::class);

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
        $this->authorize('create', SpecialPayrollBatch::class);

        $request->validate([
            'type'                 => ['required', 'in:nosi,nosa'],
            'employee_id'          => ['required', 'integer', 'exists:employees,id'],
            'effectivity_date_from'=> ['required', 'date'],
            'effectivity_date_to'  => ['required', 'date', 'after_or_equal:effectivity_date_from'],
            'old_salary'           => ['required', 'numeric', 'min:0'],
            'new_salary'           => ['required', 'numeric', 'gt:old_salary'],
            'remarks'              => ['nullable', 'string', 'max:1000'],
        ], [
            'new_salary.gt' => 'New salary must be greater than the old salary.',
            'type.in'       => 'Type must be either NOSI or NOSA.',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $request->effectivity_date_from,
            effectivity_date_to:   $request->effectivity_date_to,
            old_salary:            (float) $request->old_salary,
            new_salary:            (float) $request->new_salary,
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
            'gross_amount'        => $result['total_earned'],
            'deductions_amount'   => $result['total_deductions'],
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
        $batch = SpecialPayrollBatch::with('employee', 'approver')
            ->whereIn('type', ['nosi', 'nosa'])
            ->findOrFail($id);
        $this->authorize('view', $batch);

        $employee = $batch->employee;

        /** @var SalaryDifferentialService $service */
        $service = app(SalaryDifferentialService::class);

        $result = $service->compute(
            employee:              $employee,
            effectivity_date_from: $batch->period_start->toDateString(),
            effectivity_date_to:   $batch->period_end->toDateString(),
            old_salary:            (float) $batch->old_basic_salary,
            new_salary:            (float) $batch->new_basic_salary,
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
            $this->authorize('certify', $batch);
            $new    = 'approved';
            $action = 'Approved ' . $typeLabel;
        } elseif ($batch->status === 'approved') {
            $this->authorize('approve', $batch);
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
        $batch = SpecialPayrollBatch::whereIn('type', ['nosi', 'nosa'])
            ->where('status', 'draft')
            ->findOrFail($id);
        $this->authorize('delete', $batch);

        $batch->delete();

        return redirect()->route('special-payroll.nosi-nosa.index')
            ->with('success', 'Record deleted.');
    }

    // =====================================================================
    //  Private helpers
    // =====================================================================
}

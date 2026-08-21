<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\StoreEmployeeRequest;
use Modules\Payroll\Http\Requests\UpdateEmployeeRequest;
use App\SharedKernel\Models\Division;
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Services\HrisApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Payroll\Models\Allowances\EmployeeAllowance;
use Modules\Payroll\Services\AllowanceService;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->input('search');
        $divisionId = $request->input('division_id');
        $status     = $request->input('status');

        $employees = Employee::query()
            ->with('division')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('last_name',          'like', "%{$search}%")
                       ->orWhere('first_name',       'like', "%{$search}%")
                       ->orWhere('employee_no',      'like', "%{$search}%")
                       ->orWhere('plantilla_item_no', 'like', "%{$search}%")
                       ->orWhere('position_title',   'like', "%{$search}%");
                });
            })
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->when($status,     fn ($q) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $divisions = Division::orderBy('name')->get(['id', 'name', 'code']);

        return view('payroll::employees.index', compact('employees', 'divisions', 'search', 'divisionId', 'status'));
    }

    public function create()
    {
        $divisions  = Division::orderBy('name')->get(['id', 'name', 'code']);
        $sitYears   = [2022, 2021]; // latest first
        $latestYear = 2022;

        return view('payroll::employees.create', compact('divisions', 'sitYears', 'latestYear'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        // Salary arrives formatted ("12,345.00") - strip commas before persisting
        $data = $request->validated();
        $data['basic_salary'] = str_replace(',', '', $data['basic_salary']);
        // Empty string (field left blank) means "no override" — must be
        // null, not '', or it would fail the decimal column / read back
        // as a truthy override in computeCutoffSplit().
        $data['salary_split_override_pct'] = ($data['salary_split_override_pct'] ?? '') !== ''
            ? $data['salary_split_override_pct']
            : null;

        Employee::create($data);

        return redirect()->route('employees.index')
            ->with('success', 'Employee record created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['division', 'promotionHistory', 'deductions']);

        $peraInfo = $this->resolvedPeraInfo($employee);

        return view('payroll::employees.show', compact('employee', 'peraInfo'));
    }

    public function edit(Employee $employee)
    {
        $divisions = Division::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $sitYears  = [2022, 2021];

        $peraInfo = $this->resolvedPeraInfo($employee);

        return view('payroll::employees.edit', compact('employee', 'divisions', 'sitYears', 'peraInfo'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        // Same salary sanitization as store()
        $data = $request->validated();
        $data['basic_salary'] = str_replace(',', '', $data['basic_salary']);
        $data['salary_split_override_pct'] = ($data['salary_split_override_pct'] ?? '') !== ''
            ? $data['salary_split_override_pct']
            : null;

        // PERA is no longer editable from this form (2026-08-19) — edit.blade.php
        // now shows it read-only and links to the Allowance module instead.
        // Editing the raw employee.pera column here was a silent no-op for
        // any employee who already has a standing PERA enrollment (payroll
        // resolves PERA via AllowanceService, which ignores this column once
        // a standing enrollment exists), with no indication to the person
        // editing that their change had no effect. Stripped defensively here
        // too, in case UpdateEmployeeRequest still lets it through.
        unset($data['pera']);

        $employee->update($data);

        return redirect()->route('employees.index')
            ->with('success', 'Employee record updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        $employee->delete(); // soft delete — record is recoverable

        return redirect()->route('employees.index')
            ->with('success', "Employee \"{$name}\" removed from the active plantilla.");
    }

    /**
     * Pull and sync employee records from the HRIS API.
     *
     * Match strategy:
     *   - Division is resolved by `division_code` from the API payload.
     *     Employees without a matching local division are skipped entirely.
     *   - Existing employees are matched by `employee_no` only.
     *     On update, `plantilla_item_no` is intentionally left untouched
     *     to preserve any manual corrections made locally.
     *   - New employees get `hire_date` defaulted to their original
     *     appointment date, falling back to today if absent.
     */
    public function pullFromApi(Request $request)
    {
        try {
            $employees = app(HrisApiService::class)->fetchEmployees();

            Log::info('HRIS sync starting', [
                'total_from_api' => count($employees),
                'current_db_count' => Employee::withTrashed()->count(),
            ]);

            $synced          = 0;
            $updated         = 0;
            $skippedDivision = 0;
            $divisionsCreated = 0;
            $processed       = 0;

            foreach ($employees as $index => $empData) {
                $processed++;
                // Resolve division — auto-create if no local match exists
                $divisionCode = $empData['division_code'] ?? null;
                $divisionName = $empData['division_name'] ?? null;
                $division = Division::where('code', $divisionCode)->first();

                if (! $division) {
                    // Auto-create division from HRIS data
                    if ($divisionCode && $divisionName) {
                        try {
                            $division = Division::create([
                                'name'        => $divisionName,
                                'code'        => $divisionCode,
                                'description' => "Synced from HRIS",
                                'is_active'   => true,
                            ]);
                            $divisionsCreated++;
                            Log::info('Auto-created division from HRIS', [
                                'division_code' => $divisionCode,
                                'division_name' => $divisionName,
                                'division_id' => $division->id,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to auto-create division', [
                                'division_code' => $divisionCode,
                                'division_name' => $divisionName,
                                'error' => $e->getMessage(),
                            ]);
                            $skippedDivision++;
                            continue;
                        }
                    } else {
                        Log::warning('Skipping employee: missing division data', [
                            'employee_no'   => $empData['employee_id'],
                            'employee_name' => $empData['first_name'] . ' ' . $empData['last_name'],
                            'division_code' => $divisionCode,
                            'division_name' => $divisionName,
                        ]);
                        $skippedDivision++;
                        continue;
                    }
                }

                // Map API field names → local database columns
                $dbData = [
                    'division_id'               => $division->id,
                    'hris_employee_id'          => $empData['employee_id']               ?? null, // Store original HRIS employee_id for reference
                    'last_name'                 => $empData['last_name'],
                    'first_name'                => $empData['first_name'],
                    'middle_name'               => $empData['middle_name']               ?? null,
                    'position_title'            => $empData['position_title'],
                    'plantilla_item_no'         => $empData['plantilla_item_no'],
                    'salary_grade'              => $empData['salary_grade'],
                    'step'                      => $empData['step'],
                    'basic_salary'              => $empData['basic_monthly_salary'],
                    'employment_status'         => $empData['employment_status']         ?? 'permanent',
                    'official_station'          => $empData['official_station']          ?? null,
                    'hire_date'                 => $empData['date_original_appointment'] ?? now(),
                    'original_appointment_date' => $empData['date_original_appointment'] ?? null,
                    'last_promotion_date'       => $empData['last_promotion_date']       ?? null,
                    'gsis_bp_no'                => $empData['gsis_bp_no']                ?? null,
                    'gsis_crn'                  => $empData['gsis_crn']                  ?? null,
                    'pagibig_no'                => $empData['pagibig_mid_no']            ?? null,
                    'pagibig_mid_no'            => $empData['pagibig_mid_no']            ?? null,
                    'pagibig_id'                => $empData['pagibig_id']                ?? ($empData['pagibig_mid_no'] ?? null),
                    'philhealth_no'             => $empData['philhealth_no']             ?? null,
                    'tin'                       => $empData['tin']                       ?? null,
                    'status'                    => 'active',
                ];

                // Match by hris_employee_id - this is the unique identifier from HRIS
                // Include soft-deleted records to restore them during sync
                $existing = Employee::withTrashed()
                    ->where('hris_employee_id', $dbData['hris_employee_id'])
                    ->first();

                if ($existing) {
                    Log::info('Updating existing employee', [
                        'hris_employee_id' => $dbData['hris_employee_id'],
                        'existing_id' => $existing->id,
                    ]);

                    // Restore if soft-deleted
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    // Generate employee_no only if it's currently null
                    if (is_null($existing->employee_no) && !empty($dbData['hris_employee_id'])) {
                        // Extract numeric part from HRIS employee_id (e.g., "EMP001" → "1", "1" → "1")
                        $numericId = preg_replace('/[^0-9]/', '', $dbData['hris_employee_id']);
                        $dbData['employee_no'] = 'EMP-' . str_pad($numericId, 4, '0', STR_PAD_LEFT);
                        Log::info('Generated employee_no for existing employee', [
                            'hris_employee_id' => $dbData['hris_employee_id'],
                            'employee_no' => $dbData['employee_no'],
                        ]);
                    } else {
                        // Preserve existing employee_no
                        unset($dbData['employee_no']);
                    }

                    // Preserve locally managed plantilla_item_no on updates only if it already has a value
                    // If it's NULL, allow the HRIS API value to be set
                    if (!is_null($existing->plantilla_item_no)) {
                        unset($dbData['plantilla_item_no']);
                    }
                    $existing->update($dbData);
                    $updated++;
                } else {
                    Log::info('Creating new employee', [
                        'hris_employee_id' => $dbData['hris_employee_id'],
                        'plantilla' => $dbData['plantilla_item_no'],
                    ]);

                    // Generate employee_no for new employees
                    if (!empty($dbData['hris_employee_id'])) {
                        // Extract numeric part from HRIS employee_id (e.g., "EMP001" → "1", "1" → "1")
                        $numericId = preg_replace('/[^0-9]/', '', $dbData['hris_employee_id']);
                        $dbData['employee_no'] = 'EMP-' . str_pad($numericId, 4, '0', STR_PAD_LEFT);
                        Log::info('Generated employee_no for new employee', [
                            'hris_employee_id' => $dbData['hris_employee_id'],
                            'employee_no' => $dbData['employee_no'],
                        ]);
                    }

                    try {
                        $newEmployee = Employee::create($dbData);
                        Log::info('Successfully created employee', [
                            'hris_employee_id' => $dbData['hris_employee_id'],
                            'employee_no' => $dbData['employee_no'],
                            'new_id' => $newEmployee->id,
                        ]);
                        $synced++;
                    } catch (\Exception $e) {
                        Log::error('Failed to create employee', [
                            'hris_employee_id' => $dbData['hris_employee_id'],
                            'error' => $e->getMessage(),
                            'dbData' => $dbData,
                        ]);
                    }
                }
            }

            Log::info('HRIS sync completed', [
                'processed'          => $processed,
                'synced'             => $synced,
                'updated'            => $updated,
                'skipped_division'   => $skippedDivision,
                'divisions_created'  => $divisionsCreated,
                'final_db_count'     => Employee::withTrashed()->count(),
            ]);

            $message = "Synced {$synced} new and updated {$updated} existing employees from HRIS.";
            if ($divisionsCreated > 0) {
                $message .= " Created {$divisionsCreated} new divisions.";
            }

            return redirect()->route('employees.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('HRIS sync failed', ['error' => $e->getMessage()]);

            return redirect()->route('employees.index')
                ->with('error', 'Failed to sync from HRIS: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Deductions — stub until Phase 2
    // Full module (types, amounts, effectivity dates) is out of scope
    // for the current release. Routes are wired so views don't 404.
    // ----------------------------------------------------------------

    public function deductions(Employee $employee)
    {
        $employee->load('deductions');

        return view('payroll::employees.deductions', compact('employee'));
    }

    public function updateDeductions(Request $request, Employee $employee)
    {
        return redirect()->route('employees.show', $employee)
            ->with('success', 'Deductions updated.');
    }

    /**
     * Toggle employee exclusion from payroll processing.
     *
     * HR can exclude employees from payroll computation without deleting them.
     * This is useful for employees on leave, suspended, or other special cases.
     */
    public function toggleExclusion(Request $request, Employee $employee)
    {
        $request->validate([
            'is_excluded' => ['required', 'boolean'],
        ]);

        $employee->update([
            'is_excluded' => $request->is_excluded,
        ]);

        return response()->json([
            'success' => true,
            'message' => $employee->full_name . ' has been ' . ($request->is_excluded ? 'excluded from' : 'included in') . ' payroll processing.',
        ]);
    }

    /**
     * Resolve the employee's current PERA the same way payroll actually
     * computes it — via AllowanceService::resolveForPeriod() — rather than
     * the raw employee.pera column, plus whether that figure is coming from
     * a standing Allowance-module enrollment or the legacy employee.pera
     * fallback. Used by show()/edit() so the Employee profile screens
     * display what's actually in effect for payroll, instead of a column
     * that can silently have no bearing on it once a standing enrollment
     * exists (see 2026-08-19 update() change).
     *
     * @return array{amount: float, from_standing_enrollment: bool}
     */
    private function resolvedPeraInfo(Employee $employee): array
    {
        $today = now();

        $hasStandingPera = EmployeeAllowance::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereHas('allowanceType', fn ($q) => $q->where('code', 'PERA'))
            ->where('effectivity_date', '<=', $today->toDateString())
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', $today->toDateString());
            })
            ->exists();

        /** @var AllowanceService $allowanceService */
        $allowanceService = app(AllowanceService::class);

        $resolvedLines = $allowanceService->resolveForPeriod(
            $employee,
            $today->year,
            $today->month,
            $today->copy()->startOfDay(),
            $today->copy()->startOfDay()
        );

        $peraLine = collect($resolvedLines)->firstWhere('code', 'PERA');

        return [
            'amount'                   => $peraLine['amount'] ?? (float) $employee->pera,
            'from_standing_enrollment' => $hasStandingPera,
        ];
    }
}

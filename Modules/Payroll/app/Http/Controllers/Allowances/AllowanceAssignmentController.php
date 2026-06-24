<?php

namespace Modules\Payroll\Http\Controllers\Allowances;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\Allowances\AllowanceAssignment;
use Modules\Payroll\Models\Allowances\AllowanceAssignmentEntry;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Modules\Payroll\Models\Allowances\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AllowanceAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = AllowanceAssignment::with('creator')
            ->withCount('entries')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::allowances.assignments.index', compact('assignments', 'currentYear'));
    }

    public function create()
    {
        $types     = AllowanceType::where('is_active', true)->orderBy('display_order')->get();
        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        $standingPairs = $this->getStandingPairs();

        return view('payroll::allowances.assignments.create', compact('types', 'employees', 'standingPairs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month'  => ['required', 'integer', 'min:1', 'max:12'],
            'cutoff'        => ['required', 'string', 'in:1st,2nd,monthly'],
            'period_start'  => ['required', 'date'],
            'period_end'    => ['nullable', 'date', 'after_or_equal:period_start'],
            'remarks'       => ['nullable', 'string'],
            'entries'       => [
                'required', 'array', 'min:1',
                $this->noDuplicateEntriesRule(),
                $this->noCrossBatchDuplicateRule($request),
            ],
            'entries.*.employee_id'       => ['required', 'exists:employees,id'],
            'entries.*.allowance_type_id' => ['required', 'exists:allowance_types,id'],
            'entries.*.amount'            => ['required', 'numeric', 'min:0'],
            'entries.*.remarks'           => ['nullable', 'string'],
        ]);

        $assignment = DB::transaction(function () use ($validated) {
            $assignment = AllowanceAssignment::create([
                'period_year'  => $validated['period_year'],
                'period_month' => $validated['period_month'],
                'cutoff'       => $validated['cutoff'],
                'period_start' => $validated['period_start'],
                'period_end'   => $validated['period_end'] ?? null,
                'status'       => 'draft',
                'created_by'   => Auth::id(),
                'prepared_at'  => now(),
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['entries'] as $row) {
                $amount = round((float) $row['amount'], 2);
                AllowanceAssignmentEntry::create([
                    'allowance_assignment_id' => $assignment->id,
                    'employee_id'        => $row['employee_id'],
                    'allowance_type_id'  => $row['allowance_type_id'],
                    'amount'             => $amount,
                    'gross_amount'       => $amount,
                    'net_amount'         => $amount,
                    'remarks'            => $row['remarks'] ?? null,
                ]);
            }

            return $assignment;
        });

        return redirect()->route('payroll.allowances.assignments.show', $assignment)
            ->with('success', 'Allowance assignment created.');
    }

    public function show(AllowanceAssignment $assignment)
    {
        $assignment->load(['entries.employee', 'entries.allowanceType', 'creator']);

        return view('payroll::allowances.assignments.show', compact('assignment'));
    }

    public function edit(AllowanceAssignment $assignment)
    {
        if (! in_array($assignment->status, ['draft'], true)) {
            return redirect()->route('payroll.allowances.assignments.show', $assignment)
                ->with('error', 'Only draft assignments can be edited.');
        }

        $assignment->load(['entries.employee', 'entries.allowanceType']);
        $types     = AllowanceType::where('is_active', true)->orderBy('display_order')->get();
        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        $standingPairs = $this->getStandingPairs();

        return view('payroll::allowances.assignments.edit', compact('assignment', 'types', 'employees', 'standingPairs'));
    }

    public function update(Request $request, AllowanceAssignment $assignment)
    {
        if ($assignment->status !== 'draft') {
            return redirect()->route('payroll.allowances.assignments.show', $assignment)
                ->with('error', 'Only draft assignments can be edited.');
        }

        $validated = $request->validate([
            'period_year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month'  => ['required', 'integer', 'min:1', 'max:12'],
            'cutoff'        => ['required', 'string', 'in:1st,2nd,monthly'],
            'period_start'  => ['required', 'date'],
            'period_end'    => ['nullable', 'date', 'after_or_equal:period_start'],
            'remarks'       => ['nullable', 'string'],
            'entries'       => [
                'required', 'array', 'min:1',
                $this->noDuplicateEntriesRule(),
                $this->noCrossBatchDuplicateRule($request, $assignment->id),
            ],
            'entries.*.employee_id'       => ['required', 'exists:employees,id'],
            'entries.*.allowance_type_id' => ['required', 'exists:allowance_types,id'],
            'entries.*.amount'            => ['required', 'numeric', 'min:0'],
            'entries.*.remarks'           => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->update([
                'period_year'  => $validated['period_year'],
                'period_month' => $validated['period_month'],
                'cutoff'       => $validated['cutoff'],
                'period_start' => $validated['period_start'],
                'period_end'   => $validated['period_end'] ?? null,
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            $assignment->entries()->delete();

            foreach ($validated['entries'] as $row) {
                $amount = round((float) $row['amount'], 2);
                AllowanceAssignmentEntry::create([
                    'allowance_assignment_id' => $assignment->id,
                    'employee_id'        => $row['employee_id'],
                    'allowance_type_id'  => $row['allowance_type_id'],
                    'amount'             => $amount,
                    'gross_amount'       => $amount,
                    'net_amount'         => $amount,
                    'remarks'            => $row['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('payroll.allowances.assignments.show', $assignment)
            ->with('success', 'Allowance assignment updated.');
    }

    public function advance(Request $request, AllowanceAssignment $assignment)
    {
        $action = $request->validate(['action' => 'required|in:release'])['action'];

        if ($action === 'release' && $assignment->status === 'draft') {
            $assignment->update([
                'status' => 'released',
                'released_by' => Auth::id(),
                'released_at' => now(),
            ]);
            return back()->with('success', 'Allowance assignment released.');
        }

        return back()->with('error', 'Invalid status transition.');
    }

    public function destroy(AllowanceAssignment $assignment)
    {
        if ($assignment->status !== 'draft') {
            return back()->with('error', 'Only draft assignments can be deleted.');
        }

        $assignment->delete();

        return redirect()->route('payroll.allowances.index')
            ->with('success', 'Allowance assignment deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return a flat array of "employee_id-allowance_type_id" strings for every
     * active standing allowance record.  Used by the create/edit views so the
     * bulk-add button can skip employees who are already covered by a standing
     * (recurring) allowance for the chosen type.
     *
     * @return array<int, string>
     */
    private function getStandingPairs(): array
    {
        return EmployeeAllowance::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->get(['employee_id', 'allowance_type_id'])
            ->map(fn ($r) => $r->employee_id . '-' . $r->allowance_type_id)
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Validation rules
    // -------------------------------------------------------------------------

    /**
     * Rejects entries arrays that contain more than one row for the same
     * employee_id + allowance_type_id pair within this batch.
     */
    private function noDuplicateEntriesRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $seen = [];

            foreach ((array) $value as $row) {
                $employeeId = $row['employee_id'] ?? null;
                $typeId     = $row['allowance_type_id'] ?? null;

                if (! $employeeId || ! $typeId) {
                    continue;
                }

                $key = $employeeId . '-' . $typeId;

                if (isset($seen[$key])) {
                    $fail('Each employee can only have one entry per allowance type in a batch. Please remove duplicate rows.');

                    return;
                }

                $seen[$key] = true;
            }
        };
    }

    /**
     * Rejects entries that contain an employee_id + allowance_type_id pair
     * already present in *another* batch for the same period_year + period_month
     * + cutoff combination.
     *
     * This prevents the "two RATA batches for the same month, same 82
     * employees" scenario that the within-batch duplicate rule alone cannot
     * catch.
     */
    private function noCrossBatchDuplicateRule(Request $request, ?int $excludeBatchId = null): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($request, $excludeBatchId) {
            $periodYear  = $request->input('period_year');
            $periodMonth = $request->input('period_month');
            $cutoff      = $request->input('cutoff');

            if (! $periodYear || ! $periodMonth || ! $cutoff) {
                return;
            }

            $pairs = collect((array) $value)
                ->filter(fn ($row) => ! empty($row['employee_id']) && ! empty($row['allowance_type_id']))
                ->map(fn ($row) => $row['employee_id'] . '-' . $row['allowance_type_id'])
                ->unique();

            if ($pairs->isEmpty()) {
                return;
            }

            $employeeIds = collect((array) $value)->pluck('employee_id')->filter()->unique();
            $typeIds     = collect((array) $value)->pluck('allowance_type_id')->filter()->unique();

            $conflicts = AllowanceAssignmentEntry::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('allowance_type_id', $typeIds)
                ->whereHas('assignment', function ($q) use ($periodYear, $periodMonth, $cutoff, $excludeBatchId) {
                    $q->where('period_year', $periodYear)
                      ->where('period_month', $periodMonth)
                      ->where('cutoff', $cutoff);

                    if ($excludeBatchId) {
                        $q->where('id', '!=', $excludeBatchId);
                    }
                })
                ->with(['employee', 'allowanceType'])
                ->get()
                ->filter(fn ($e) => $pairs->contains($e->employee_id . '-' . $e->allowance_type_id));

            if ($conflicts->isNotEmpty()) {
                $sample = $conflicts->take(5)->map(function ($e) {
                    $name = trim(($e->employee->last_name ?? '?') . ', ' . ($e->employee->first_name ?? ''));
                    $type = $e->allowanceType->name ?? 'this allowance';

                    return "{$name} ({$type}, Assignment #{$e->allowance_assignment_id})";
                })->implode('; ');

                $more = $conflicts->count() > 5
                    ? ' and ' . ($conflicts->count() - 5) . ' more'
                    : '';

                $fail("These employees already have an entry for this allowance type in this period, in another assignment: {$sample}{$more}. Remove them here or edit the existing assignment instead.");
            }
        };
    }
}

<?php

namespace Modules\Payroll\Http\Controllers\Allowances;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\Allowances\AllowanceBatch;
use Modules\Payroll\Models\Allowances\AllowanceEntry;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Modules\Payroll\Models\Allowances\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AllowanceBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = AllowanceBatch::with('creator')
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

        $batches     = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::allowances.batches.index', compact('batches', 'currentYear'));
    }

    public function create()
    {
        $types     = AllowanceType::where('is_active', true)->orderBy('display_order')->get();
        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        $standingPairs = $this->getStandingPairs();

        return view('payroll::allowances.batches.create', compact('types', 'employees', 'standingPairs'));
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

        $batch = DB::transaction(function () use ($validated) {
            $batch = AllowanceBatch::create([
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
                AllowanceEntry::create([
                    'allowance_batch_id' => $batch->id,
                    'employee_id'        => $row['employee_id'],
                    'allowance_type_id'  => $row['allowance_type_id'],
                    'amount'             => $amount,
                    'gross_amount'       => $amount,
                    'net_amount'         => $amount,
                    'remarks'            => $row['remarks'] ?? null,
                ]);
            }

            return $batch;
        });

        return redirect()->route('payroll.allowances.batches.show', $batch)
            ->with('success', 'Allowance batch created.');
    }

    public function show(AllowanceBatch $batch)
    {
        $batch->load(['entries.employee', 'entries.allowanceType', 'creator']);

        return view('payroll::allowances.batches.show', compact('batch'));
    }

    public function edit(AllowanceBatch $batch)
    {
        if (! in_array($batch->status, ['draft'], true)) {
            return redirect()->route('payroll.allowances.batches.show', $batch)
                ->with('error', 'Only draft batches can be edited.');
        }

        $batch->load(['entries.employee', 'entries.allowanceType']);
        $types     = AllowanceType::where('is_active', true)->orderBy('display_order')->get();
        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        $standingPairs = $this->getStandingPairs();

        return view('payroll::allowances.batches.edit', compact('batch', 'types', 'employees', 'standingPairs'));
    }

    public function update(Request $request, AllowanceBatch $batch)
    {
        if ($batch->status !== 'draft') {
            return redirect()->route('payroll.allowances.batches.show', $batch)
                ->with('error', 'Only draft batches can be edited.');
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
                $this->noCrossBatchDuplicateRule($request, $batch->id),
            ],
            'entries.*.employee_id'       => ['required', 'exists:employees,id'],
            'entries.*.allowance_type_id' => ['required', 'exists:allowance_types,id'],
            'entries.*.amount'            => ['required', 'numeric', 'min:0'],
            'entries.*.remarks'           => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($batch, $validated) {
            $batch->update([
                'period_year'  => $validated['period_year'],
                'period_month' => $validated['period_month'],
                'cutoff'       => $validated['cutoff'],
                'period_start' => $validated['period_start'],
                'period_end'   => $validated['period_end'] ?? null,
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            $batch->entries()->delete();

            foreach ($validated['entries'] as $row) {
                $amount = round((float) $row['amount'], 2);
                AllowanceEntry::create([
                    'allowance_batch_id' => $batch->id,
                    'employee_id'        => $row['employee_id'],
                    'allowance_type_id'  => $row['allowance_type_id'],
                    'amount'             => $amount,
                    'gross_amount'       => $amount,
                    'net_amount'         => $amount,
                    'remarks'            => $row['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('payroll.allowances.batches.show', $batch)
            ->with('success', 'Allowance batch updated.');
    }

    public function advance(Request $request, AllowanceBatch $batch)
    {
        $action = $request->validate(['action' => 'required|in:submit,approve,release'])['action'];

        $transitions = [
            'submit'  => ['from' => ['draft'],          'to' => 'pending_review', 'field' => 'reviewed'],
            'approve' => ['from' => ['pending_review'], 'to' => 'approved',       'field' => 'approved'],
            'release' => ['from' => ['approved'],       'to' => 'released',       'field' => 'released'],
        ];

        $rule = $transitions[$action];

        if (! in_array($batch->status, $rule['from'], true)) {
            return back()->with('error', 'Invalid status transition.');
        }

        $updates = ['status' => $rule['to']];

        if ($rule['field'] === 'reviewed') {
            $updates['reviewed_by'] = Auth::id();
            $updates['reviewed_at'] = now();
        } elseif ($rule['field'] === 'approved') {
            $updates['approved_by'] = Auth::id();
            $updates['approved_at'] = now();
        } else {
            $updates['released_by'] = Auth::id();
            $updates['released_at'] = now();
        }

        $batch->update($updates);

        return back()->with('success', 'Batch status updated to ' . str_replace('_', ' ', $rule['to']) . '.');
    }

    public function destroy(AllowanceBatch $batch)
    {
        if ($batch->status !== 'draft') {
            return back()->with('error', 'Only draft batches can be deleted.');
        }

        $batch->delete();

        return redirect()->route('payroll.allowances.index')
            ->with('success', 'Allowance batch deleted.');
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

            $conflicts = AllowanceEntry::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('allowance_type_id', $typeIds)
                ->whereHas('batch', function ($q) use ($periodYear, $periodMonth, $cutoff, $excludeBatchId) {
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

                    return "{$name} ({$type}, Batch #{$e->allowance_batch_id})";
                })->implode('; ');

                $more = $conflicts->count() > 5
                    ? ' and ' . ($conflicts->count() - 5) . ' more'
                    : '';

                $fail("These employees already have an entry for this allowance type in this period, in another batch: {$sample}{$more}. Remove them here or edit the existing batch instead.");
            }
        };
    }
}

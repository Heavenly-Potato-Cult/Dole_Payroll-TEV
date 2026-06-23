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

        return view('payroll::allowances.batches.create', compact('types', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month'  => ['required', 'integer', 'min:1', 'max:12'],
            'cutoff'        => ['required', 'string', 'in:1st,2nd,monthly'],
            'period_start'  => ['required', 'date'],
            'period_end'    => ['required', 'date', 'after_or_equal:period_start'],
            'remarks'       => ['nullable', 'string'],
            'entries'       => ['required', 'array', 'min:1'],
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
                'period_end'   => $validated['period_end'],
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

        return view('payroll::allowances.batches.edit', compact('batch', 'types', 'employees'));
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
            'period_end'    => ['required', 'date', 'after_or_equal:period_start'],
            'remarks'       => ['nullable', 'string'],
            'entries'       => ['required', 'array', 'min:1'],
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
                'period_end'   => $validated['period_end'],
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
            'submit'  => ['from' => ['draft'],              'to' => 'pending_review', 'field' => 'reviewed'],
            'approve' => ['from' => ['pending_review'],     'to' => 'approved',       'field' => 'approved'],
            'release' => ['from' => ['approved'],           'to' => 'released',       'field' => 'released'],
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
}

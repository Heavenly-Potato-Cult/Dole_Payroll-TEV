<?php

namespace Modules\Payroll\Http\Controllers\Allowances;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AllowanceTypeController extends Controller
{
    public function index()
    {
        $types = AllowanceType::orderBy('display_order')
            ->orderBy('name')
            ->get();

        // --- Build enrolled count per type in PHP to avoid MySQL's derived-table
        //     scope barrier that breaks correlated UNION subqueries. ---
        //
        // Source 1: active standing enrollments (employee_allowances)
        $standingCounts = DB::table('employee_allowances')
            ->select('allowance_type_id', 'employee_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('allowance_type_id')
            ->map(fn ($rows) => $rows->pluck('employee_id')->unique());

        // Source 2: entries in released assignments (allowance_assignment_entries)
        $assignmentCounts = DB::table('allowance_assignment_entries as aae')
            ->select('aae.allowance_type_id', 'aae.employee_id')
            ->join('allowance_assignments as aa', 'aa.id', '=', 'aae.allowance_assignment_id')
            ->whereIn('aa.status', ['draft', 'released'])
            ->whereNull('aae.deleted_at')
            ->whereNull('aa.deleted_at')
            ->get()
            ->groupBy('allowance_type_id')
            ->map(fn ($rows) => $rows->pluck('employee_id')->unique());

        // Merge both sources per type: union the employee_id sets, then count distinct.
        $types->each(function ($type) use ($standingCounts, $assignmentCounts) {
            $standing   = $standingCounts->get($type->id, collect());
            $assignment = $assignmentCounts->get($type->id, collect());

            $type->active_enrollments_count = $standing->merge($assignment)->unique()->count();
        });

        return view('payroll::allowances.types.index', compact('types'));
    }

    public function create()
    {
        $nextOrder = (AllowanceType::max('display_order') ?? 0) + 1;

        return view('payroll::allowances.types.create', compact('nextOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'                     => ['required', 'string', 'max:50', 'unique:allowance_types,code', 'regex:/^[A-Z0-9_]+$/'],
            'name'                     => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'is_taxable'               => ['boolean'],
            'is_gsis_deductible'       => ['boolean'],
            'is_philhealth_deductible' => ['boolean'],
            'is_pagibig_deductible'    => ['boolean'],
            'display_order'            => ['integer', 'min:0'],
            'is_active'                => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        AllowanceType::create($validated);

        return redirect()->route('payroll.allowances.types.index')
            ->with('success', 'Allowance type created.');
    }

    public function edit(AllowanceType $type)
    {
        return view('payroll::allowances.types.edit', compact('type'));
    }

    public function update(Request $request, AllowanceType $type)
    {
        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'is_taxable'               => ['boolean'],
            'is_gsis_deductible'       => ['boolean'],
            'is_philhealth_deductible' => ['boolean'],
            'is_pagibig_deductible'    => ['boolean'],
            'display_order'            => ['integer', 'min:0'],
        ]);

        $type->update($validated);

        return redirect()->route('payroll.allowances.types.index')
            ->with('success', 'Allowance type updated.');
    }

    public function toggle(AllowanceType $type)
    {
        $type->update(['is_active' => ! $type->is_active]);

        return redirect()->route('payroll.allowances.types.index')
            ->with('success', 'Allowance type status updated.');
    }

    public function destroy(AllowanceType $type)
    {
        if ($type->employeeAllowances()->exists() || $type->assignmentEntries()->exists()) {
            return redirect()->route('payroll.allowances.types.index')
                ->with('error', 'Cannot delete — employees are enrolled in this allowance type.');
        }

        $type->delete();

        return redirect()->route('payroll.allowances.types.index')
            ->with('success', 'Allowance type deleted.');
    }
}

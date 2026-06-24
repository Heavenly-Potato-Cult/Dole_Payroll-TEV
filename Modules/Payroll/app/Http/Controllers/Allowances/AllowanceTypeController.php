<?php

namespace Modules\Payroll\Http\Controllers\Allowances;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AllowanceTypeController extends Controller
{
    public function index()
    {
        $types = AllowanceType::withCount([
                'employeeAllowances as active_enrollments_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

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
        if ($type->employeeAllowances()->exists()) {
            return redirect()->route('payroll.allowances.types.index')
                ->with('error', 'Cannot delete — employees are enrolled in this allowance type.');
        }

        $type->delete();

        return redirect()->route('payroll.allowances.types.index')
            ->with('success', 'Allowance type deleted.');
    }
}

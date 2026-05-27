<?php

namespace App\Http\Controllers\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Modules\Payroll\Models\AllowanceType;
use Illuminate\Http\Request;

class AllowanceTypeController extends Controller
{
    public function index()
    {
        $allowanceTypes = AllowanceType::orderBy('sort_order')->orderBy('name')->get();
        return view('payroll::allowance-types.index', compact('allowanceTypes'));
    }

    public function create()
    {
        return view('payroll::allowance-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:allowance_types'],
            'description' => ['nullable', 'string'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        AllowanceType::create($validated);

        return redirect()->route('allowance-types.index')
            ->with('success', 'Allowance type created successfully.');
    }

    public function edit(AllowanceType $allowanceType)
    {
        return view('payroll::allowance-types.edit', compact('allowanceType'));
    }

    public function update(Request $request, AllowanceType $allowanceType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:allowance_types,code,' . $allowanceType->id],
            'description' => ['nullable', 'string'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $allowanceType->update($validated);

        return redirect()->route('allowance-types.index')
            ->with('success', 'Allowance type updated successfully.');
    }

    public function toggle(AllowanceType $allowanceType)
    {
        $allowanceType->update([
            'is_active' => !$allowanceType->is_active,
        ]);

        return redirect()->route('allowance-types.index')
            ->with('success', 'Allowance type status updated.');
    }

    public function destroy(AllowanceType $allowanceType)
    {
        $allowanceType->delete();

        return redirect()->route('allowance-types.index')
            ->with('success', 'Allowance type deleted successfully.');
    }
}

<?php

namespace Modules\Allowances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\Employee;
use Modules\Allowances\Models\AllowanceType;
use Modules\Allowances\Models\EmployeeAllowance;
use Illuminate\Http\Request;

class EmployeeAllowanceController extends Controller
{
    public function index(Employee $employee)
    {
        $employee->load('division');

        $types = AllowanceType::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $enrollments = EmployeeAllowance::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->with('allowanceType')
            ->get()
            ->keyBy('allowance_type_id');

        return view('allowances::employees.allowances', compact('employee', 'types', 'enrollments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'allowances'                          => 'nullable|array',
            'allowances.*.amount'                 => ['nullable', 'numeric', 'min:0'],
            'allowances.*.effectivity_date'       => ['nullable', 'date'],
            'allowances.*.expiry_date'          => ['nullable', 'date'],
            'allowances.*.remarks'              => ['nullable', 'string'],
            'allowances.*.enabled'              => ['nullable', 'boolean'],
        ]);

        $types = AllowanceType::where('is_active', true)->get()->keyBy('id');

        foreach ($types as $typeId => $type) {
            $row = $request->input("allowances.{$typeId}", []);
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $existing = EmployeeAllowance::where('employee_id', $employee->id)
                ->where('allowance_type_id', $typeId)
                ->where('is_active', true)
                ->first();

            if (! $enabled) {
                if ($existing) {
                    $existing->update(['is_active' => false]);
                }
                continue;
            }

            $amount = isset($row['amount']) ? round((float) $row['amount'], 2) : 0;
            $effectivity = $row['effectivity_date'] ?? now()->toDateString();

            if ($existing) {
                $existing->update([
                    'amount'           => $amount,
                    'effectivity_date' => $effectivity,
                    'expiry_date'      => $row['expiry_date'] ?? null,
                    'remarks'          => $row['remarks'] ?? null,
                    'is_active'        => true,
                ]);
            } else {
                EmployeeAllowance::create([
                    'employee_id'        => $employee->id,
                    'allowance_type_id'  => $typeId,
                    'amount'             => $amount,
                    'effectivity_date'   => $effectivity,
                    'expiry_date'        => $row['expiry_date'] ?? null,
                    'remarks'            => $row['remarks'] ?? null,
                    'is_active'          => true,
                ]);
            }
        }

        return redirect()->route('employees.allowances', $employee)
            ->with('success', 'Employee allowances updated.');
    }
}

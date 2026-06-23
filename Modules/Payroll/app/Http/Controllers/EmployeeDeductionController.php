<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionType;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\EmployeeDeductionEnrollment;
use Illuminate\Http\Request;

class EmployeeDeductionController extends Controller
{
    /**
     * Show all deduction enrollments for an employee.
     *
     * Non-loan types are kept exactly as before: $enrollments is keyed by
     * deduction_type_id so the Blade view can do a direct lookup
     * ($enrollments[$type->id]).
     *
     * Types where DeductionType::supportsMultipleAccounts() is true can
     * have multiple active rows per type — one per account. Those are
     * collected separately into $loanEnrollments, nested as
     * [deduction_type_id][account_number] => enrollment, so the Blade
     * partial can render one "slot" per account.
     */
    public function index(Employee $employee)
    {
        $employee->load(['division']);

        $deductionTypes = DeductionType::active()->ordered()->get();

        $loanTypeIds = $deductionTypes
            ->filter(fn ($type) => $type->supportsMultipleAccounts())
            ->pluck('id')
            ->all();

        $allEnrollments = EmployeeDeductionEnrollment::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->with('deductionType')
            ->get();

        $enrollments = $allEnrollments
            ->reject(fn ($e) => in_array($e->deduction_type_id, $loanTypeIds))
            ->keyBy('deduction_type_id');

        $loanEnrollments = $allEnrollments
            ->filter(fn ($e) => in_array($e->deduction_type_id, $loanTypeIds))
            ->groupBy('deduction_type_id')
            ->map(fn ($group) => $group->values());

        return view('payroll::employees.deductions', compact(
            'employee',
            'deductionTypes',
            'enrollments',
            'loanEnrollments'
        ));
    }

    /**
     * Bulk-upsert deduction enrollments from the deductions form.
     *
     * Skip rules (these types are not managed per-employee):
     *   1. Formula-driven types (is_computed = true) — amounts come from the payroll engine.
     *   2. Effectively locked types (is_locked = true AND not a loan category) —
     *      amounts come from DeductionType::default_amount globally.
     *
     * Loan-category types are handled separately via syncLoanAccounts():
     * each submitted "account" is its own enrollment row, upserted on
     * (employee_id, deduction_type_id, account_number).
     *
     * Un-enrolling deactivates records rather than deleting them to
     * preserve the audit trail.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'deductions'                              => 'nullable|array',
            'deductions.*.amount'                      => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'deductions.*.effective_from'               => 'nullable|date',
            'deductions.*.effective_to'                 => 'nullable|date|after_or_equal:deductions.*.effective_from',

            // Loan-category "accounts" payload
            'deductions.*.accounts'                     => 'nullable|array|max:3',
            'deductions.*.accounts.*.amount'            => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'deductions.*.accounts.*.account_number'    => 'nullable|string|max:100',
            'deductions.*.accounts.*.effective_from'    => 'nullable|date',
            'deductions.*.accounts.*.effective_to'      => 'nullable|date|after_or_equal:deductions.*.accounts.*.effective_from',
            'deductions.*.accounts.*.notes'             => 'nullable|string|max:200',
        ]);

        $submitted = $request->input('deductions', []);

        foreach ($submitted as $typeId => $data) {
            $type = DeductionType::find($typeId);

            if (! $type) continue;

            // Skip formula-computed types — owned by payroll engine
            if ($type->is_computed) continue;

            // Skip effectively-locked types — owned by the global default_amount
            if ($type->isEffectivelyLocked()) continue;

            $enrolled = ! empty($data['enrolled']);

            if ($type->supportsMultipleAccounts()) {
                $this->syncLoanAccounts($employee, $type, $enrolled, $data['accounts'] ?? []);
                continue;
            }

            $amount = $enrolled ? ($data['amount'] ?? 0) : 0;

            if ($enrolled && $amount > 0) {
                EmployeeDeductionEnrollment::updateOrCreate(
                    [
                        'employee_id'       => $employee->id,
                        'deduction_type_id' => $typeId,
                        'account_number'    => null,
                    ],
                    [
                        'amount'         => $amount,
                        'effective_from' => $data['effective_from'] ?? now()->startOfMonth()->toDateString(),
                        'effective_to'   => $data['effective_to'] ?: null,
                        'is_active'      => true,
                        'notes'          => $data['notes'] ?? null,
                    ]
                );
            } else {
                EmployeeDeductionEnrollment::where('employee_id', $employee->id)
                    ->where('deduction_type_id', $typeId)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        }

        return redirect()->route('employees.deductions', $employee)
            ->with('success', 'Deductions updated for ' . $employee->full_name . '.');
    }

    /**
     * Sync the per-account enrollment rows for a loan-category deduction type.
     *
     * Each account slot upserts its own row keyed on
     * (employee_id, deduction_type_id, account_number). Slots with no
     * amount (or amount <= 0) are skipped rather than saved as zero rows.
     *
     * Unchecking the type's main "enrolled" checkbox deactivates ALL
     * existing accounts for that type, regardless of what was submitted
     * in $accounts.
     *
     * Any previously-active account that was NOT re-submitted this time
     * (the user clicked the "×" remove button on that slot) is also
     * deactivated, so removed accounts don't linger as active.
     */
    private function syncLoanAccounts(Employee $employee, DeductionType $type, bool $enrolled, array $accounts): void
    {
        if (! $enrolled) {
            EmployeeDeductionEnrollment::where('employee_id', $employee->id)
                ->where('deduction_type_id', $type->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return;
        }

        $keptIds = [];

        foreach ($accounts as $account) {
            $amount = (float) ($account['amount'] ?? 0);

            if ($amount <= 0) continue;

            $accountNumber = trim((string) ($account['account_number'] ?? ''));
            $accountNumber = $accountNumber !== '' ? $accountNumber : null;

            $row = EmployeeDeductionEnrollment::updateOrCreate(
                [
                    'employee_id'       => $employee->id,
                    'deduction_type_id' => $type->id,
                    'account_number'    => $accountNumber,
                ],
                [
                    'amount'         => $amount,
                    'effective_from' => $account['effective_from'] ?? now()->startOfMonth()->toDateString(),
                    'effective_to'   => $account['effective_to'] ?: null,
                    'is_active'      => true,
                    'notes'          => $account['notes'] ?? null,
                ]
            );

            $keptIds[] = $row->id;
        }

        // Deactivate any previously-active account rows for this type that
        // weren't re-submitted this time (i.e. the user removed that slot).
        EmployeeDeductionEnrollment::where('employee_id', $employee->id)
            ->where('deduction_type_id', $type->id)
            ->where('is_active', true)
            ->whereNotIn('id', $keptIds ?: [0])
            ->update(['is_active' => false]);
    }
}

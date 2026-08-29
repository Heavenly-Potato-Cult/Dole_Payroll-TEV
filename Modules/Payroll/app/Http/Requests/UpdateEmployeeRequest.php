<?php

namespace Modules\Payroll\Http\Requests;

use App\SharedKernel\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            // ── Identity ─────────────────────────────────────────
            'plantilla_item_no' => ['required', 'string', 'max:100',
                                    Rule::unique('employees', 'plantilla_item_no')
                                        ->ignore($employeeId)
                                        ->whereNull('deleted_at')],
            'last_name'         => ['required', 'string', 'max:100'],
            'first_name'        => ['required', 'string', 'max:100'],
            'middle_name'       => ['nullable', 'string', 'max:100'],
            'suffix'            => ['nullable', 'string', 'max:20'],

            // ── Position ─────────────────────────────────────────
            'position_title'    => ['required', 'string', 'max:200'],
            'division_id'       => ['required', 'integer', 'exists:divisions,id'],
            // Nullable — see StoreEmployeeRequest for why. This was the
            // missing piece causing PSIPOP edits to silently no-op: without
            // a rule here, $request->validated() dropped the field before
            // it ever reached $employee->update($data).
            'psipop_office_id'  => ['nullable', 'integer', 'exists:psipop_offices,id'],

            // ── Salary ───────────────────────────────────────────
            'salary_grade'      => ['required', 'integer', 'min:1', 'max:33'],
            'step'              => ['required', 'integer', 'min:1', 'max:8'],
            'sit_year'          => ['required', 'integer', 'min:2021'],
            'basic_salary'      => ['required', 'numeric', 'min:1'],
            // pera removed 2026-08-19 — edit.blade.php no longer submits it
            // (read-only now, resolved via AllowanceService; see
            // EmployeeController::resolvedPeraInfo()). A 'required' rule
            // here would fail every employee update, since the field is
            // never present in the request body anymore.
            // 2026-08-14: nullable = keep the actual-attendance-days cutoff
            // split (default). 0-100 = fixed % of net pay at the 1st cutoff.
            'salary_split_override_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // ── Employment ───────────────────────────────────────
            'hire_date'         => ['nullable', 'date'],
            'status'            => ['required', Rule::in(Employee::STATUSES)],

            // ── Government IDs ────────────────────────────────────
            'tin'               => ['nullable', 'string', 'max:50'],
            'gsis_bp_no'        => ['nullable', 'string', 'max:50'],
            'pagibig_no'        => ['nullable', 'string', 'max:50'],
            'pagibig_id'        => ['nullable', 'string', 'max:50'],
            'pagibig_mid_no'    => ['nullable', 'string', 'max:50'],
            'mp2_account_no'    => ['nullable', 'string', 'max:50'],
            'hdmf_mpl_app_no'   => ['nullable', 'string', 'max:80'],
            'hdmf_cal_app_no'   => ['nullable', 'string', 'max:80'],
            'hdmf_housing_app_no' => ['nullable', 'string', 'max:80'],
            'philhealth_no'     => ['nullable', 'string', 'max:50'],
            'sss_no'            => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'plantilla_item_no.unique' => 'This plantilla item number is already assigned to another employee.',
            'division_id.exists'       => 'The selected division does not exist.',
            'psipop_office_id.exists'  => 'The selected PSIPOP office does not exist.',
            'basic_salary.required'    => 'Basic salary is required. Use the SG/Step lookup to auto-fill.',
            'salary_split_override_pct.max' => 'The 1st Cutoff Split % must be between 0 and 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'plantilla_item_no' => 'Plantilla Item No.',
            'last_name'         => 'Last Name',
            'first_name'        => 'First Name',
            'middle_name'       => 'Middle Name',
            'position_title'    => 'Position Title',
            'division_id'       => 'Division',
            'psipop_office_id'  => 'PSIPOP Office',
            'salary_grade'      => 'Salary Grade',
            'basic_salary'      => 'Basic Salary',
            'salary_split_override_pct' => '1st Cutoff Split %',
            'hire_date'         => 'Hire Date',
            'sit_year'          => 'SIT Year',
            'gsis_bp_no'        => 'GSIS Number',
            'pagibig_no'        => 'Pag-IBIG Number',
            'pagibig_id'        => 'Pag-IBIG ID',
            'pagibig_mid_no'    => 'Pag-IBIG MID Number',
            'mp2_account_no'    => 'MP2 Account Number',
            'hdmf_mpl_app_no'   => 'HDMF MPL Application Number',
            'hdmf_cal_app_no'   => 'HDMF Calamity Application Number',
            'hdmf_housing_app_no' => 'HDMF Housing Application Number',
            'philhealth_no'     => 'PhilHealth Number',
        ];
    }
}

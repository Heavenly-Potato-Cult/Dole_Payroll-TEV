<?php

namespace Modules\Payroll\Http\Requests;

use App\SharedKernel\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth check handled by route middleware
    }

    public function rules(): array
    {
        return [
            // ── Identity ─────────────────────────────────────────
            'plantilla_item_no' => ['required', 'string', 'max:100', 'unique:employees,plantilla_item_no'],
            'last_name'         => ['required', 'string', 'max:100'],
            'first_name'        => ['required', 'string', 'max:100'],
            'middle_name'       => ['nullable', 'string', 'max:100'],
            'suffix'            => ['nullable', 'string', 'max:20'],

            // ── Position ─────────────────────────────────────────
            'position_title'    => ['required', 'string', 'max:200'],
            'division_id'       => ['required', 'integer', 'exists:divisions,id'],
            // Nullable because the "always populated" guarantee is enforced
            // by Employee::booted()'s creating hook (defaults to the
            // Unassigned bucket), not by the validator — the form always
            // sends a value in practice since edit.blade.php's <select>
            // has no blank option, but this must not block a submission
            // that somehow omits it.
            'psipop_office_id'  => ['nullable', 'integer', 'exists:psipop_offices,id'],

            // ── Salary ───────────────────────────────────────────
            'salary_grade'      => ['required', 'integer', 'min:1', 'max:33'],
            'step'              => ['required', 'integer', 'min:1', 'max:8'],
            'sit_year'          => ['required', 'integer', 'min:2021'],
            'basic_salary'      => ['required', 'numeric', 'min:1'],
            'pera'              => ['required', 'numeric', 'min:0'],

            // ── Employment ───────────────────────────────────────
            'hire_date'         => ['nullable', 'date'],
            'status'            => ['required', Rule::in(Employee::STATUSES)],

            // ── Government IDs (optional) ─────────────────────────
            'tin'               => ['nullable', 'string', 'max:50'],
            'gsis_bp_no'           => ['nullable', 'string', 'max:50'],
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
            'salary_grade.min'         => 'Salary Grade must be between 1 and 33.',
            'salary_grade.max'         => 'Salary Grade must be between 1 and 33.',
            'step.min'                 => 'Step must be between 1 and 8.',
            'step.max'                 => 'Step must be between 1 and 8.',
            'basic_salary.required'    => 'Basic salary is required. Use the SG/Step lookup to auto-fill.',
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
            'hire_date'         => 'Hire Date',
            'sit_year'          => 'SIT Year',
            'gsis_bp_no'           => 'GSIS Number',
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

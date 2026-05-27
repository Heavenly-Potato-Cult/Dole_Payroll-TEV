<?php

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComputePayrollRequest extends FormRequest
{
    /**
     * Only payroll_officer and hrmo may create/compute payroll.
     */
    public function authorize(): bool
    {
        return \App\SharedKernel\Services\RoleService::canCreatePayroll($this->user());
    }

    public function rules(): array
    {
        return [
            'period_year'  => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'cutoff'       => ['required', 'in:1st,2nd'],
            'period_start' => ['nullable', 'date', 'after_or_equal:period_year-01-01'],
            'period_end'   => ['nullable', 'date', 'after:period_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_year.required'  => 'Please select a payroll year.',
            'period_year.min'       => 'Year must be 2020 or later.',
            'period_month.required' => 'Please select a payroll month.',
            'period_month.min'      => 'Month must be between January and December.',
            'period_month.max'      => 'Month must be between January and December.',
            'cutoff.required'       => 'Please choose a cut-off period.',
            'cutoff.in'             => 'Cut-off must be either 1st (1–15) or 2nd (16–30/31).',
            'period_start.after_or_equal' => 'Period start must be within the selected year.',
            'period_end.after'      => 'Period end must be after period start.',
        ];
    }

    /**
     * Convenience: return human-readable period string, e.g. "March 1–15, 2026"
     */
    public function periodLabel(): string
    {
        $months = [
            1=>'January',2=>'February',3=>'March',4=>'April',
            5=>'May',6=>'June',7=>'July',8=>'August',
            9=>'September',10=>'October',11=>'November',12=>'December',
        ];
        $month = $months[(int) $this->period_month] ?? '—';
        $year  = $this->period_year;
        $days  = $this->cutoff === '1st' ? '1–15' : '16–30/31';

        return "{$month} {$days}, {$year}";
    }
}

<?php

namespace Modules\Payroll\Http\Requests;

use Carbon\Carbon;
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
            // 'cutoff' removed — system now uses a single monthly batch.
            'period_start' => [
                'nullable',
                'date',
                'after_or_equal:' . $this->input('period_year') . '-01-01',
            ],
            'period_end'   => [
                'nullable',
                'date',
                'after:period_start',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period_year.required'        => 'Please select a payroll year.',
            'period_year.min'             => 'Year must be 2020 or later.',
            'period_month.required'       => 'Please select a payroll month.',
            'period_month.min'            => 'Month must be between January and December.',
            'period_month.max'            => 'Month must be between January and December.',
            'period_start.after_or_equal' => 'Period start must be within the selected year.',
            'period_end.after'            => 'Period end must be after period start.',
        ];
    }

    /**
     * Derive the full-month period_start date (1st of the month).
     * Returns the validated input if provided, otherwise computes from year + month.
     */
    public function resolvedPeriodStart(): string
    {
        if ($this->filled('period_start')) {
            return $this->input('period_start');
        }

        return Carbon::create((int) $this->period_year, (int) $this->period_month, 1)
            ->startOfMonth()
            ->toDateString();
    }

    /**
     * Derive the full-month period_end date (last day of the month).
     * Returns the validated input if provided, otherwise computes from year + month.
     */
    public function resolvedPeriodEnd(): string
    {
        if ($this->filled('period_end')) {
            return $this->input('period_end');
        }

        return Carbon::create((int) $this->period_year, (int) $this->period_month, 1)
            ->endOfMonth()
            ->toDateString();
    }

    /**
     * Human-readable period label, e.g. "May 1–31, 2026".
     * Replaces the old cutoff-based label ("March 1–15, 2026").
     */
    public function periodLabel(): string
    {
        $start = Carbon::parse($this->resolvedPeriodStart())->format('F j');
        $end   = Carbon::parse($this->resolvedPeriodEnd())->format('j, Y');

        return "{$start}–{$end}";
    }

    /**
     * Convenience: return the month name, e.g. "May".
     */
    public function monthName(): string
    {
        return Carbon::create((int) $this->period_year, (int) $this->period_month, 1)
            ->format('F');
    }
}
<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * SalaryDifferentialService
 *
 * Computes salary differential payroll for promotions, step increments,
 * and salary adjustments — exactly matching the 01C Excel WP sheet logic.
 *
 * Formula reference (WP sheet):
 *   differential     = new_salary - old_salary
 *   partial month    : ROUND(differential × days_in_partial / 22, 2)
 *   full month       : ROUND(differential × 22 / 22, 2) = differential
 *   per-month GSIS   = ROUND(differential_earned × 0.09, 2)
 *   per-month PHIC   = ROUND(differential_earned × 0.05 / 2, 2)
 *   per-month Pag-IB = 200.00 (fixed per month)
 *   WHT              = ROUND(total_earned × wht_rate, 2)   [employee rate or 20%]
 *   total_deductions = sum(GSIS) + sum(PHIC) + sum(Pag-IBIG) + WHT
 *   net_amount       = total_earned - total_deductions
 *
 * NOTE: Deductions are computed on the DIFFERENTIAL AMOUNT ONLY —
 *       not on the full basic salary. Only mandatory deductions apply:
 *       GSIS Personal Share, PhilHealth, Pag-IBIG I, and WHT.
 */
class SalaryDifferentialService
{
    private const DENOMINATOR  = 22;
    private const GSIS_RATE    = 0.09;
    private const PHIC_RATE    = 0.025;   // 5% ÷ 2 (employee share)
    private const PAGIBIG_FIXED = 200.00;
    private const DEFAULT_WHT  = 0.20;

    /**
     * Compute the salary differential for a given employee and date range.
     *
     * @param  Employee $employee
     * @param  string   $effectivity_date_from  e.g. "2024-10-26"
     * @param  string   $effectivity_date_to    e.g. "2025-12-31"
     * @param  float    $old_salary
     * @param  float    $new_salary
     * @param  array|null $deductionRates  Optional custom deduction rates
     * @return array{
     *   differential:      float,
     *   per_month:         array<int, array{month_label: string, days: int, earned: float,
     *                          gsis: float, phic: float, pagibig: float}>,
     *   total_earned:      float,
     *   total_gsis:        float,
     *   total_phic:        float,
     *   total_pagibig:     float,
     *   total_wht:         float,
     *   total_deductions:  float,
     *   net_amount:        float,
     *   wht_rate:          float,
     *   effectivity_from:  string,
     *   effectivity_to:    string,
     *   old_salary:        float,
     *   new_salary:        float,
     * }
     */
    public function compute(
        Employee $employee,
        string   $effectivity_date_from,
        string   $effectivity_date_to,
        float    $old_salary,
        float    $new_salary,
        ?array   $deductionRates = null,
    ): array {
        $from         = Carbon::parse($effectivity_date_from)->startOfDay();
        $to           = Carbon::parse($effectivity_date_to)->startOfDay();
        $differential = round($new_salary - $old_salary, 2);

        // Use custom deduction rates if provided, otherwise use defaults
        $gsisRate = isset($deductionRates['gsis_percent']) && $deductionRates['gsis_percent'] !== null
            ? (float) $deductionRates['gsis_percent'] / 100
            : self::GSIS_RATE;
        $phicRate = isset($deductionRates['philhealth_percent']) && $deductionRates['philhealth_percent'] !== null
            ? (float) $deductionRates['philhealth_percent'] / 100
            : self::PHIC_RATE;
        $pagibigFixed = isset($deductionRates['pagibig_amount']) && $deductionRates['pagibig_amount'] !== null
            ? (float) $deductionRates['pagibig_amount']
            : self::PAGIBIG_FIXED;
        $whtRate = isset($deductionRates['wht_percent']) && $deductionRates['wht_percent'] !== null
            ? (float) $deductionRates['wht_percent'] / 100
            : (isset($employee->wht_rate) && $employee->wht_rate > 0
                ? (float) $employee->wht_rate
                : self::DEFAULT_WHT);

        $perMonth    = [];
        $totalEarned = 0.0;
        $totalGsis   = 0.0;
        $totalPhic   = 0.0;
        $totalPagIbig = 0.0;

        // ── Iterate month by month across the effectivity range ───────────
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to->copy()->startOfMonth())) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            // Clamp to the effectivity window
            $segmentStart = $monthStart->lt($from) ? $from->copy() : $monthStart->copy();
            $segmentEnd   = $monthEnd->gt($to)     ? $to->copy()   : $monthEnd->copy();

            // Count working days in segment (calendar days, no exclusions —
            // DOLE uses calendar days against the fixed 22 denominator)
            $daysInMonth  = $monthEnd->day;              // total days in month
            $daysInSegment = $segmentEnd->day - $segmentStart->day + 1;

            // Full month: segment covers the entire month
            $isFullMonth = ($segmentStart->day === 1 && $segmentEnd->day === $daysInMonth);

            if ($isFullMonth) {
                $earned = round($differential, 2);
                $days   = $daysInMonth;
            } else {
                $earned = round($differential * $daysInSegment / self::DENOMINATOR, 2);
                $days   = $daysInSegment;
            }

            $gsis   = round($earned * $gsisRate, 2);
            $phic   = round($earned * $phicRate, 2);
            $pagIbig = $pagibigFixed;

            $perMonth[] = [
                'month_label' => $cursor->format('M Y'),
                'days'        => $days,
                'earned'      => $earned,
                'gsis'        => $gsis,
                'phic'        => $phic,
                'pagibig'     => $pagIbig,
                'is_full'     => $isFullMonth,
            ];

            $totalEarned  += $earned;
            $totalGsis    += $gsis;
            $totalPhic    += $phic;
            $totalPagIbig += $pagIbig;

            $cursor->addMonth();
        }

        // Round accumulated totals to avoid float drift
        $totalEarned  = round($totalEarned, 2);
        $totalGsis    = round($totalGsis, 2);
        $totalPhic    = round($totalPhic, 2);
        $totalPagIbig = round($totalPagIbig, 2);

        // WHT is applied on total differential earned
        $totalWht        = round($totalEarned * $whtRate, 2);
        $totalDeductions = round($totalGsis + $totalPhic + $totalPagIbig + $totalWht, 2);
        $netAmount       = round($totalEarned - $totalDeductions, 2);

        return [
            'differential'     => $differential,
            'per_month'        => $perMonth,
            'total_earned'     => $totalEarned,
            'total_gsis'       => $totalGsis,
            'total_phic'       => $totalPhic,
            'total_pagibig'    => $totalPagIbig,
            'total_wht'        => $totalWht,
            'total_deductions' => $totalDeductions,
            'net_amount'       => $netAmount,
            'wht_rate'         => $whtRate,
            'effectivity_from' => $from->toDateString(),
            'effectivity_to'   => $to->toDateString(),
            'old_salary'       => $old_salary,
            'new_salary'       => $new_salary,
        ];
    }
}

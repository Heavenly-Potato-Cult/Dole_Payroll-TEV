<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Carbon\Carbon;

/**
 * SalaryDifferentialService
 *
 * Computes salary differential payroll for promotions, step increments,
 * and salary adjustments — exactly matching the 01C Excel WP sheet logic.
 *
 * Formula reference (WP sheet):
 *   differential      = new_salary - old_salary
 *   calendar_days      : ALL days in the segment, Sundays included —
 *                        segmentEnd->day - segmentStart->day + 1 for a
 *                        partial month, or the full days-in-month count
 *                        for a full month. This is the same "calendar,
 *                        not working days" rule NewlyHiredPayrollService
 *                        uses for its GSIS base (see calendarDays()).
 *   partial month      : earned = ROUND(differential × calendar_days / 22, 2)
 *   full month         : earned = differential  (calendar_days = days in month)
 *   gsis_base (/month) = earned   (deductions are computed on the
 *                        DIFFERENTIAL AMOUNT ONLY, never the full basic
 *                        salary — kept as an explicit named variable,
 *                        mirroring NewlyHiredPayrollService::compute()'s
 *                        gsis_base, even though it numerically equals
 *                        $earned here)
 *   per-month GSIS     = ROUND(gsis_base × 0.09, 2)
 *   phic_base (/month) = earned
 *   per-month PHIC     = ROUND(phic_base × 0.05 / 2, 2)
 *   per-month Pag-IB   = 200.00 (fixed per month)
 *   WHT                = ROUND(total_earned × wht_rate, 2)   [employee rate or 20%]
 *   total_deductions   = sum(GSIS) + sum(PHIC) + sum(Pag-IBIG) + WHT
 *   pera_amount        = payroll-officer-entered flat PERA back-pay
 *                        adjustment for the whole batch (optional — see
 *                        $peraAdjustment). Not GSIS/PHIC/WHT-able, matching
 *                        PERA's treatment in NewlyHiredPayrollService.
 *                        Typically capped around the standard ₱2,000 PERA
 *                        ceiling, but that's not enforced here — the
 *                        preparer may need to exceed it in edge cases.
 *   gross_amount       = total_earned + pera_amount
 *   net_amount         = gross_amount - total_deductions
 *
 * NOTE: Deductions (GSIS, PhilHealth, Pag-IBIG, WHT) are computed on the
 *       DIFFERENTIAL AMOUNT ONLY — never on the full basic salary, and
 *       never on the PERA adjustment either.
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
     * @param  float|null $peraAdjustment  Optional payroll-officer-entered flat
     *         PERA back-pay figure for the whole batch (not per month). When
     *         set, it's added once to gross/net and is never subject to
     *         GSIS, PhilHealth, or WHT — same treatment PERA gets in
     *         NewlyHiredPayrollService. Null means "no PERA adjustment on
     *         this batch" (the common case).
     * @return array{
     *   differential:      float,
     *   per_month:         array<int, array{month_label: string, calendar_days: int,
     *                          earned: float, gsis_base: float, gsis: float,
     *                          phic_base: float, phic: float, pagibig: float,
     *                          is_full: bool}>,
     *   total_earned:      float,
     *   total_gsis_base:   float,
     *   total_gsis:        float,
     *   total_phic_base:   float,
     *   total_phic:        float,
     *   total_pagibig:     float,
     *   total_wht:         float,
     *   total_deductions:  float,
     *   pera_amount:       float,
     *   pera_overridden:   bool,
     *   gross_amount:      float,
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
        ?float   $peraAdjustment = null,
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

        $perMonth       = [];
        $totalEarned    = 0.0;
        $totalGsisBase  = 0.0;
        $totalGsis      = 0.0;
        $totalPhicBase  = 0.0;
        $totalPhic      = 0.0;
        $totalPagIbig   = 0.0;

        // ── Iterate month by month across the effectivity range ───────────
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to->copy()->startOfMonth())) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            // Clamp to the effectivity window
            $segmentStart = $monthStart->lt($from) ? $from->copy() : $monthStart->copy();
            $segmentEnd   = $monthEnd->gt($to)     ? $to->copy()   : $monthEnd->copy();

            // Calendar days in segment — ALL days, Sundays included, no
            // weekday exclusions. This is the same "calendar, not working
            // days" rule NewlyHiredPayrollService::calendarDays() applies.
            $daysInMonth   = $monthEnd->day;               // total days in month
            $calendarDays  = $segmentEnd->day - $segmentStart->day + 1;

            // Full month: segment covers the entire month
            $isFullMonth = ($segmentStart->day === 1 && $segmentEnd->day === $daysInMonth);

            if ($isFullMonth) {
                $earned       = round($differential, 2);
                $calendarDays = $daysInMonth;
            } else {
                $earned = round($differential * $calendarDays / self::DENOMINATOR, 2);
            }

            // ── Deduction bases, explicit (calendar-day-prorated earned
            // differential — never the full basic salary) ─────────────────
            $gsisBase = $earned;
            $phicBase = $earned;

            $gsis    = round($gsisBase * $gsisRate, 2);
            $phic    = round($phicBase * $phicRate, 2);
            $pagIbig = $pagibigFixed;

            $perMonth[] = [
                'month_label'   => $cursor->format('M Y'),
                'calendar_days' => $calendarDays,
                'earned'        => $earned,
                'gsis_base'     => $gsisBase,
                'gsis'          => $gsis,
                'phic_base'     => $phicBase,
                'phic'          => $phic,
                'pagibig'       => $pagIbig,
                'is_full'       => $isFullMonth,
            ];

            $totalEarned   += $earned;
            $totalGsisBase += $gsisBase;
            $totalGsis     += $gsis;
            $totalPhicBase += $phicBase;
            $totalPhic     += $phic;
            $totalPagIbig  += $pagIbig;

            $cursor->addMonth();
        }

        // Round accumulated totals to avoid float drift
        $totalEarned   = round($totalEarned, 2);
        $totalGsisBase = round($totalGsisBase, 2);
        $totalGsis     = round($totalGsis, 2);
        $totalPhicBase = round($totalPhicBase, 2);
        $totalPhic     = round($totalPhic, 2);
        $totalPagIbig  = round($totalPagIbig, 2);

        // WHT is applied on total differential earned only — PERA
        // adjustment (below) never enters the WHT, GSIS, or PHIC base.
        $totalWht        = round($totalEarned * $whtRate, 2);
        $totalDeductions = round($totalGsis + $totalPhic + $totalPagIbig + $totalWht, 2);

        // ── Optional flat PERA back-pay adjustment ─────────────────────────
        $peraIsOverridden = $peraAdjustment !== null;
        $peraAmount       = $peraIsOverridden ? round($peraAdjustment, 2) : 0.0;

        $grossAmount = round($totalEarned + $peraAmount, 2);
        $netAmount   = round($grossAmount - $totalDeductions, 2);

        return [
            'differential'     => $differential,
            'per_month'        => $perMonth,
            'total_earned'     => $totalEarned,
            'total_gsis_base'  => $totalGsisBase,
            'total_gsis'       => $totalGsis,
            'total_phic_base'  => $totalPhicBase,
            'total_phic'       => $totalPhic,
            'total_pagibig'    => $totalPagIbig,
            'total_wht'        => $totalWht,
            'total_deductions' => $totalDeductions,
            'pera_amount'      => $peraAmount,
            'pera_overridden'  => $peraIsOverridden,
            'gross_amount'     => $grossAmount,
            'net_amount'       => $netAmount,
            'wht_rate'         => $whtRate,
            'effectivity_from' => $from->toDateString(),
            'effectivity_to'   => $to->toDateString(),
            'old_salary'       => $old_salary,
            'new_salary'       => $new_salary,
        ];
    }
}

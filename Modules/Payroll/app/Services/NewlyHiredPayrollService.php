<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * NewlyHiredPayrollService
 *
 * Computes pro-rated salary for a newly hired / transferee employee.
 *
 * Formulas derived from 01B-Newly-Hired-or-Transferee-Employee.xlsx (WP sheet):
 *
 *   working_days  = weekday count from effectivity_date to cutoff_end (inclusive)
 *   salary_earned = ROUND((basic_salary / 22) * working_days, 2)
 *   pera_earned   = ROUND((pera / 22) * working_days, 2)
 *                   — OR the payroll officer's manually entered figure
 *                   (see $peraOverride), when the pro-rated amount doesn't
 *                   match the standard ₱2,000 PERA cap.
 *   lwop_salary   = ROUND((basic_salary / 22), 2) * lwop_days
 *   lwop_pera     = ROUND((pera / 22), 2) * lwop_days
 *                   — 0 whenever pera_earned is manually overridden; the
 *                   entered figure is treated as the final earned amount.
 *   net_earned    = (salary_earned − lwop_salary) + (pera_earned − lwop_pera)
 *
 *   calendar_days = ALL days (including Sundays) from effectivity_date to
 *                   cutoff_end, inclusive — distinct from working_days,
 *                   which only counts weekdays.
 *   gsis_base     = ROUND((basic_salary / 22) * calendar_days, 2)
 *   GSIS PS       = ROUND(gsis_base * rate, 2)     ← 9% employee share by default
 *                   GSIS premiums are pro-rated on calendar time-in-service
 *                   for the period, not the weekday-only working_days figure
 *                   used for salary_earned above.
 *   PHIC          = 0.00   (not deducted for newly hired — govt share only)
 *   Pag-IBIG I    = 0.00   (₱200 is government share, not deducted from net)
 *   WHT           = 0.00   (annualized — insufficient history for newly hired)
 *
 *   total_deductions = GSIS PS
 *   net_amount       = net_earned − total_deductions
 *
 * Government shares (for reference / reporting only, NOT deducted from net):
 *   gsis_gs   = ROUND(salary_earned * 0.12, 2)     (12% employer share)
 *   phic_gs   = ROUND(salary_earned * 0.05 / 2, 2) (5% total, half employer)
 *   hdmf_gs   = 200.00 (fixed employer Pag-IBIG contribution)
 */
class NewlyHiredPayrollService
{
    const GSIS_EMPLOYEE_RATE = 0.09;
    const GSIS_GOVERNMENT_RATE = 0.12;

    /**
     * Weekday count from effectivity_date to cutoff_end (inclusive).
     * Returns 0 if effectivity falls after cutoff_end.
     *
     * Extracted so callers (e.g. the controller, to pro-rate optional
     * allowance lines before calling compute()) can get the same
     * working-day figure compute() uses internally, without duplicating
     * the calendar logic.
     */
    public function workingDays(string $effectivity_date, string $cutoff_end): int
    {
        $startDate     = Carbon::parse($effectivity_date);
        $cutoffEndDate = Carbon::parse($cutoff_end);

        if ($startDate->gt($cutoffEndDate)) {
            return 0;
        }

        $count  = 0;
        $period = CarbonPeriod::create($startDate, $cutoffEndDate);
        foreach ($period as $day) {
            if ($day->isWeekday()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calendar day count (ALL days, including Saturdays and Sundays) from
     * effectivity_date to cutoff_end, inclusive. Used only for the GSIS
     * Personal Share base — GSIS premiums are pro-rated on actual calendar
     * time-in-service for a partial period, unlike salary_earned/pera_earned
     * which use the weekday-only working_days figure above.
     *
     * Example: effectivity 2026-06-16, cutoff_end 2026-06-30 → 15 calendar
     * days (16 through 30, inclusive), vs. workingDays() which would drop
     * any Saturdays/Sundays in that span.
     */
    public function calendarDays(string $effectivity_date, string $cutoff_end): int
    {
        $startDate     = Carbon::parse($effectivity_date);
        $cutoffEndDate = Carbon::parse($cutoff_end);

        if ($startDate->gt($cutoffEndDate)) {
            return 0;
        }

        return $startDate->diffInDays($cutoffEndDate) + 1;
    }

    /**
     * Compute pro-rated payroll for a newly hired employee.
     *
     * @param  Employee  $employee
     * @param  string    $effectivity_date  ISO date string (YYYY-MM-DD) — first day of work
     * @param  string    $cutoff_start      ISO date — start of the payroll cut-off period
     * @param  string    $cutoff_end        ISO date — last day of the cut-off period (inclusive)
     * @param  int       $lwop_days         Leave Without Pay days (whole days only)
     * @param  int       $tardiness_minutes Total tardiness/undertime in minutes (currently unused in net calc)
     * @param  float|null $gsisRate         Optional custom GSIS rate (default 9%)
     * @param  array<int, array{allowance_type_id:int, code:string, name:string, full_amount:float, amount:float}>  $allowanceLines
     *         Pre-prorated allowance lines (see AllowanceService::proRateLines()).
     *         PERA must not appear here — it stays a first-class column below.
     *         GSIS PS is computed on the calendar-day gsis_base only; allowances
     *         here are never part of the GSIS base (confirmed — matches PERA's
     *         existing treatment).
     * @param  float|null $peraOverride     Optional payroll-officer-entered PERA
     *         Earned figure. When set, this replaces the auto pro-rated
     *         (pera / 22 × working_days) calculation exactly, and LWOP is not
     *         separately deducted from it — the entered figure is taken as
     *         final. Use when the pro-rated amount doesn't match the standard
     *         ₱2,000 PERA cap.
     * @return array
     */
    public function compute(
        Employee $employee,
        string   $effectivity_date,
        string   $cutoff_start,
        string   $cutoff_end,
        int      $lwop_days         = 0,
        int      $tardiness_minutes = 0,
        ?float   $gsisRate         = null,
        array    $allowanceLines   = [],
        ?float   $peraOverride     = null
    ): array {
        // Use custom GSIS rate if provided, otherwise use default
        $gsisRate = $gsisRate ?? self::GSIS_EMPLOYEE_RATE;

        $working_days = $this->workingDays($effectivity_date, $cutoff_end);
        $calendar_days = $this->calendarDays($effectivity_date, $cutoff_end);

        $basic = (float) $employee->basic_salary;
        $pera  = (float) $employee->pera;

        // ── Core earnings ─────────────────────────────────────────────────
        $salary_earned = round(($basic / 22) * $working_days, 2);

        // PERA Earned — manual override (payroll officer's figure) takes
        // priority over the auto pro-rated calculation. See docblock above.
        $pera_is_overridden = $peraOverride !== null;
        $pera_earned = $pera_is_overridden
            ? round($peraOverride, 2)
            : round(($pera / 22) * $working_days, 2);

        // ── LWOP deductions ───────────────────────────────────────────────
        // LWOP is not separately applied to PERA when it's been manually
        // overridden — the entered figure is treated as final.
        $daily_basic    = round($basic / 22, 2);
        $daily_pera     = round($pera  / 22, 2);
        $lwop_salary    = $daily_basic * $lwop_days;
        $lwop_pera      = $pera_is_overridden ? 0.0 : ($daily_pera * $lwop_days);
        $lwop_deduction = round($lwop_salary + $lwop_pera, 2);

        // ── Allowances (RATA/etc — optional, already pro-rated by caller) ──
        // PERA is intentionally excluded from $allowanceLines (see docblock)
        // so it is never double-counted against pera_earned above.
        $allowances_earned = round(
            array_sum(array_map(fn ($l) => (float) $l['amount'], $allowanceLines)),
            2
        );

        // ── Net earned (after LWOP) ───────────────────────────────────────
        $net_earned = ($salary_earned - $lwop_salary) + ($pera_earned - $lwop_pera) + $allowances_earned;
        $net_earned = round($net_earned, 2);

        // ── Mandatory deductions ──────────────────────────────────────────
        // GSIS is pro-rated on CALENDAR days (Sundays included), not the
        // weekday-only working_days used for salary_earned:
        //   (basic / 22) * calendar_days  => gsis_base
        //   gsis_base * rate              => GSIS amount
        // NOTE: allowances (and PERA) are never GSIS-able, matching the
        // existing PERA treatment.
        $gsis_base = round(($basic / 22) * $calendar_days, 2);
        $gsis_ps   = round($gsis_base * $gsisRate, 2);
        $phic    = 0.00;                                 // Not deducted for newly hired
        $pagibig = 0.00;                                 // ₱200 is govt share only
        $wht     = 0.00;                                 // Zero for newly hired (no history)

        $total_deductions = $gsis_ps; // Only GSIS PS hits the employee's net

        $net_amount = round($net_earned - $total_deductions, 2);

        // ── Government shares (for reference only — not deducted) ─────────
        $gsis_gs  = round($salary_earned * self::GSIS_GOVERNMENT_RATE, 2);  // 12% employer share
        $phic_gs  = round($salary_earned * 0.025,  2);  // 2.5% employer PhilHealth share
        $hdmf_gs  = 200.00;                              // Fixed Pag-IBIG employer share

        return [
            // Input summary
            'working_days'     => $working_days,
            'calendar_days'    => $calendar_days,
            'basic_salary'     => $basic,
            'pera'             => $pera,

            // Earnings
            'salary_earned'    => $salary_earned,
            'pera_earned'      => $pera_earned,
            'pera_overridden'  => $pera_is_overridden,
            'allowance_lines'  => $allowanceLines,      // as passed in, for display
            'allowances_earned'=> $allowances_earned,
            'net_earned'       => $net_earned,   // gross before mandatory deductions (now includes allowances)

            // LWOP
            'lwop_days'        => $lwop_days,
            'lwop_salary'      => round($lwop_salary, 2),
            'lwop_pera'        => round($lwop_pera,   2),
            'lwop_deduction'   => $lwop_deduction,

            // Deductions (employee share — deducted from net)
            'gsis_base'        => $gsis_base,
            'gsis_ps'          => $gsis_ps,
            'phic'             => $phic,
            'pagibig'          => $pagibig,
            'wht'              => $wht,
            'total_deductions' => $total_deductions,

            // Final
            'net_amount'       => $net_amount,

            // Government shares (reference only)
            'gsis_gs'          => $gsis_gs,
            'phic_gs'          => $phic_gs,
            'hdmf_gs'          => $hdmf_gs,
        ];
    }
}

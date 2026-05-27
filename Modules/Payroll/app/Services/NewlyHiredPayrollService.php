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
 *   lwop_salary   = ROUND((basic_salary / 22), 2) * lwop_days
 *   lwop_pera     = ROUND((pera / 22), 2) * lwop_days
 *   net_earned    = (salary_earned − lwop_salary) + (pera_earned − lwop_pera)
 *
 *   GSIS PS       = ROUND(salary_earned * 0.09, 2)     ← 9% employee share
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
     * Compute pro-rated payroll for a newly hired employee.
     *
     * @param  Employee  $employee
     * @param  string    $effectivity_date  ISO date string (YYYY-MM-DD) — first day of work
     * @param  string    $cutoff_start      ISO date — start of the payroll cut-off period
     * @param  string    $cutoff_end        ISO date — last day of the cut-off period (inclusive)
     * @param  int       $lwop_days         Leave Without Pay days (whole days only)
     * @param  int       $tardiness_minutes Total tardiness/undertime in minutes (currently unused in net calc)
     * @param  float|null $gsisRate         Optional custom GSIS rate (default 9%)
     * @return array
     */
    public function compute(
        Employee $employee,
        string   $effectivity_date,
        string   $cutoff_start,
        string   $cutoff_end,
        int      $lwop_days         = 0,
        int      $tardiness_minutes = 0,
        ?float   $gsisRate         = null
    ): array {
        $startDate     = Carbon::parse($effectivity_date);
        $cutoffEndDate = Carbon::parse($cutoff_end);

        // Use custom GSIS rate if provided, otherwise use default
        $gsisRate = $gsisRate ?? self::GSIS_EMPLOYEE_RATE;

        // ── Working days: weekdays from effectivity_date to cutoff_end ────
        // If effectivity falls after cut-off end, working_days = 0
        $working_days = 0;
        if ($startDate->lte($cutoffEndDate)) {
            $period = CarbonPeriod::create($startDate, $cutoffEndDate);
            foreach ($period as $day) {
                if ($day->isWeekday()) {
                    $working_days++;
                }
            }
        }

        $basic = (float) $employee->basic_salary;
        $pera  = (float) $employee->pera;

        // ── Core earnings ─────────────────────────────────────────────────
        $salary_earned = round(($basic / 22) * $working_days, 2);
        $pera_earned   = round(($pera  / 22) * $working_days, 2);

        // ── LWOP deductions ───────────────────────────────────────────────
        $daily_basic    = round($basic / 22, 2);
        $daily_pera     = round($pera  / 22, 2);
        $lwop_salary    = $daily_basic * $lwop_days;
        $lwop_pera      = $daily_pera  * $lwop_days;
        $lwop_deduction = round($lwop_salary + $lwop_pera, 2);

        // ── Net earned (after LWOP) ───────────────────────────────────────
        $net_earned = ($salary_earned - $lwop_salary) + ($pera_earned - $lwop_pera);
        $net_earned = round($net_earned, 2);

        // ── Mandatory deductions ──────────────────────────────────────────
        // GSIS for incomplete periods:
        //   (basic / 22) * working_days  => salary_earned
        //   salary_earned * rate         => GSIS amount
        $gsis_ps = round($salary_earned * $gsisRate, 2);
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
            'basic_salary'     => $basic,
            'pera'             => $pera,

            // Earnings
            'salary_earned'    => $salary_earned,
            'pera_earned'      => $pera_earned,
            'net_earned'       => $net_earned,   // gross before mandatory deductions

            // LWOP
            'lwop_days'        => $lwop_days,
            'lwop_salary'      => round($lwop_salary, 2),
            'lwop_pera'        => round($lwop_pera,   2),
            'lwop_deduction'   => $lwop_deduction,

            // Deductions (employee share — deducted from net)
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

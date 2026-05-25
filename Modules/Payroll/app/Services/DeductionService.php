<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\DeductionType;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollBatch;
use Carbon\Carbon;

/**
 * DeductionService
 *
 * Resolves the full set of deduction line items for a single employee
 * for one payroll cut-off.
 *
 * ── Three-Tier Resolution Order ──────────────────────────────────────────
 *
 *  Tier 1 — Formula-driven  (is_computed = true)
 *    a. If is_locked = true AND override_amount is set → use override_amount (global fixed).
 *    b. If override_amount is set (not locked) → use override_amount (Enhancement #1).
 *    c. Otherwise → run the salary-based formula.
 *
 *  Tier 2 — Locked global  (is_computed = false, isEffectivelyLocked() = true)
 *    Use default_amount directly. No per-employee enrollment lookup.
 *    Loan-category types are exempt — they always fall to Tier 3.
 *
 *  Tier 3 — Per-employee  (is_computed = false, isEffectivelyLocked() = false)
 *    Look up the employee's active enrollment for this cut-off date.
 *    If found, use enrollment.amount. Otherwise 0.
 * ─────────────────────────────────────────────────────────────────────────
 */
class DeductionService
{
    const DENOMINATOR = 22;

    /**
     * Resolve all deduction line items for one employee in one payroll batch.
     *
     * @param  Employee     $employee
     * @param  PayrollBatch $batch
     * @param  float        $ytdGross   Year-to-date gross before this cut-off (for WHT)
     * @return array        Each element: [deduction_type_id, code, name, amount, is_overridden, is_global]
     */
    public function resolveDeductions(Employee $employee, PayrollBatch $batch, float $ytdGross = 0.0): array
    {
        $basicMonthly = (float) $employee->basic_monthly_salary;
        $peraMonthly  = (float) $employee->pera_amount;
        $payrollDate  = Carbon::create($batch->period_year, $batch->period_month, 1)->toDateString();

        $allTypes = DeductionType::active()->ordered()->get();

        // Pre-load per-employee enrollments (Tier 3 only)
        $enrollments = $employee->deductionEnrollments()
            ->with('deductionType')
            ->activeOn($payrollDate)
            ->get()
            ->keyBy(fn ($e) => $e->deductionType->code);

        // ── Pre-compute formula amounts ───────────────────────────────────
        // These are computed once and used only when the formula path is taken.
        $formulaAmounts = [
            'PAG_IBIG_1'           => $this->computePagibig1($basicMonthly),
            'PHILHEALTH'           => $this->computePhilHealth($basicMonthly),
            'GSIS_LIFE_RETIREMENT' => $this->computeGsisLife($basicMonthly),
            'WITHHOLDING_TAX'      => $this->computeWithholdingTax(
                                          $employee, $basicMonthly, $peraMonthly,
                                          $ytdGross, $batch
                                      ),
        ];

        $lines = [];

        foreach ($allTypes as $type) {
            $amount       = 0.00;
            $isOverridden = false;
            $isGlobal     = false;

            // ── Tier 1: Formula-driven ────────────────────────────────────
            if ($type->is_computed) {
                if ($type->isEffectivelyLocked() && $type->override_amount !== null) {
                    // Locked + override → global fixed amount, bypasses formula
                    $amount       = (float) $type->override_amount;
                    $isOverridden = true;
                    $isGlobal     = true;
                } elseif ($type->isOverridden()) {
                    // Not locked but override set → per-type manual adjustment (Enhancement #1)
                    $amount       = (float) $type->override_amount;
                    $isOverridden = true;
                } else {
                    // Normal formula path
                    $amount = $formulaAmounts[$type->code] ?? 0.00;
                }

            // ── Tier 2: Locked global manual ─────────────────────────────
            } elseif ($type->isEffectivelyLocked()) {
                if ($type->default_amount !== null) {
                    $amount   = (float) $type->default_amount;
                    $isGlobal = true;
                }
                // If no default_amount configured yet → amount stays 0, skip.

            // ── Tier 3: Per-employee enrollment ──────────────────────────
            } elseif (isset($enrollments[$type->code])) {
                $amount = (float) $enrollments[$type->code]->amount;
            }

            if ($amount > 0.00) {
                $lines[] = [
                    'deduction_type_id' => $type->id,
                    'code'              => $type->code,
                    'name'              => $type->name,
                    'amount'            => round($amount, 2),
                    // is_overridden: payslip view shows asterisk for manual overrides
                    'is_overridden'     => $isOverridden,
                    // is_global: payslip view may show a different indicator for
                    // globally-applied amounts (e.g. a globe icon)
                    'is_global'         => $isGlobal,
                ];
            }
        }

        return $lines;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Computed Government-Mandatory Deduction Helpers
    //  All return the PER CUT-OFF (semi-monthly) amount.
    // ═══════════════════════════════════════════════════════════════════

    public function computePagibig1(float $basicMonthly): float
    {
        $rate      = $basicMonthly <= 1_500.00 ? 0.01 : 0.02;
        $monthlyEE = min(round($basicMonthly * $rate, 2), 100.00);
        return round($monthlyEE / 2, 2);
    }

    public function computePhilHealth(float $basicMonthly): float
    {
        $monthlyPremium = max(500.00, min(round($basicMonthly * 0.05, 2), 5_000.00));
        $monthlyEE      = round($monthlyPremium / 2, 2);
        return round($monthlyEE / 2, 2);
    }

    public function computeGsisLife(float $basicMonthly): float
    {
        $monthlyPS = round($basicMonthly * 0.09, 2);
        return round($monthlyPS / 2, 2);
    }

    public function computeWithholdingTax(
        Employee     $employee,
        float        $basicMonthly,
        float        $peraMonthly,
        float        $ytdGross,
        PayrollBatch $batch
    ): float {
        $cutoffNumber = ($batch->period_month - 1) * 2 + ($batch->cutoff === '1st' ? 1 : 2);
        $cutoffNumber = max(1, $cutoffNumber);

        $thisGross        = round(($basicMonthly + $peraMonthly) / 2, 2);
        $accumulatedGross = $ytdGross + $thisGross;
        $projectedAnnual  = round($accumulatedGross / $cutoffNumber * 24, 2);

        $annualGSIS = round($basicMonthly * 0.09 * 12, 2);
        $annualPHIC = max(6_000.00, min(round($basicMonthly * 0.05 * 12, 2), 60_000.00));
        $annualHDMF = min(round($basicMonthly * 0.02 * 12, 2), 1_200.00);

        $taxableIncome = max(0.00, $projectedAnnual - $annualGSIS - $annualPHIC - $annualHDMF);
        $annualTax     = $this->birGraduatedTax($taxableIncome);

        return max(0.00, round($annualTax / 24, 2));
    }

    public function birGraduatedTax(float $taxableIncome): float
    {
        return match (true) {
            $taxableIncome <= 250_000   => 0.00,
            $taxableIncome <= 400_000   => round(($taxableIncome - 250_000) * 0.15, 2),
            $taxableIncome <= 800_000   => round(22_500 + ($taxableIncome - 400_000) * 0.20, 2),
            $taxableIncome <= 2_000_000 => round(102_500 + ($taxableIncome - 800_000) * 0.25, 2),
            $taxableIncome <= 8_000_000 => round(402_500 + ($taxableIncome - 2_000_000) * 0.30, 2),
            default                     => round(2_202_500 + ($taxableIncome - 8_000_000) * 0.35, 2),
        };
    }
}

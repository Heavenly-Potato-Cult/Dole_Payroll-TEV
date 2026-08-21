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
 *    If percentage is set → calculate as percentage of basic salary.
 *    Otherwise use default_amount directly. No per-employee enrollment lookup.
 *    Loan-category types are exempt — they always fall to Tier 3.
 *
 *  Tier 3 — Per-employee  (is_computed = false, isEffectivelyLocked() = false)
 *    Look up the employee's active enrollment for this cut-off date.
 *    If found and enrollment has amount → use enrollment.amount.
 *    If found but enrollment has no amount and type has percentage → calculate as percentage.
 *    Otherwise 0.
 *
 * ── Formula Rate Columns ─────────────────────────────────────────────────
 *
 *  computePagibig1(), computePhilHealth(), and computeGsisLife() now accept
 *  the DeductionType model and read their rates from the formula_rate_*
 *  columns added by the 2026_05_28_000001 migration.
 *
 *  If a column is null the method falls back to the hardcoded constant below
 *  so that existing installations with no seeded values continue to work
 *  without any data changes.
 *
 *  Withholding Tax (computeWithholdingTax) remains fully hardcoded — the
 *  BIR graduated table has too many interdependent brackets to expose safely
 *  in a UI. A developer must update those brackets directly in this class.
 *
 * ── Fix Log ──────────────────────────────────────────────────────────────
 *
 *  2026-06-10  Fix 1: Added EMPLOYER_ONLY_CODES skip list.
 *              GSIS_GOVERNMENT_SHARE is the employer's 12% contribution —
 *              it must never be deducted from the employee's net pay.
 *              Previously it was resolving through Tier 2 (locked, percentage
 *              set to 12%) and being summed into total_deductions, causing
 *              the payslip total to be overstated by ~₱1,177 per employee.
 *
 *  2026-06-10  Fix 2: Added PAG-IBIG deduplication via PAGIBIG_GROUP_CODES.
 *              Both PAG_IBIG_1 and PAGIBIG_1 deduction type records are
 *              active in the DB (legacy + new naming). Both resolved
 *              independently to ₱50 each, doubling the PAG-IBIG deduction.
 *              Permanent fix: run — UPDATE deduction_types SET is_active = 0
 *              WHERE code = 'PAGIBIG_1'; — then remove this dedup logic.
 *
 *  2026-06-10  Fix 2a: Corrected PAG-IBIG dedup guard. The original guard
 *              only set $pagibigGroupResolved = true inside the `if ($amount > 0)`
 *              block. If the first code resolved to 0.00 (e.g. no enrollment for
 *              Tier 3), the flag was never set and the alias code still slipped
 *              through. Fixed by moving the flag setter to immediately after
 *              first encounter of any PAG-IBIG group code, before amount
 *              resolution.
 *
 *  2026-06-10  Fix 3: WHT always returning 0.00 — fixed in
 *              PayrollComputationService::computeBatch(). ytdGross is now
 *              queried from prior payroll_entries before each computeEntry() call.
 *
 *  2026-07-07  Added assignment_scope guard. Tier 1/2 types with
 *              assignment_scope = 'specific' are now skipped for employees
 *              not present in the deduction_type_employee pivot (see
 *              DeductionType::appliesToEmployeeId()). This is the structural
 *              fix for duplicate type pairs (e.g. two "Pag-IBIG 1" rows,
 *              one per share) that must never both apply to the same employee.
 *
 *  2026-08-19  Fix 4: WHT's semi-monthly gross was reading employee->pera_amount
 *              (the raw legacy column) directly, bypassing AllowanceService
 *              entirely — the same class of bug fixed for Newly Hired
 *              pro-rated payroll the same day. An employee with a standing
 *              PERA enrollment or a released assignment override in the
 *              Allowance module had their WHT computed against a stale PERA
 *              figure. resolveDeductions() now accepts an optional
 *              $peraMonthly parameter — PayrollComputationService passes the
 *              same AllowanceService-resolved (or carried-over) figure it
 *              already uses for the payroll entry's own PERA line, so both
 *              stay in sync. Falls back to employee->pera_amount only when
 *              the caller omits the parameter.
 * ─────────────────────────────────────────────────────────────────────────
 */
class DeductionService
{
    // ── Hardcoded fallback constants ──────────────────────────────────────
    // Used when the corresponding formula_rate_* column on DeductionType is null.
    // These match the statutory rates in effect as of May 2026.

    // PAG-IBIG I
    const PAGIBIG_RATE          = 0.02;    // 2 % standard rate
    const PAGIBIG_RATE_LOW      = 0.01;    // 1 % for salary ≤ threshold
    const PAGIBIG_THRESHOLD     = 1500.00; // ₱1,500 salary threshold
    const PAGIBIG_MONTHLY_CAP   = 100.00;  // EE share capped at ₱100/month

    // PhilHealth
    const PHILHEALTH_RATE            = 0.05;    // 5 % total premium rate
    const PHILHEALTH_MONTHLY_FLOOR   = 500.00;  // ₱500 minimum monthly premium
    const PHILHEALTH_MONTHLY_CEILING = 5000.00; // ₱5,000 maximum monthly premium

    // GSIS Life & Retirement
    const GSIS_RATE = 0.09; // 9 % personal share

    const DENOMINATOR = 22;

    /**
     * Deduction type codes that represent the EMPLOYER'S share.
     *
     * These are stored in deduction_types for reporting/GL purposes but must
     * NEVER be deducted from the employee's net pay. They are skipped entirely
     * during employee deduction resolution.
     *
     * Add any future employer-side codes here (e.g. PHILHEALTH_EMPLOYER if
     * you later split PhilHealth into separate EE/ER records).
     */
    const EMPLOYER_ONLY_CODES = [
        'GSIS_GOVERNMENT_SHARE',  // 12% employer GSIS contribution — not employee's deduction
        'PHILHEALTH_EMPLOYER',    // 50% PhilHealth employer share — if ever split
    ];

    /**
     * Groups of legacy/alias PAG-IBIG codes that resolve to the same deduction.
     *
     * The dedup guard marks the group as resolved on FIRST ENCOUNTER of any
     * code in the group — regardless of whether it produces amount > 0.
     * This prevents the alias from sneaking through when the first code
     * resolves to zero (e.g. Tier 3 employee with no enrollment record).
     *
     * Permanent fix: deactivate the old code in the DB:
     *   UPDATE deduction_types SET is_active = 0 WHERE code = 'PAGIBIG_1';
     * Once done, this constant has no effect but is harmless to leave in.
     */
    const PAGIBIG_GROUP_CODES = [
        'PAG_IBIG_1',
        'PAGIBIG_1',
    ];

    // ─────────────────────────────────────────────────────────────────────

    /**
     * Resolve all deduction line items for one employee in one payroll batch.
     *
     * Returns only EMPLOYEE-side deductions. Employer-only codes (see
     * EMPLOYER_ONLY_CODES) are silently skipped.
     *
     * @param  Employee     $employee
     * @param  PayrollBatch $batch
     * @param  float        $ytdGross   Year-to-date gross BEFORE this cut-off (for WHT).
     *                                  Must be the sum of gross_income from all prior
     *                                  payroll_entries for this employee in the same
     *                                  calendar year. Passing 0.0 causes WHT = 0 for all.
     * @param  int|null     $daysWorked Days worked in the period (for prorated GSIS)
     * @param  int          $totalDays  Total working days in the period (default 22)
     * @param  float|null   $peraMonthly Monthly PERA base for the WHT gross calculation,
     *                                  resolved by the caller via AllowanceService (see
     *                                  PayrollComputationService::computeEntry(), which
     *                                  passes the same $peraEarned figure it resolves for
     *                                  the allowances line, respecting that method's own
     *                                  apply/force/carry-over semantics). Falls back to
     *                                  employee->pera_amount when omitted, so any caller
     *                                  not yet updated keeps working unchanged — but that
     *                                  fallback reads the raw legacy column directly and
     *                                  goes stale once a standing PERA enrollment or
     *                                  assignment override exists for the employee.
     * @return array        Each element: [deduction_type_id, code, name, amount, is_overridden, is_global]
     */
    public function resolveDeductions(
        Employee     $employee,
        PayrollBatch $batch,
        float        $ytdGross    = 0.0,
        ?int         $daysWorked  = null,
        int          $totalDays   = 22,
        ?float       $peraMonthly = null
    ): array {
        $basicMonthly = (float) $employee->basic_monthly_salary;
        $peraMonthly  = $peraMonthly ?? (float) $employee->pera_amount;
        $payrollDate  = Carbon::create($batch->period_year, $batch->period_month, 1)->toDateString();

        // Eager-load the assignment pivot scoped to THIS employee only, so
        // DeductionType::appliesToEmployeeId() below reads the loaded
        // relation in-memory instead of firing a fresh query per type.
        // This keeps the cost at one extra query per employee (not per
        // employee × type) — see DeductionType::appliesToEmployeeId().
        $allTypes = DeductionType::active()->ordered()
            ->with(['assignedEmployees' => function ($q) use ($employee) {
                $q->where('employees.id', $employee->id);
            }])
            ->get();

        // Pre-load per-employee enrollments (Tier 3 only)
        $enrollments = $employee->deductionEnrollments()
            ->with('deductionType')
            ->activeOn($payrollDate)
            ->get()
            ->keyBy(fn ($e) => $e->deductionType->code);

        // ── Pre-compute formula amounts ───────────────────────────────────
        // Computed per type so each method can read its own DB-configured rates.
        $computedTypes = $allTypes->keyBy('code');

        $formulaAmounts = [
            'PAG_IBIG_1' => $this->computePagibig1(
                $basicMonthly,
                $computedTypes->get('PAG_IBIG_1') ?? $computedTypes->get('PAGIBIG_1')
            ),
            'PAGIBIG_1' => $this->computePagibig1(
                $basicMonthly,
                $computedTypes->get('PAGIBIG_1') ?? $computedTypes->get('PAG_IBIG_1')
            ),
            'PHILHEALTH' => $this->computePhilHealth(
                $basicMonthly,
                $computedTypes->get('PHILHEALTH')
            ),
            'GSIS_LIFE_RETIREMENT' => $this->computeGsisLife(
                $basicMonthly,
                $computedTypes->get('GSIS_LIFE_RETIREMENT') ?? $computedTypes->get('GSIS_LIFE_RET'),
                $daysWorked,
                $totalDays
            ),
            'GSIS_LIFE_RET' => $this->computeGsisLife(
                $basicMonthly,
                $computedTypes->get('GSIS_LIFE_RET') ?? $computedTypes->get('GSIS_LIFE_RETIREMENT'),
                $daysWorked,
                $totalDays
            ),
            'WITHHOLDING_TAX' => $this->computeWithholdingTax(
                $employee, $basicMonthly, $peraMonthly, $ytdGross, $batch
            ),
        ];

        $lines = [];

        // Tracks whether a PAG-IBIG group code has already been encountered.
        // Set on FIRST ENCOUNTER (not just when amount > 0) so a zero-resolving
        // first record still blocks the alias from being processed.
        $pagibigGroupResolved = false;

        foreach ($allTypes as $type) {

            // ── Fix 1: Skip employer-only codes entirely ──────────────────
            if (in_array($type->code, self::EMPLOYER_ONLY_CODES)) {
                continue;
            }

            // ── Assignment scope: skip types this employee is not assigned to ──
            // Applies uniformly to Tier 1 (formula) and Tier 2 (locked global)
            // types, which otherwise have no way to exclude an employee.
            // Tier 3 already gates via enrollment, but this is still checked
            // as a hard boundary (e.g. an employee transferred out of scope).
            if (! $type->appliesToEmployeeId($employee->id)) {
                continue;
            }

            // ── Fix 2 + 2a: Deduplicate PAG-IBIG alias codes ─────────────
            // Mark the group resolved on FIRST ENCOUNTER regardless of amount,
            // then skip any subsequent code in the same group.
            $isPagibigCode = in_array($type->code, self::PAGIBIG_GROUP_CODES);
            if ($isPagibigCode && $pagibigGroupResolved) {
                continue;
            }
            if ($isPagibigCode) {
                $pagibigGroupResolved = true;
            }

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
                    // Not locked but override set → per-type manual adjustment
                    $amount       = (float) $type->override_amount;
                    $isOverridden = true;
                } else {
                    // Normal formula path — uses DB-configured rates with hardcoded fallbacks
                    $amount = $formulaAmounts[$type->code] ?? 0.00;
                }

            // ── Tier 2: Locked global manual ─────────────────────────────
            } elseif ($type->isEffectivelyLocked()) {
                if ($type->percentage !== null) {
                    $monthlyAmount = round($basicMonthly * ($type->percentage / 100), 2);
                    $amount        = round($monthlyAmount / 2, 2);
                    $isGlobal      = true;
                } elseif ($type->default_amount !== null) {
                    $amount   = (float) $type->default_amount;
                    $isGlobal = true;
                }

            // ── Tier 3: Per-employee enrollment ──────────────────────────
            } elseif (isset($enrollments[$type->code])) {
                $enrollment = $enrollments[$type->code];
                if ($enrollment->amount > 0) {
                    $amount = (float) $enrollment->amount;
                } elseif ($enrollment->percentage_override !== null) {
                    $monthlyAmount = round($basicMonthly * ($enrollment->percentage_override / 100), 2);
                    $amount        = round($monthlyAmount / 2, 2);
                } elseif ($type->percentage !== null) {
                    $monthlyAmount = round($basicMonthly * ($type->percentage / 100), 2);
                    $amount        = round($monthlyAmount / 2, 2);
                }
            }

            if ($amount > 0.00) {
                $lines[] = [
                    'deduction_type_id' => $type->id,
                    'code'              => $type->code,
                    'name'              => $type->name,
                    'amount'            => round($amount, 2),
                    'is_overridden'     => $isOverridden,
                    'is_global'         => $isGlobal,
                ];
            }
        }

        return $lines;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Computed Government-Mandatory Deduction Helpers
    //
    //  Each method accepts a nullable ?DeductionType $type parameter.
    //  When $type is provided, rates are read from its formula_rate_*
    //  columns. A null column falls back to this class's hardcoded
    //  constants (see top of file). When $type is null entirely, all
    //  constants are used — this preserves unit-test compatibility.
    //
    //  All return the PER CUT-OFF (semi-monthly) amount.
    // ═══════════════════════════════════════════════════════════════════

    /**
     * PAG-IBIG I (HDMF Mandatory) — per cut-off EE share.
     *
     * Rate logic:
     *   • If basicMonthly ≤ threshold → apply rate_low
     *   • Otherwise → apply rate
     *   • Cap the monthly EE share at monthly_cap
     *   • Divide by 2 for the cut-off amount
     *
     * DB columns used: formula_rate, formula_rate_low,
     *                  formula_rate_threshold, formula_monthly_cap
     */
    public function computePagibig1(float $basicMonthly, ?DeductionType $type = null): float
    {
        $rate      = (float) ($type?->formula_rate           ?? self::PAGIBIG_RATE);
        $rateLow   = (float) ($type?->formula_rate_low       ?? self::PAGIBIG_RATE_LOW);
        $threshold = (float) ($type?->formula_rate_threshold ?? self::PAGIBIG_THRESHOLD);
        $cap       = (float) ($type?->formula_monthly_cap    ?? self::PAGIBIG_MONTHLY_CAP);

        $appliedRate = $basicMonthly <= $threshold ? $rateLow : $rate;
        $monthlyEE   = min(round($basicMonthly * $appliedRate, 2), $cap);

        return round($monthlyEE / 2, 2);
    }

    /**
     * PhilHealth Mandatory Premium — per cut-off EE share.
     *
     * Rate logic:
     *   • total monthly premium = basicMonthly × rate
     *   • clamp to [floor, ceiling]
     *   • EE share = total × 0.5  (employee pays half)
     *   • Divide by 2 for the cut-off amount
     *
     * DB columns used: formula_rate, formula_monthly_floor,
     *                  formula_monthly_ceiling
     */
    public function computePhilHealth(float $basicMonthly, ?DeductionType $type = null): float
    {
        $rate    = (float) ($type?->formula_rate            ?? self::PHILHEALTH_RATE);
        $floor   = (float) ($type?->formula_monthly_floor   ?? self::PHILHEALTH_MONTHLY_FLOOR);
        $ceiling = (float) ($type?->formula_monthly_ceiling ?? self::PHILHEALTH_MONTHLY_CEILING);

        $totalMonthly = round($basicMonthly * $rate, 2);
        $totalMonthly = max($floor, min($totalMonthly, $ceiling));
        $monthlyEE    = round($totalMonthly / 2, 2); // EE pays 50%

        return round($monthlyEE / 2, 2);
    }

    /**
     * GSIS Life & Retirement (Personal Share) — per cut-off EE share.
     *
     * Rate logic:
     *   • monthly personal share = basicMonthly × rate
     *   • Prorate if daysWorked < totalDays
     *   • Divide by 2 for the cut-off amount
     *
     * DB columns used: formula_rate
     */
    public function computeGsisLife(
        float          $basicMonthly,
        ?DeductionType $type       = null,
        ?int           $daysWorked = null,
        ?int           $totalDays  = 22
    ): float {
        $rate = (float) ($type?->formula_rate ?? self::GSIS_RATE);

        $monthlyPS = round($basicMonthly * $rate, 2);

        // Prorate for incomplete months when daysWorked is provided
        if ($daysWorked !== null && $totalDays > 0 && $daysWorked < $totalDays) {
            $monthlyPS = round($basicMonthly / $totalDays * $rate * $daysWorked, 2);
        }

        return round($monthlyPS / 2, 2);
    }

    /**
     * Withholding Tax (BIR TRAIN Law) — per cut-off amount.
     *
     * ⚠ DEVELOPER NOTE — rates intentionally hardcoded:
     *   The BIR graduated tax table has six interdependent brackets with
     *   both fixed base amounts and marginal rates. Exposing them in a UI
     *   risks misconfiguration (e.g. overlapping ranges, wrong base amounts)
     *   that would silently produce incorrect tax deductions for all employees.
     *   If the tax table changes (new TRAIN amendments), a developer must
     *   update birGraduatedTax() below and re-test.
     *
     * No DeductionType parameter — this method does not use formula_rate_* columns.
     */
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

        // Annual mandatory deductions (hardcoded statutory rates — see note above)
        $annualGSIS = round($basicMonthly * 0.09 * 12, 2);
        $annualPHIC = max(6_000.00, min(round($basicMonthly * 0.05 * 12, 2), 60_000.00));
        $annualHDMF = min(round($basicMonthly * 0.02 * 12, 2), 1_200.00);

        $taxableIncome = max(0.00, $projectedAnnual - $annualGSIS - $annualPHIC - $annualHDMF);
        $annualTax     = $this->birGraduatedTax($taxableIncome);

        return max(0.00, round($annualTax / 24, 2));
    }

    /**
     * BIR TRAIN Law graduated income tax table.
     *
     * ⚠ HARDCODED — do not expose in UI. See computeWithholdingTax() note.
     * Last updated: TRAIN Law (RA 10963) rates effective 2023 onwards.
     *
     * Bracket structure (annual taxable income):
     *   ≤ ₱250,000              →  0 %
     *   ₱250,001 – ₱400,000     → 15 % of excess over ₱250,000
     *   ₱400,001 – ₱800,000     → ₱22,500  + 20 % of excess over ₱400,000
     *   ₱800,001 – ₱2,000,000   → ₱102,500 + 25 % of excess over ₱800,000
     *   ₱2,000,001 – ₱8,000,000 → ₱402,500 + 30 % of excess over ₱2,000,000
     *   > ₱8,000,000             → ₱2,202,500 + 35 % of excess over ₱8,000,000
     */
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

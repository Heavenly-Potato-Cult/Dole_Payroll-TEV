<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DeductionTypeSeeder
 *
 * Seeds all 28 deduction types for the DOLE RO9 payroll system.
 *
 * ── CRITICAL CONTRACT ────────────────────────────────────────────────────────
 *
 * The `code` values here are the single source of truth. They MUST match:
 *   1. DeductionService::resolveDeductions()            — computed[] map keys
 *   2. PayrollEntryController::payslipDeductionRows()    — 'code' field in each row
 *   3. employee_deduction_enrollments.deduction_type_id  — via code lookup
 *
 * DO NOT rename codes without updating all three locations above.
 *
 * ── Schema note (rebuilt version) ───────────────────────────────────────────
 *
 * The original version of this seeder (pre-refactor) only set code/name/
 * category/is_computed/is_active/display_order/notes. The table has since
 * grown deduction_type_category_id (FK — resolved below by looking up
 * deduction_type_categories.code at runtime, not hardcoded IDs, since IDs
 * differ across environments), formula_rate / formula_rate_low /
 * formula_rate_threshold / formula_monthly_floor / formula_monthly_ceiling /
 * formula_monthly_cap (populated for the 4 computed types, from the same
 * rates the old notes text described), is_fixed_amount, is_locked, and
 * assignment_scope. See inline comments below for the reasoning behind each.
 *
 * ── is_computed flag ─────────────────────────────────────────────────────────
 *
 * TRUE  = amount is auto-calculated from salary (PAG-IBIG I, PhilHealth,
 *         GSIS Life/Retirement, Withholding Tax). No enrollment needed.
 *
 * FALSE = amount comes from employee_deduction_enrollments. The payroll
 *         officer enrolls these per employee with a fixed monthly amount.
 *
 * ── assignment_scope ─────────────────────────────────────────────────────────
 *
 * 'all'      — the 4 computed, government-mandated types: every active
 *              employee is subject to these, so they apply universally.
 * 'specific' — the 24 optional/voluntary enrollment-based types (loans,
 *              union dues, HMO, etc.): only employees who actually signed
 *              up should show up in these, via deduction_type_employee.
 *
 * ── allow_multiple_accounts ──────────────────────────────────────────────────
 *
 * Set true only for loan-type deductions where an employee could plausibly
 * carry more than one account of the same type at once (multiple GSIS
 * policy loans, multiple HDMF loans, etc.) — gates the repeatable
 * account-slot UI on the employee deductions form, per
 * EmployeeDeductionEnrollment.account_number. Left false elsewhere; you can
 * flip individual ones on later from the Deduction Types CMS if a real case
 * comes up that isn't covered here.
 *
 * ── display_order ────────────────────────────────────────────────────────────
 *
 * Matches the exact payslip column order from the DOLE RO9 Excel template
 * (01A-General-Payroll-Monthly.xlsx, Payslip sheet) — same order the old
 * seeder used.
 *
 * ── Seeder run order ─────────────────────────────────────────────────────────
 *
 * This is layer 1 of a 3-seeder pipeline. Run in this order:
 *   1. DeductionTypeSeeder              (this file — base 28 codes)
 *   2. DeductionTypeUpdateSeeder        (adds PHILHEALTH_EMPLOYEE/EMPLOYER +
 *                                        GSIS_GOVERNMENT_SHARE bookkeeping
 *                                        rows, flips PHILHEALTH to computed,
 *                                        locks the 2 GSIS rows)
 *   3. DeductionTypeFormulaRateSeeder   (sets formula_rate/floor/ceiling/cap
 *                                        on PAG_IBIG_1, PHILHEALTH,
 *                                        GSIS_LIFE_RETIREMENT — the single
 *                                        source of truth for those numbers,
 *                                        NOT this file)
 *
 * Running #2 or #3 without #1 first is exactly what produced a
 * deduction_types table with only 3 rows (PHILHEALTH_EMPLOYEE/EMPLOYER,
 * GSIS_GOVERNMENT_SHARE) and none of the 28 base codes — #2 ran fine on
 * its own since it only adds/updates specific codes, it just had nothing
 * from #1 underneath it yet.
 *
 * ── Re-running safely ────────────────────────────────────────────────────────
 *
 * Uses updateOrInsert keyed on `code` so this can be re-run without
 * duplicates. To fully reset: php artisan migrate:fresh --seed
 */
class DeductionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Resolve category string -> deduction_type_category_id at runtime
        // (never hardcode IDs from another environment's dump).
        $categoryIds = DB::table('deduction_type_categories')->pluck('id', 'code');

        $types = [
            // ── HDMF / Pag-IBIG ─────────────────────────────────────────────
            [
                'code'          => 'PAG_IBIG_1',
                'name'          => 'PAG-IBIG I',
                'category'      => 'pagibig',
                'is_computed'   => true,
                'is_fixed_amount' => false,
                // formula_rate / formula_rate_low / formula_rate_threshold /
                // formula_monthly_cap are intentionally NOT set here —
                // DeductionTypeFormulaRateSeeder is the single source of
                // truth for these and must run after this seeder.
                'assignment_scope' => 'all',
                'is_active'     => true,
                'display_order' => 1,
                'notes'         => 'HDMF mandatory contribution. Rates set by DeductionTypeFormulaRateSeeder. Auto-computed.',
            ],
            [
                'code'          => 'HDMF_MPL',
                'name'          => 'MULTI-PURPOSE',
                'category'      => 'pagibig',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 2,
                'notes'         => 'HDMF Multi-Purpose Loan. Fixed monthly amortization per employee.',
            ],
            [
                'code'          => 'HDMF_CAL',
                'name'          => 'CALAMITY LOAN',
                'category'      => 'pagibig',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 3,
                'notes'         => 'HDMF Calamity Loan amortization.',
            ],
            [
                'code'          => 'HDMF_HOUSING',
                'name'          => 'HOUSE & LOT',
                'category'      => 'pagibig',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 4,
                'notes'         => 'HDMF Housing / House & Lot loan amortization.',
            ],
            [
                'code'          => 'HDMF_P2',
                'name'          => 'PAG-IBIG II',
                'category'      => 'pagibig',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 5,
                'notes'         => 'Modified Pag-IBIG II (MP2) voluntary savings. Fixed enrollment amount.',
            ],

            // ── PhilHealth ───────────────────────────────────────────────────
            [
                'code'          => 'PHILHEALTH',
                'name'          => 'PHILHEALTH',
                'category'      => 'philhealth',
                'is_computed'   => true,
                'is_fixed_amount' => false,
                // formula_rate / formula_monthly_floor / formula_monthly_ceiling
                // owned by DeductionTypeFormulaRateSeeder — run after this one.
                'assignment_scope' => 'all',
                'is_active'     => true,
                'display_order' => 6,
                'notes'         => 'PhilHealth mandatory contribution. Rates set by DeductionTypeFormulaRateSeeder. Auto-computed.',
            ],

            // ── GSIS ─────────────────────────────────────────────────────────
            [
                'code'          => 'GSIS_LIFE_RETIREMENT',
                'name'          => 'LIFE/RETIREMENT',
                'category'      => 'gsis',
                'is_computed'   => true,
                'is_fixed_amount' => false,
                // formula_rate owned by DeductionTypeFormulaRateSeeder —
                // run after this one. is_locked is also set there/by
                // DeductionTypeUpdateSeeder, not here.
                'assignment_scope' => 'all',
                'is_active'     => true,
                'display_order' => 7,
                'notes'         => 'GSIS Life & Retirement Personal Share (PS). Rate set by DeductionTypeFormulaRateSeeder. Auto-computed.',
            ],
            [
                'code'          => 'GSIS_CONSO',
                'name'          => 'CONSO LOAN',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 8,
                'notes'         => 'GSIS Consolidated Loan amortization.',
            ],
            [
                'code'          => 'GSIS_POLICY',
                'name'          => 'POLICY LOAN',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 9,
                'notes'         => 'GSIS Policy Loan (Regular & Optional).',
            ],
            [
                'code'          => 'GSIS_REAL_ESTATE',
                'name'          => 'REAL ESTATE',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 10,
                'notes'         => 'GSIS Real Estate Loan amortization.',
            ],
            [
                'code'          => 'GSIS_MPL',
                'name'          => 'GSIS MPL',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 11,
                'notes'         => 'GSIS Multi-Purpose Loan (MPL).',
            ],
            [
                'code'          => 'GSIS_CPL',
                'name'          => 'GSIS CPL',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 12,
                'notes'         => 'GSIS Consolidated Policy Loan (CPL).',
            ],
            [
                'code'          => 'GSIS_MPL_LITE',
                'name'          => 'GSIS MPL Lite',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 13,
                'notes'         => 'GSIS MPL Lite.',
            ],
            [
                'code'          => 'GSIS_GFAL',
                'name'          => 'GFAL',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 14,
                'notes'         => 'GSIS GFAL (Gratuity Fund Assistance Loan).',
            ],
            [
                'code'          => 'GSIS_HELP',
                'name'          => 'HELP',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 15,
                'notes'         => 'GSIS HELP (Housing Emergency Loan Program).',
            ],
            [
                'code'          => 'GSIS_EMERGENCY',
                'name'          => 'GSIS EMERG LOAN',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 16,
                'notes'         => 'GSIS Emergency Loan.',
            ],

            // ── Other Government / Voluntary ──────────────────────────────────
            [
                'code'          => 'MASS',
                'name'          => 'MASS',
                'category'      => 'other_gov',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 17,
                'notes'         => 'MASS (Mutual Aid Support System). Fixed enrollment amount.',
            ],
            [
                'code'          => 'SSS',
                'name'          => 'SSS CONTRIBUTION',
                'category'      => 'other_gov',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 18,
                'notes'         => 'SSS Voluntary Contribution. Fixed enrollment amount.',
            ],
            [
                'code'          => 'PROVIDENT_FUND',
                'name'          => 'PROVIDENT FUND',
                'category'      => 'other_gov',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 19,
                'notes'         => 'DOLE Provident Fund contribution. Fixed enrollment amount.',
            ],

            // ── Tax ───────────────────────────────────────────────────────────
            [
                'code'          => 'WITHHOLDING_TAX',
                'name'          => 'W/HOLDING TAX',
                'category'      => 'other_gov',
                'is_computed'   => true,
                'is_fixed_amount' => false,
                // BIR TRAIN graduated table, annualized — not a flat rate,
                // so formula_rate/floor/ceiling are intentionally left null.
                // Computed by DeductionService using the graduated table,
                // not a single stored rate.
                'assignment_scope' => 'all',
                'is_active'     => true,
                'display_order' => 20,
                'notes'         => 'Withholding Tax. BIR TRAIN Law graduated table, annualized. Auto-computed.',
            ],

            // ── Loans ─────────────────────────────────────────────────────────
            [
                'code'          => 'LBP_LOAN',
                'name'          => 'LBP LOAN',
                'category'      => 'loan',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 21,
                'notes'         => 'Land Bank of the Philippines salary loan amortization.',
            ],
            [
                'code'          => 'GSIS_EDUC',
                'name'          => 'GSIS EDUCL LOAN',
                'category'      => 'gsis',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 22,
                'notes'         => 'GSIS Educational Assistance Loan.',
            ],
            [
                'code'          => 'HMO',
                'name'          => 'HMO',
                'category'      => 'other_gov',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 23,
                'notes'         => 'HMO (Health Maintenance Organization) deduction.',
            ],

            // ── CARESS IX ────────────────────────────────────────────────────
            [
                'code'          => 'CARESS_UNION',
                'name'          => 'UNION DUES',
                'category'      => 'caress',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 24,
                'notes'         => 'CARESS IX Union Dues. Fixed monthly amount.',
            ],
            [
                'code'          => 'CARESS_MORTUARY',
                'name'          => 'MORTUARY',
                'category'      => 'caress',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 25,
                'notes'         => 'CARESS IX Mortuary contribution. = Daily Rate × 25%.',
            ],
            [
                'code'          => 'CARESS_CARES',
                'name'          => 'CAREs',
                'category'      => 'caress',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'allow_multiple_accounts' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 26,
                'notes'         => 'CARESS IX CAREs Loan amortization.',
            ],

            // ── Miscellaneous ─────────────────────────────────────────────────
            [
                'code'          => 'SMART_PLAN_GOLD',
                'name'          => 'SMART PLAN GOLD EXCESS CHARGES',
                'category'      => 'misc',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 27,
                'notes'         => 'Smart Plan Gold excess charges deduction.',
            ],
            [
                'code'          => 'REFUND_VARIOUS',
                'name'          => 'REFUND (VARIOUS)',
                'category'      => 'misc',
                'is_computed'   => false,
                'is_fixed_amount' => true,
                'assignment_scope' => 'specific',
                'is_active'     => true,
                'display_order' => 28,
                'notes'         => 'Refund / BTR Refund — various types. Per-payroll enrollment.',
            ],
        ];

        foreach ($types as $type) {
            $categoryCode = $type['category'];
            $categoryId   = $categoryIds[$categoryCode] ?? null;

            if ($categoryId === null) {
                $this->command->warn(
                    "DeductionTypeSeeder: no deduction_type_categories row found for code "
                    . "'{$categoryCode}' (deduction type '{$type['code']}') — "
                    . "deduction_type_category_id left null for this row."
                );
            }

            DB::table('deduction_types')->updateOrInsert(
                ['code' => $type['code']],   // match key
                array_merge($type, [
                    'deduction_type_category_id' => $categoryId,
                    'allow_multiple_accounts'    => $type['allow_multiple_accounts'] ?? false,
                    'is_locked'                  => $type['is_locked'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('DeductionTypeSeeder: ' . count($types) . ' deduction types seeded/updated.');
        $this->command->info('Computed types: PAG_IBIG_1, PHILHEALTH, GSIS_LIFE_RETIREMENT, WITHHOLDING_TAX');
    }
}

<?php

/**
 * DeductionTypeFormulaRateSeeder
 *
 * Seeds / refreshes the formula-rate columns on the three configurable
 * computed deduction types.  Safe to run multiple times (uses updateOrCreate
 * on `code`).
 *
 * Run with:
 *   php artisan db:seed --class=DeductionTypeFormulaRateSeeder
 *
 * Or call from your main DatabaseSeeder:
 *   $this->call(DeductionTypeFormulaRateSeeder::class);
 *
 * ── Column meanings ─────────────────────────────────────────────────────
 *
 *  formula_rate             Decimal. Main contribution rate (e.g. 0.0500 = 5 %).
 *  formula_rate_low         Decimal. Lower-tier rate for salary ≤ threshold. PAG-IBIG only.
 *  formula_rate_threshold   Decimal. Salary ceiling for the lower-tier rate. PAG-IBIG only.
 *  formula_monthly_floor    Decimal. Minimum monthly premium before EE split. PhilHealth only.
 *  formula_monthly_ceiling  Decimal. Maximum monthly premium before EE split. PhilHealth only.
 *  formula_monthly_cap      Decimal. Hard ceiling on employee's monthly share. PAG-IBIG only.
 *
 * ── Statutory basis (as of May 2026) ────────────────────────────────────
 *
 *  PAG-IBIG I (HDMF Circular 274):
 *    • 2% of basic for salaries above ₱1,500/month
 *    • 1% of basic for salaries ≤ ₱1,500/month
 *    • EE share capped at ₱100/month → ÷ 2 per cut-off
 *
 *  PhilHealth (PhilHealth Circular 2023-0014, effective Jan 2024):
 *    • 5% of basic monthly salary (total)
 *    • EE share = 50% of total premium
 *    • Monthly premium floor: ₱500 (salary ≤ ₱10,000)
 *    • Monthly premium ceiling: ₱5,000 (salary ≥ ₱100,000)
 *    • Per-cut-off EE amount = (total premium × 0.5) ÷ 2
 *
 *  GSIS Life & Retirement (RA 8291, Personal Share):
 *    • 9% of basic monthly salary → ÷ 2 per cut-off
 *
 * ── When rates change ────────────────────────────────────────────────────
 *  Update the values below and re-run the seeder, or edit directly in
 *  the Deduction Types CMS (Admin → Payroll → Deduction Types → Edit).
 *  Changes take effect on the next payroll run.
 * ─────────────────────────────────────────────────────────────────────────
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payroll\Models\DeductionType;

class DeductionTypeFormulaRateSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [

            // ── PAG-IBIG I / HDMF Mandatory ─────────────────────────────
            [
                'codes'  => ['PAG_IBIG_1', 'PAGIBIG_1'],   // both code variants
                'values' => [
                    'formula_rate'           => 0.0200,   // 2 % — standard rate
                    'formula_rate_low'       => 0.0100,   // 1 % — low-salary tier
                    'formula_rate_threshold' => 1500.00,  // ₱1,500 salary threshold
                    'formula_monthly_cap'    => 100.00,   // EE share capped at ₱100/month
                    // floor/ceiling not used for PAG-IBIG
                    'formula_monthly_floor'   => null,
                    'formula_monthly_ceiling' => null,
                ],
            ],

            // ── PhilHealth ───────────────────────────────────────────────
            [
                'codes'  => ['PHILHEALTH'],
                'values' => [
                    'formula_rate'           => 0.0500,   // 5 % total premium rate
                    'formula_monthly_floor'  => 500.00,   // ₱500 minimum total monthly premium
                    'formula_monthly_ceiling'=> 5000.00,  // ₱5,000 maximum total monthly premium
                    // lower-tier not used for PhilHealth
                    'formula_rate_low'        => null,
                    'formula_rate_threshold'  => null,
                    'formula_monthly_cap'     => null,
                ],
            ],

            // ── GSIS Life & Retirement ───────────────────────────────────
            [
                'codes'  => ['GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET'],
                'values' => [
                    'formula_rate'           => 0.0900,   // 9 % personal share
                    // other columns not used for GSIS
                    'formula_rate_low'        => null,
                    'formula_rate_threshold'  => null,
                    'formula_monthly_floor'   => null,
                    'formula_monthly_ceiling' => null,
                    'formula_monthly_cap'     => null,
                ],
            ],

        ];

        foreach ($definitions as $def) {
            foreach ($def['codes'] as $code) {
                DeductionType::where('code', $code)->update($def['values']);

                $this->command?->line("  ✓ {$code} formula rates seeded.");
            }
        }

        $this->command?->info('DeductionTypeFormulaRateSeeder complete.');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds configurable formula-rate columns to deduction_types.
 *
 * These columns apply only to Tier 1 (is_computed = true) types and allow
 * the Payroll Officer to adjust statutory rates through the admin UI without
 * touching code.
 *
 * Column guide
 * ────────────────────────────────────────────────────────────────────────
 *  formula_rate             — main contribution rate as a decimal
 *                             PAG-IBIG: 0.0200 (2%)
 *                             PhilHealth: 0.0500 (5%)
 *                             GSIS Life/Ret: 0.0900 (9%)
 *
 *  formula_rate_low         — lower-tier rate used when salary is at or
 *                             below formula_rate_threshold.
 *                             PAG-IBIG only: 0.0100 (1%)
 *
 *  formula_rate_threshold   — monthly salary threshold that triggers the
 *                             lower rate tier (formula_rate_low).
 *                             PAG-IBIG only: 1 500.00
 *
 *  formula_monthly_floor    — minimum monthly contribution amount (EE + ER
 *                             combined) before splitting EE share.
 *                             PhilHealth only: 500.00
 *
 *  formula_monthly_ceiling  — maximum monthly contribution amount before
 *                             splitting EE share.
 *                             PhilHealth only: 5 000.00
 *
 *  formula_monthly_cap      — absolute ceiling on the employee's monthly
 *                             share after applying the rate.
 *                             PAG-IBIG only: 100.00
 *
 * All columns are nullable.  DeductionService falls back to its hardcoded
 * constants when a column is null, so existing rows are unaffected until
 * a Payroll Officer explicitly saves new values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            // Main contribution rate (e.g. 0.0500 = 5 %)
            $table->decimal('formula_rate', 5, 4)
                  ->nullable()
                  ->after('percentage')
                  ->comment('Primary contribution rate as a decimal (e.g. 0.0500 for 5%). Applies to PAG-IBIG, PhilHealth, GSIS.');

            // Lower-tier rate — PAG-IBIG only
            $table->decimal('formula_rate_low', 5, 4)
                  ->nullable()
                  ->after('formula_rate')
                  ->comment('Lower rate applied when salary ≤ formula_rate_threshold. PAG-IBIG only (0.0100).');

            // Salary threshold for the lower rate — PAG-IBIG only
            $table->decimal('formula_rate_threshold', 10, 2)
                  ->nullable()
                  ->after('formula_rate_low')
                  ->comment('Monthly salary ceiling for the lower-rate tier. PAG-IBIG only (1500.00).');

            // Monthly floor — PhilHealth only
            $table->decimal('formula_monthly_floor', 10, 2)
                  ->nullable()
                  ->after('formula_rate_threshold')
                  ->comment('Minimum total monthly premium before EE/ER split. PhilHealth only (500.00).');

            // Monthly ceiling — PhilHealth only
            $table->decimal('formula_monthly_ceiling', 10, 2)
                  ->nullable()
                  ->after('formula_monthly_floor')
                  ->comment('Maximum total monthly premium before EE/ER split. PhilHealth only (5000.00).');

            // Monthly cap on EE share — PAG-IBIG only
            $table->decimal('formula_monthly_cap', 10, 2)
                  ->nullable()
                  ->after('formula_monthly_ceiling')
                  ->comment('Hard ceiling on the employee monthly share after rate is applied. PAG-IBIG only (100.00).');
        });

        // ── Seed defaults for the three configurable computed types ──────
        // Using updateOrCreate-style raw update so this runs safely even if
        // the seeder is not executed separately.
        $defaults = [
            // PAG-IBIG I — two-tier rate, capped at ₱100/month EE share
            'PAG_IBIG_1'           => [
                'formula_rate'           => 0.0200,
                'formula_rate_low'       => 0.0100,
                'formula_rate_threshold' => 1500.00,
                'formula_monthly_cap'    => 100.00,
            ],
            // Legacy code variant
            'PAGIBIG_1'            => [
                'formula_rate'           => 0.0200,
                'formula_rate_low'       => 0.0100,
                'formula_rate_threshold' => 1500.00,
                'formula_monthly_cap'    => 100.00,
            ],
            // PhilHealth — single rate with floor/ceiling
            'PHILHEALTH'           => [
                'formula_rate'           => 0.0500,
                'formula_monthly_floor'  => 500.00,
                'formula_monthly_ceiling'=> 5000.00,
            ],
            // GSIS Life & Retirement — single rate, no caps
            'GSIS_LIFE_RETIREMENT' => [
                'formula_rate'           => 0.0900,
            ],
            'GSIS_LIFE_RET'        => [
                'formula_rate'           => 0.0900,
            ],
        ];

        foreach ($defaults as $code => $values) {
            DB::table('deduction_types')
              ->where('code', $code)
              ->update($values);
        }
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn([
                'formula_rate',
                'formula_rate_low',
                'formula_rate_threshold',
                'formula_monthly_floor',
                'formula_monthly_ceiling',
                'formula_monthly_cap',
            ]);
        });
    }
};
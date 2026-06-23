<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds a dedicated multi-account flag to deduction_types.
 *
 * The multi-account loan UI was originally gated on
 * `category IN ('loan', 'caress')`. In practice several PAG-IBIG loan
 * products (HDMF_CALAMITY, HDMF_MPL, HDMF_HOUSING) are stored with
 * category = 'pagibig' — that column is also used for display grouping
 * and possibly for PAG-IBIG remittance reporting, so changing it to
 * 'loan' would move those rows into the wrong card and risks breaking
 * anything downstream that groups by category = 'pagibig'.
 *
 * `allow_multiple_accounts` is independent of `category` and `is_locked`.
 * It defaults to true for the existing loan/caress category types (so
 * LBP_LOAN and CARESS_LOAN keep working without a manual flip) and is
 * explicitly enabled for the three PAG-IBIG loan products above.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->boolean('allow_multiple_accounts')
                ->default(false)
                ->after('category')
                ->comment('Independent of category — gates the repeatable account-slot UI on the employee deductions form.');
        });

        // Existing loan-category types: keep current behavior working.
        DB::table('deduction_types')
            ->whereIn('category', ['loan', 'caress'])
            ->update(['allow_multiple_accounts' => true]);

        // PAG-IBIG loan products filed under category = 'pagibig' that
        // actually need multiple accounts (e.g. two Calamity Loans).
        DB::table('deduction_types')
            ->whereIn('code', ['HDMF_CALAMITY', 'HDMF_MPL', 'HDMF_HOUSING'])
            ->update(['allow_multiple_accounts' => true]);
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn('allow_multiple_accounts');
        });
    }
};

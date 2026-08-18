<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the payroll-officer-entered PERA override to special_payroll_batches.
 *
 * Currently only meaningful for type = newly_hired / transferee (the only
 * special payroll type that computes a PERA figure at all — see
 * NewlyHiredPayrollService::compute()). Nullable: null means "auto-computed
 * as (PERA / 22 * working_days)", matching pre-existing batches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->decimal('pera_override', 10, 2)->nullable()->after('gsis_rate_applied');
        });
    }

    public function down(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->dropColumn('pera_override');
        });
    }
};

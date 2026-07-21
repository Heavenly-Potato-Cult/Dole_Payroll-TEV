<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'cutoff' is normally already dropped by the earlier
        // consolidate_payroll_to_monthly_and_add_daily_logs migration.
        // This guard makes it a safe no-op in that case instead of
        // failing with "Can't DROP COLUMN cutoff; check that it exists".
        if (Schema::hasColumn('payroll_batches', 'cutoff')) {
            Schema::table('payroll_batches', function (Blueprint $table) {
                $table->dropColumn('cutoff');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payroll_batches', 'cutoff')) {
            Schema::table('payroll_batches', function (Blueprint $table) {
                $table->string('cutoff', 10)->after('period_month');
            });
        }
    }
};

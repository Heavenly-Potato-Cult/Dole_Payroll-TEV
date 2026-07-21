<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the actual GSIS PS rate applied at creation time (0 when the
 * preparer turned GSIS off for a non-covered COS/JO hire). Needed because
 * newHireShow()/newHirePayslip() re-run NewlyHiredPayrollService::compute()
 * on every view — without this column they'd have no way to know whether
 * GSIS was deliberately disabled vs. just using the default rate, and would
 * silently re-apply 9% even for a batch that was created with it off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->decimal('gsis_rate_applied', 5, 4)->nullable()->after('deductions_amount');
        });
    }

    public function down(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->dropColumn('gsis_rate_applied');
        });
    }
};

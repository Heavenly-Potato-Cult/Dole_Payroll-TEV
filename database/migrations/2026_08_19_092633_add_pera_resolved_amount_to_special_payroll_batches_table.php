<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            // Monthly PERA base resolved via AllowanceService::resolveForPeriod()
            // (standing enrollment -> legacy employee.pera fallback -> released
            // assignment override) at the moment the batch was created, and
            // frozen from then on — same reasoning as special_payroll_batch_allowances
            // being persisted rather than re-resolved: newHireShow()/newHirePayslip()
            // should keep matching what was actually applied at creation, not
            // silently drift if the employee's standing PERA enrollment changes
            // later. Null on rows created before this migration; those fall back
            // to a live resolve at read time (see SpecialPayrollController).
            $table->decimal('pera_resolved_amount', 10, 2)->nullable()->after('pera_override');
        });
    }

    public function down(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->dropColumn('pera_resolved_amount');
        });
    }
};

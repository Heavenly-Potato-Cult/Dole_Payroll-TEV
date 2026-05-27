<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
            $table->decimal('percentage_override', 5, 2)->nullable()->after('amount')
                ->comment('Individual percentage override for this employee (overrides type-level percentage)');
        });
    }

    public function down(): void
    {
        Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
            $table->dropColumn('percentage_override');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds account_number to employee_deduction_enrollments.
 *
 * Needed so a single employee can have multiple enrollment rows for the
 * same loan-category deduction type (e.g. two PAG-IBIG Calamity Loan
 * accounts). For non-loan types this column stays NULL — those types
 * still resolve to exactly one row per (employee_id, deduction_type_id),
 * matching existing behavior.
 *
 * No DB-level unique constraint is added here on purpose: MySQL treats
 * each NULL as distinct in a unique index, so it would not actually
 * protect non-loan rows from duplication, and adding one retroactively
 * risks failing if any duplicate (employee_id, deduction_type_id) pairs
 * already exist. Uniqueness for loan accounts is enforced at the
 * application layer in EmployeeDeductionController::syncLoanAccounts().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
            $table->string('account_number', 100)
                ->nullable()
                ->after('deduction_type_id')
                ->comment('Distinguishes multiple accounts of the same loan-category deduction type for one employee. NULL for non-loan types.');
        });
    }

    public function down(): void
    {
        Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
            $table->dropColumn('account_number');
        });
    }
};

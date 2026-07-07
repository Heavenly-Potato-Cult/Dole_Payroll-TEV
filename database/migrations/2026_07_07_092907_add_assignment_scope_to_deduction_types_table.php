<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds assignment_scope to deduction_types.
 *
 * Default 'all' preserves current behavior — every deduction type
 * continues to apply to every active employee unless an admin
 * explicitly narrows it.
 *
 * 'specific' switches resolution (DeductionService,
 * PayrollComputationService, and EmployeeDeductionController::index())
 * over to consulting the deduction_type_employee pivot table instead —
 * see the create_deduction_type_employee_table migration.
 *
 * Placed after is_locked to sit alongside the other tier/behavior flags
 * on this table (is_computed, is_locked, allow_multiple_accounts).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('deduction_types', 'assignment_scope')) {
            Schema::table('deduction_types', function (Blueprint $table) {
                $table->enum('assignment_scope', ['all', 'specific'])
                      ->default('all')
                      ->after('is_locked')
                      ->comment('all = applies to every active employee (default). specific = only employees listed in deduction_type_employee.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('deduction_types', 'assignment_scope')) {
            Schema::table('deduction_types', function (Blueprint $table) {
                $table->dropColumn('assignment_scope');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates deduction_type_employee — an inclusion whitelist consulted
 * only when deduction_types.assignment_scope = 'specific'.
 *
 * When a type's scope is 'all', this table is not consulted at all, so
 * rows are safely left in place if an admin later flips a type back to
 * 'specific' — no need to re-select employees from scratch.
 *
 * Deliberately a simple pivot (no extra columns) — this table answers
 * "is this employee eligible for this type," not "what is the amount."
 * Amounts/enrollment for Tier 3 types remain in
 * employee_deduction_enrollments; Tier 1/2 amounts remain computed by
 * DeductionService / PayrollComputationService.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deduction_type_employee')) {
            Schema::create('deduction_type_employee', function (Blueprint $table) {
                $table->id();

                $table->foreignId('deduction_type_id')
                      ->constrained('deduction_types')
                      ->cascadeOnDelete();

                $table->foreignId('employee_id')
                      ->constrained('employees')
                      ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(['deduction_type_id', 'employee_id'], 'dte_type_employee_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_type_employee');
    }
};

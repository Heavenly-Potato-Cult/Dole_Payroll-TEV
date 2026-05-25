<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds global_amount + is_locked to deduction_types.
 *
 * Design:
 *   default_amount  — CMS-managed per-cut-off amount applied to all employees
 *                     when is_locked = true; acts as a default pre-fill when unlocked.
 *   is_locked       — when true, the payroll engine uses default_amount globally
 *                     and the employee enrollment form disables editing.
 *
 * Relationship with existing is_computed:
 *   is_computed = true  → formula-driven (PAG-IBIG I, PhilHealth, GSIS, WHT)
 *                         is_locked on these types makes override_amount the global value.
 *   is_computed = false → manual/loan types; is_locked controls whether HR can
 *                         edit per-employee or only the global default is used.
 */
return new class extends Migration
{
public function up(): void
{
    Schema::table('deduction_types', function (Blueprint $table) {
        if (!Schema::hasColumn('deduction_types', 'default_amount')) {
            $table->decimal('default_amount', 12, 2)->nullable()
                  ->after('override_note')
                  ->comment('Global default amount per cut-off. Used directly when is_locked=1.');
        }

        if (!Schema::hasColumn('deduction_types', 'is_locked')) {
            $table->boolean('is_locked')->default(false)
                  ->after('default_amount')
                  ->comment('1 = global amount applied to all employees, no per-employee edit allowed.');
        }
    });
}

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn(['default_amount', 'is_locked']);
        });
    }
};

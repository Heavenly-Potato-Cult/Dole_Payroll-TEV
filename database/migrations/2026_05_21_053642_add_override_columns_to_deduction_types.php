<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: deduction_types — computed-override columns
 *
 * Adds two columns that support Enhancement #1:
 *   - override_amount  — when set, the payroll engine uses this value instead
 *                        of the formula result for a computed type.
 *   - override_note    — explains why the amount was overridden (audit trail).
 *
 * These are nullable so existing rows are unaffected.  A NULL override_amount
 * means "use the formula as normal".
 *
 * Enhancement #3 (duplicate display_order) is handled at the application layer
 * (unique-per-category validation in the controller) rather than a DB unique
 * index, because the same order number CAN be used across different categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            // Override support for computed types
            $table->decimal('override_amount', 12, 2)->nullable()->after('default_amount')
                  ->comment('When set, payroll engine uses this instead of the computed formula.');
            $table->string('override_note', 300)->nullable()->after('override_amount')
                  ->comment('Reason for the manual override (audit trail).');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn(['override_amount', 'override_note']);
        });
    }
};

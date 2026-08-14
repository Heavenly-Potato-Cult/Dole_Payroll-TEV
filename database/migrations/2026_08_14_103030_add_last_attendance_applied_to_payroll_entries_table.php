<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Scaffolded with:
//   php artisan make:migration add_last_attendance_applied_to_payroll_entries_table --table=payroll_entries
// then replace the generated body with this file's up()/down().

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            // Defaults to false. This is deliberately separate from
            // applied_components->attendance, which is cumulative (an OR
            // against its own history) and feeds the submit-to-accountant
            // gate — it must stay true forever once attendance has
            // genuinely been computed at least once for an entry.
            //
            // last_attendance_applied instead mirrors ONLY the most
            // recent compute pass's "Apply Attendance" checkbox, straight
            // overwrite each time (never OR'd). It drives the Payroll
            // Register's 1st/2nd cutoff Split column: unchecking Apply
            // Attendance and recomputing should visibly fall back to an
            // even 50/50 split again, as if attendance were being
            // computed for the first time.
            $table->boolean('last_attendance_applied')
                ->default(false)
                ->after('applied_components')
                ->comment('Per-pass (non-cumulative) — did the MOST RECENT compute pass apply attendance. Drives the register Split column only; NOT used by the submit gate.');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn('last_attendance_applied');
        });
    }
};

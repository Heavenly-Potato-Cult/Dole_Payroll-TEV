<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes:
     *  - payroll_batches   → drop `cutoff` column
     *  - attendance_snapshots → add `daily_logs` (JSON) and `is_first_cutoff` (boolean)
     */
    public function up(): void
    {
        // ── payroll_batches ───────────────────────────────────────────
        if (Schema::hasColumn('payroll_batches', 'cutoff')) {
            // MySQL/MariaDB can throw error 1072 ("Key column 'cutoff'
            // doesn't exist in table") when dropping a column that is
            // still part of a composite index — the index has to be
            // dropped explicitly first, it is not auto-adjusted here.
            $indexes = DB::select("SHOW INDEX FROM payroll_batches WHERE Column_name = 'cutoff'");
            foreach ($indexes as $index) {
                DB::statement("ALTER TABLE payroll_batches DROP INDEX `{$index->Key_name}`");
            }

            Schema::table('payroll_batches', function (Blueprint $table) {
                $table->dropColumn('cutoff');
            });
        }

        // Ensure unique index exists on (period_year, period_month)
        // This replaces the old unique index that included cutoff
        $uniqueExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'payroll_batches')
            ->where('index_name', 'payroll_batch_unique')
            ->exists();

        if (! $uniqueExists) {
            Schema::table('payroll_batches', function (Blueprint $table) {
                $table->unique(['period_year', 'period_month'], 'payroll_batch_unique');
            });
        }

        // ── attendance_snapshots ──────────────────────────────────────
        Schema::table('attendance_snapshots', function (Blueprint $table) {
            // Store day-by-day attendance keyed by date string.
            // Example:
            // {
            //   "2026-05-01": {
            //       "present": true,
            //       "late_minutes": 0,
            //       "undertime_minutes": 0,
            //       "is_first_cutoff": true
            //   },
            //   "2026-05-16": {
            //       "present": true,
            //       "late_minutes": 15,
            //       "undertime_minutes": 0,
            //       "is_first_cutoff": false
            //   }
            // }
            if (! Schema::hasColumn('attendance_snapshots', 'daily_logs')) {
                $table->json('daily_logs')->nullable()->after('leave_credits');
            }

            // Convenience flag: true = days 1-15, false = days 16-end.
            // Stored per-snapshot so a single snapshot can still represent
            // a split view if needed, but primarily used for backward-
            // compatible reporting queries.
            if (! Schema::hasColumn('attendance_snapshots', 'is_first_cutoff')) {
                $table->boolean('is_first_cutoff')->nullable()->after('daily_logs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ── attendance_snapshots ──────────────────────────────────────
        Schema::table('attendance_snapshots', function (Blueprint $table) {
            $table->dropColumn(['daily_logs', 'is_first_cutoff']);
        });

        // ── payroll_batches ───────────────────────────────────────────
        Schema::table('payroll_batches', function (Blueprint $table) {
            // Restore the cutoff column in its original position.
            // Adjust ->after() to match your original schema.
            $table->enum('cutoff', ['1st', '2nd'])
                  ->nullable()
                  ->after('period_month');
        });
    }
};

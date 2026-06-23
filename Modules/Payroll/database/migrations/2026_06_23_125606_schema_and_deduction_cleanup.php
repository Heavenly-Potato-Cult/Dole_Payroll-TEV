<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add allow_multiple_accounts to deduction_types ─────────────
        if (! Schema::hasColumn('deduction_types', 'allow_multiple_accounts')) {
            Schema::table('deduction_types', function (Blueprint $table) {
                $table->boolean('allow_multiple_accounts')
                      ->default(false)
                      ->after('deduction_type_category_id')
                      ->comment('When true, the employee deductions form renders repeatable account slots for this type.');
            });
        }

        // Seed allow_multiple_accounts = 1 for loan-style types
        DB::table('deduction_types')
            ->whereIn('code', [
                'HDMF_MPL',
                'HDMF_CALAMITY',
                'HDMF_CAL',
                'HDMF_HOUSING',
                'PAGIBIG_2',
                'HDMF_P2',
                'LBP_LOAN',
                'CARESS_LOAN',
                'CARESS_CARES',
            ])
            ->update(['allow_multiple_accounts' => true]);

        // ── 2. Add account_number to employee_deduction_enrollments ────────
        if (! Schema::hasColumn('employee_deduction_enrollments', 'account_number')) {
            Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
                $table->string('account_number', 100)
                      ->nullable()
                      ->after('deduction_type_id')
                      ->comment('Distinguishes multiple loan accounts of the same type per employee. Null for single-account types.');
            });
        }

        // ── 3. Add unique index for updateOrCreate() key ───────────────────
        $indexName = 'ede_employee_type_account_unique';
        $indexExists = collect(
            DB::select("SHOW INDEX FROM employee_deduction_enrollments WHERE Key_name = '{$indexName}'")
        )->isNotEmpty();

        if (! $indexExists) {
            Schema::table('employee_deduction_enrollments', function (Blueprint $table) use ($indexName) {
                $table->unique(
                    ['employee_id', 'deduction_type_id', 'account_number'],
                    $indexName
                );
            });
        }

        // ── 4. Deactivate WHT (id 21) — duplicate of WITHHOLDING_TAX ──────
        DB::table('deduction_types')
            ->where('code', 'WHT')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Re-activate WHT
        DB::table('deduction_types')
            ->where('code', 'WHT')
            ->update(['is_active' => true]);

        // Drop unique index
        $indexName = 'ede_employee_type_account_unique';
        $indexExists = collect(
            DB::select("SHOW INDEX FROM employee_deduction_enrollments WHERE Key_name = '{$indexName}'")
        )->isNotEmpty();

        if ($indexExists) {
            Schema::table('employee_deduction_enrollments', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }

        // Drop account_number
        if (Schema::hasColumn('employee_deduction_enrollments', 'account_number')) {
            Schema::table('employee_deduction_enrollments', function (Blueprint $table) {
                $table->dropColumn('account_number');
            });
        }

        // Drop allow_multiple_accounts
        if (Schema::hasColumn('deduction_types', 'allow_multiple_accounts')) {
            Schema::table('deduction_types', function (Blueprint $table) {
                $table->dropColumn('allow_multiple_accounts');
            });
        }
    }
};

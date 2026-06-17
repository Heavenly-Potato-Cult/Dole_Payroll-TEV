<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'pagibig_id')) {
                $table->string('pagibig_id', 50)->nullable()->after('pagibig_no');
            }
            if (! Schema::hasColumn('employees', 'pagibig_mid_no')) {
                $table->string('pagibig_mid_no', 50)->nullable()->after('pagibig_id');
            }
            if (! Schema::hasColumn('employees', 'mp2_account_no')) {
                $table->string('mp2_account_no', 50)->nullable()->after('pagibig_mid_no');
            }
            if (! Schema::hasColumn('employees', 'hdmf_mpl_app_no')) {
                $table->string('hdmf_mpl_app_no', 80)->nullable()->after('mp2_account_no');
            }
            if (! Schema::hasColumn('employees', 'hdmf_cal_app_no')) {
                $table->string('hdmf_cal_app_no', 80)->nullable()->after('hdmf_mpl_app_no');
            }
            if (! Schema::hasColumn('employees', 'hdmf_housing_app_no')) {
                $table->string('hdmf_housing_app_no', 80)->nullable()->after('hdmf_cal_app_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [
                'pagibig_id',
                'pagibig_mid_no',
                'mp2_account_no',
                'hdmf_mpl_app_no',
                'hdmf_cal_app_no',
                'hdmf_housing_app_no',
            ];

            $existing = array_filter($cols, fn ($col) => Schema::hasColumn('employees', $col));
            if (! empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};

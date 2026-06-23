<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('allowance_types')) {
            return;
        }

        Schema::table('allowance_types', function (Blueprint $table) {
            if (! Schema::hasColumn('allowance_types', 'is_taxable')) {
                $table->boolean('is_taxable')->default(false)->after('description');
            }
            if (! Schema::hasColumn('allowance_types', 'is_gsis_deductible')) {
                $table->boolean('is_gsis_deductible')->default(false)->after('is_taxable');
            }
            if (! Schema::hasColumn('allowance_types', 'is_philhealth_deductible')) {
                $table->boolean('is_philhealth_deductible')->default(false)->after('is_gsis_deductible');
            }
            if (! Schema::hasColumn('allowance_types', 'is_pagibig_deductible')) {
                $table->boolean('is_pagibig_deductible')->default(false)->after('is_philhealth_deductible');
            }
            if (! Schema::hasColumn('allowance_types', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_pagibig_deductible');
            }
            if (! Schema::hasColumn('allowance_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('allowance_types', 'sort_order') && Schema::hasColumn('allowance_types', 'display_order')) {
            foreach (\DB::table('allowance_types')->get() as $row) {
                \DB::table('allowance_types')->where('id', $row->id)->update([
                    'display_order' => $row->sort_order ?? 0,
                ]);
            }
        }

        foreach (['employee_allowances', 'allowance_batches', 'allowance_entries'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        // Non-destructive upgrade — no down migration.
    }
};

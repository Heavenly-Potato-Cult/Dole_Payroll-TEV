<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only drop it if it still exists — an earlier migration
        // (remove_unique_constraint_from_plantilla_item_no) may have
        // already removed it.
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'employees')
            ->where('index_name', 'employees_plantilla_item_no_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique('employees_plantilla_item_no_unique');
            });
        }
    }

    public function down(): void
    {
        // Only re-add it if it's not already there
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'employees')
            ->where('index_name', 'employees_plantilla_item_no_unique')
            ->exists();

        if (! $indexExists) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique('plantilla_item_no');
            });
        }
    }
};

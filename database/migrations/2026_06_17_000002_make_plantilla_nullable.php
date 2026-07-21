<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the unique index only if it still exists — an earlier
            // migration (remove_plantilla_unique_constraint) may have
            // already dropped it.
            $indexExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'employees')
                ->where('index_name', 'employees_plantilla_item_no_unique')
                ->exists();

            if ($indexExists) {
                $table->dropUnique(['plantilla_item_no']);
            }

            $table->string('plantilla_item_no', 50)->nullable()->change();

            // Make salary_grade and step nullable since HRIS API may return null
            $table->unsignedTinyInteger('salary_grade')->nullable()->change();
            $table->unsignedTinyInteger('step')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('plantilla_item_no', 50)->nullable(false)->unique()->change();
            $table->unsignedTinyInteger('salary_grade')->nullable(false)->change();
            $table->unsignedTinyInteger('step')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop unique constraint first, then make nullable
            $table->dropUnique(['plantilla_item_no']);
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

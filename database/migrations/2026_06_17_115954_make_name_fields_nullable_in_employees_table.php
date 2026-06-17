<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Make name fields nullable to handle cases where HRIS API returns null values
            $table->string('last_name')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            // middle_name is already nullable, but ensure it stays that way
            $table->string('middle_name')->nullable()->change();
            // Make basic_salary nullable as HRIS API may return null
            $table->decimal('basic_salary', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert to NOT NULL for name fields
            $table->string('last_name')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            // middle_name was originally nullable, keep it that way
            $table->string('middle_name')->nullable()->change();
            // Revert basic_salary to NOT NULL
            $table->decimal('basic_salary', 12, 2)->nullable(false)->change();
        });
    }
};

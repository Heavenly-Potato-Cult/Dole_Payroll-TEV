<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            // Add position and salary grade fields for salary differential, NOSI, NOSA
            $table->unsignedTinyInteger('old_step')->nullable()->after('differential_amount')->comment('Old step increment');
            $table->unsignedTinyInteger('new_step')->nullable()->after('old_step')->comment('New step increment');
            $table->unsignedTinyInteger('old_salary_grade')->nullable()->after('new_step')->comment('Old salary grade (1-33)');
            $table->unsignedTinyInteger('new_salary_grade')->nullable()->after('old_salary_grade')->comment('New salary grade (1-33)');
            $table->string('old_position', 255)->nullable()->after('new_salary_grade')->comment('Old position title');
            $table->string('new_position', 255)->nullable()->after('old_position')->comment('New position title');
        });
    }

    public function down(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->dropColumn([
                'old_step',
                'new_step',
                'old_salary_grade',
                'new_salary_grade',
                'old_position',
                'new_position',
            ]);
        });
    }
};

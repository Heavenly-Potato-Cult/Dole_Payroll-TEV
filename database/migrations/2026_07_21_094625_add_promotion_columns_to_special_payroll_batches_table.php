<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * special_payroll_batches has always listed old_step / new_step /
 * old_salary_grade / new_salary_grade / old_position / new_position in
 * SpecialPayrollBatch::$fillable (and in that model's own docblock, which
 * references a migration that was supposed to create them) — but comparing
 * against the actual table dump, these 6 columns were never created. This
 * is what throws "Unknown column 'old_step'" on differentialStore().
 *
 * Types:
 *   old_step / new_step               — small integer (typical step range 1–8)
 *   old_salary_grade / new_salary_grade — small integer (SG range 1–33)
 *   old_position / new_position       — free-text position title snapshot
 *                                        (position titles change wording
 *                                        over time; storing the string as
 *                                        of the promotion, not an FK, keeps
 *                                        historical batches accurate even
 *                                        if the position title is later
 *                                        renamed elsewhere)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_payroll_batches', function (Blueprint $table) {
            $table->unsignedTinyInteger('old_step')->nullable()->after('differential_amount');
            $table->unsignedTinyInteger('new_step')->nullable()->after('old_step');
            $table->unsignedTinyInteger('old_salary_grade')->nullable()->after('new_step');
            $table->unsignedTinyInteger('new_salary_grade')->nullable()->after('old_salary_grade');
            $table->string('old_position')->nullable()->after('new_salary_grade');
            $table->string('new_position')->nullable()->after('old_position');
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

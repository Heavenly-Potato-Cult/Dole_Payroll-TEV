<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)
                  ->comment('newly_hired, salary_differential, nosi, nosa, step_increment, generic_special');
            $table->string('title');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('effectivity_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Linked employee (for single-employee special payrolls)
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();

            // Salary differential fields
            $table->decimal('old_basic_salary', 12, 2)->nullable();
            $table->decimal('new_basic_salary', 12, 2)->nullable();
            $table->decimal('differential_amount', 12, 2)->nullable();
            $table->decimal('pro_rated_days', 5, 3)->nullable()
                  ->comment('Days worked out of 22-day denominator');

            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->string('status', 30)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_payroll_batches');
    }
};

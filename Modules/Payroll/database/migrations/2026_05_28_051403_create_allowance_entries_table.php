<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowance_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allowance_batch_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('allowance_type_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('gsis_deduction', 10, 2)->default(0);
            $table->decimal('philhealth_deduction', 10, 2)->default(0);
            $table->decimal('pagibig_deduction', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allowance_batch_id')->references('id')->on('allowance_batches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('allowance_type_id')->references('id')->on('allowance_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowance_entries');
    }
};

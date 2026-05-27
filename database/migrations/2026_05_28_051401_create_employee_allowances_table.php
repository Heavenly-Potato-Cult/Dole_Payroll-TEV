<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('allowance_type_id');
            $table->decimal('amount', 10, 2);
            $table->date('effectivity_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('allowance_type_id')->references('id')->on('allowance_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entry_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_entry_id');
            $table->unsignedBigInteger('allowance_type_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->timestamps();

            $table->foreign('payroll_entry_id')
                ->references('id')
                ->on('payroll_entries')
                ->onDelete('cascade');

            $table->foreign('allowance_type_id')
                ->references('id')
                ->on('allowance_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_allowances');
    }
};

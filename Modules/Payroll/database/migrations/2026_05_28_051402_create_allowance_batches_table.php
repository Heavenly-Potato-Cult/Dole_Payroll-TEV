<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowance_batches', function (Blueprint $table) {
            $table->id();
            $table->integer('period_year');
            $table->integer('period_month');
            $table->string('cutoff', 10);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 50)->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('prepared_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('released_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowance_batches');
    }
};

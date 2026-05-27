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
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('hr_approved_by')->nullable()->after('prepared_at');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_batches', function (Blueprint $table) {
            $table->dropColumn(['hr_approved_by', 'hr_approved_at']);
        });
    }
};

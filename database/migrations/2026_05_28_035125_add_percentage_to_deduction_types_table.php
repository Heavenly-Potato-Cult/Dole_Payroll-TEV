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
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->decimal('percentage', 5, 2)->nullable()
                  ->after('default_amount')
                  ->comment('Percentage of basic salary for deduction (e.g., 5.00 for 5%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }
};

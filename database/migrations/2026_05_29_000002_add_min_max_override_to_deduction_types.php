<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->decimal('min_override_amount', 12, 2)->nullable()
                ->comment('Minimum override amount for this deduction type (e.g., for WHT)');
            $table->decimal('max_override_amount', 12, 2)->nullable()
                ->comment('Maximum override amount for this deduction type (e.g., for WHT)');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn(['min_override_amount', 'max_override_amount']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('allowance_batches', function (Blueprint $table) {
            $table->date('period_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('allowance_batches', function (Blueprint $table) {
            // Restore NOT NULL — fill any existing nulls with period_start first
            // to avoid constraint errors on rollback.
            DB::statement('UPDATE allowance_batches SET period_end = period_start WHERE period_end IS NULL');
            $table->date('period_end')->nullable(false)->change();
        });

    }
};

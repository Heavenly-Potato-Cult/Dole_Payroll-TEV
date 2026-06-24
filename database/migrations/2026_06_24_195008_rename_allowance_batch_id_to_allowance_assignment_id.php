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
        Schema::table('allowance_assignment_entries', function (Blueprint $table) {
            $table->dropForeign('allowance_entries_allowance_batch_id_foreign');
            $table->renameColumn('allowance_batch_id', 'allowance_assignment_id');
            $table->foreign('allowance_assignment_id')->references('id')->on('allowance_assignments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allowance_assignment_entries', function (Blueprint $table) {
            $table->dropForeign(['allowance_assignment_id']);
            $table->renameColumn('allowance_assignment_id', 'allowance_batch_id');
            $table->foreign('allowance_batch_id', 'allowance_entries_allowance_batch_id_foreign')->references('id')->on('allowance_assignments');
        });
    }
};

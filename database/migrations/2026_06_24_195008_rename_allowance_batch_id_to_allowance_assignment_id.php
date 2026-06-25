<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the old FK on allowance_entries
        Schema::table('allowance_entries', function (Blueprint $table) {
            $table->dropForeign('allowance_entries_allowance_batch_id_foreign');
        });

        // 2. Rename the column BEFORE renaming the table
        Schema::table('allowance_entries', function (Blueprint $table) {
            $table->renameColumn('allowance_batch_id', 'allowance_assignment_id');
        });

        // 3. Rename the tables
        Schema::rename('allowance_entries', 'allowance_assignment_entries');
        Schema::rename('allowance_batches', 'allowance_assignments');

        // 4. Re-add the FK now that both tables have their final names
        Schema::table('allowance_assignment_entries', function (Blueprint $table) {
            $table->foreign('allowance_assignment_id')
                  ->references('id')
                  ->on('allowance_assignments')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('allowance_assignment_entries', function (Blueprint $table) {
            $table->dropForeign(['allowance_assignment_id']);
        });

        Schema::rename('allowance_assignments', 'allowance_batches');
        Schema::rename('allowance_assignment_entries', 'allowance_entries');

        Schema::table('allowance_entries', function (Blueprint $table) {
            $table->renameColumn('allowance_assignment_id', 'allowance_batch_id');
        });

        Schema::table('allowance_entries', function (Blueprint $table) {
            $table->foreign('allowance_batch_id', 'allowance_entries_allowance_batch_id_foreign')
                  ->references('id')
                  ->on('allowance_batches')
                  ->onDelete('cascade');
        });
    }
};

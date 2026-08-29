<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable at the DB level (existing rows, and to avoid a hard FK
     * dependency at migration time before the seeder has run). The
     * "never leave a true null" guarantee is enforced at the application
     * level — see Employee::booted() creating hook, which defaults new
     * records to the "UNASSIGNED / FOR PSIPOP REVIEW" bucket.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('psipop_office_id')
                ->nullable()
                ->after('division_id')
                ->constrained('psipop_offices')
                ->nullOnDelete();

            $table->index('psipop_office_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('psipop_office_id');
        });
    }
};

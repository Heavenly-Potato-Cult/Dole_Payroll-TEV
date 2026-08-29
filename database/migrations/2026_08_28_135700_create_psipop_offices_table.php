<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PSIPOP (Personal Services Itemization and Plantilla of Personnel) offices.
     *
     * A DBM staffing document grouping positions by office, in a fixed
     * DBM-mandated display order. Deliberately NOT a reuse of `divisions`:
     * PSIPOP groupings don't map 1:1 onto the org's 4 divisions (ORD, IMSD,
     * TSSD, LLCD), and `divisions` has no ordering column.
     */
    public function up(): void
    {
        Schema::create('psipop_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psipop_offices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: deduction_categories
 *
 * Extracts categories out of the hard-coded array in DeductionTypeController
 * into a first-class database table, enabling dynamic category management.
 *
 * Seeded with the seven original categories so existing deduction_types rows
 * (which store the category key as a varchar) remain valid without any data
 * migration on that table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_categories', function (Blueprint $table) {
            $table->id();

            // The short key stored in deduction_types.category (e.g. 'pagibig')
            $table->string('key', 50)->unique();

            // Human-readable label shown in the UI (e.g. 'PAG-IBIG / HDMF')
            $table->string('label', 100);

            // Controls the display order of category groups on the index page
            $table->unsignedSmallInteger('display_order')->default(0)->index();

            // Soft-delete flag: inactive categories are hidden from the "Add" form
            // but existing deduction_types referencing them still work.
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // ── Seed the original seven categories ───────────────────────────
        $now = now();
        DB::table('deduction_categories')->insert([
            ['key' => 'pagibig',    'label' => 'PAG-IBIG / HDMF',   'display_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'philhealth', 'label' => 'PhilHealth',         'display_order' => 2,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'gsis',       'label' => 'GSIS',               'display_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'other_gov',  'label' => 'Government / Tax',   'display_order' => 4,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'loan',       'label' => 'Bank Loans',         'display_order' => 5,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'caress',     'label' => 'CARESS IX',          'display_order' => 6,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'misc',       'label' => 'Miscellaneous',      'display_order' => 7,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_categories');
    }
};

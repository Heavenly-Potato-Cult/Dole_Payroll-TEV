<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Creates the deduction_type_categories table and seeds the 8 categories
 * that are currently hardcoded in DeductionTypeController::fallbackCategoryLabels().
 *
 * Backward-compat strategy
 * ────────────────────────────────────────────────────────────────────────
 * The existing deduction_types.category string column is KEPT as the
 * authoritative runtime key.  DeductionService and PayrollComputationService
 * match on this string (e.g. 'pagibig', 'loan'), so it must not change.
 *
 * The new deduction_type_category_id FK is a UI convenience column only —
 * it lets the CMS display a managed label/description for each category
 * without touching the service layer.  Both columns are synced on save by
 * DeductionTypeController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_type_categories', function (Blueprint $table) {
            $table->id();

            // Matches the existing deduction_types.category string values
            // (e.g. 'pagibig', 'loan').  Unique and immutable after creation
            // — same contract as DeductionType::code.
            $table->string('code', 50)->unique()
                  ->comment('Matches deduction_types.category. Immutable after creation.');

            $table->string('name', 150)
                  ->comment('Human-readable label shown in the UI (e.g. "PAG-IBIG / HDMF").');

            $table->text('description')->nullable()
                  ->comment('Optional longer description shown in the category CMS.');

            $table->unsignedSmallInteger('display_order')->default(0)
                  ->comment('Controls sort order in the UI.');

            $table->boolean('is_active')->default(true)
                  ->comment('Inactive categories are hidden from dropdowns but not deleted.');

            // Soft deletes — categories in use must never be hard-deleted.
            $table->softDeletes();

            $table->timestamps();

            $table->index('display_order');
            $table->index('is_active');
        });

        // ── Add FK column to deduction_types ─────────────────────────────
        // Nullable so existing rows are not broken before the seeder runs.
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->foreignId('deduction_type_category_id')
                  ->nullable()
                  ->after('category')
                  ->constrained('deduction_type_categories')
                  ->nullOnDelete()
                  ->comment('UI FK to deduction_type_categories. category string remains the runtime key.');
        });

        // ── Seed the 8 built-in categories ───────────────────────────────
        $now = now();
        $seed = [
            ['code' => 'pagibig',    'name' => 'PAG-IBIG / HDMF',    'display_order' => 10, 'description' => 'Mandatory Pag-IBIG Fund (HDMF) contributions and housing loans.'],
            ['code' => 'philhealth', 'name' => 'PhilHealth',          'display_order' => 20, 'description' => 'Philippine Health Insurance Corporation mandatory premiums.'],
            ['code' => 'gsis',       'name' => 'GSIS',                'display_order' => 30, 'description' => 'Government Service Insurance System contributions and loans.'],
            ['code' => 'other_gov',  'name' => 'Government / Tax',    'display_order' => 40, 'description' => 'Withholding tax and other government-mandated deductions not covered by the agencies above.'],
            ['code' => 'loan',       'name' => 'Bank Loans',          'display_order' => 50, 'description' => 'Bank and external loan amortizations. Always per-employee regardless of lock setting.'],
            ['code' => 'caress',     'name' => 'CARESS IX',           'display_order' => 60, 'description' => 'CARESS IX cooperative / union deductions. Always per-employee.'],
            ['code' => 'misc',       'name' => 'Miscellaneous',       'display_order' => 70, 'description' => 'Catch-all category for deductions that do not fit other categories.'],
            ['code' => 'other',      'name' => 'Other Deductions',    'display_order' => 80, 'description' => 'General non-government deductions not classified elsewhere.'],
        ];

        foreach ($seed as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('deduction_type_categories')->insert($seed);

        // ── Back-fill FK on existing deduction_types rows ────────────────
        $categories = DB::table('deduction_type_categories')->pluck('id', 'code');

        foreach ($categories as $code => $id) {
            DB::table('deduction_types')
              ->where('category', $code)
              ->update(['deduction_type_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropForeign(['deduction_type_category_id']);
            $table->dropColumn('deduction_type_category_id');
        });

        Schema::dropIfExists('deduction_type_categories');
    }
};
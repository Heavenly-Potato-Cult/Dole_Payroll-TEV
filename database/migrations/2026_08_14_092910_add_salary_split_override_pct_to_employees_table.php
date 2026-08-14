<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Scaffolded with:
//   php artisan make:migration add_salary_split_override_pct_to_employees_table --table=employees
// then replace the generated body with this file's up()/down().

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Nullable by design. NULL = keep today's behavior: the 1st/2nd
            // cutoff split is derived from actual attendance days
            // (PayrollComputationService::computeCutoffSplit()), which is
            // already ~50/50 for any employee with full attendance — this
            // is what "default stays 50/50" means in practice, with zero
            // change for employees who never touch the new field.
            //
            // A non-null value (0–100) is a per-employee override: % of
            // NET pay disbursed at the 1st cutoff, fixed regardless of
            // actual attendance split, for employees who explicitly want
            // a different distribution.
            $table->decimal('salary_split_override_pct', 5, 2)
                ->nullable()
                ->after('pera')
                ->comment('% of net pay disbursed at 1st cutoff. NULL = use actual attendance ratio (default).');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('salary_split_override_pct');
        });
    }
};

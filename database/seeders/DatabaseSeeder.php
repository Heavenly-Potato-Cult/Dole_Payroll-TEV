<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            // Roles must exist before SuperAdminSeeder assigns one.
            RoleSeeder::class,

            // Creates the admin@dole9.gov.ph test account.
            SuperAdminSeeder::class,

            // Deduction type data — formula rates first, then the
            // PhilHealth/GSIS split + lock/unlock updates that build
            // on top of the base deduction_types rows.
            DeductionTypeFormulaRateSeeder::class,
            DeductionTypeUpdateSeeder::class,

            // PSIPOP office lookup rows — must exist before any employee
            // is created, since Employee::booted() defaults new records
            // to the "Unassigned" bucket via PsipopOffice::unassignedId().
            PsipopOfficeSeeder::class,
        ]);
    }
}

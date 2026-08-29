<?php

namespace Database\Seeders;

use App\SharedKernel\Models\PsipopOffice;
use Illuminate\Database\Seeder;

class PsipopOfficeSeeder extends Seeder
{
    /**
     * Order matches the DBM PSIPOP document exactly, confirmed against an
     * actual DOLE payroll export. UNASSIGNED / FOR PSIPOP REVIEW is always
     * last: it's the catch-all every employee falls into by default
     * (see Employee::booted()), so it should read as "needs review",
     * not as a legitimate section of the plantilla.
     */
    public function run(): void
    {
        $offices = [
            'OFFICE OF THE DIRECTOR',
            'INTERNAL MNGT SERVICES DIVISION',
            'TECHNICAL SERVICES & SUPPORT DIVISION',
            'FIELD OFFICES',
            'INSPECTORS',
            'NEW PLANTILLA',
            PsipopOffice::NAME_UNASSIGNED,
        ];

        foreach ($offices as $sortOrder => $name) {
            PsipopOffice::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder + 1, 'is_active' => true]
            );
        }
    }
}

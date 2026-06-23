<?php

namespace Modules\Allowances\Database\Seeders;

use App\SharedKernel\Models\Employee;
use Illuminate\Database\Seeder;
use Modules\Allowances\Models\AllowanceType;
use Modules\Allowances\Models\EmployeeAllowance;

class AllowancesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'code'          => 'PERA',
                'name'          => 'PERA',
                'description'   => 'Personnel Economic Relief Allowance',
                'display_order' => 1,
            ],
            [
                'code'          => 'RATA',
                'name'          => 'RATA',
                'description'   => 'Representation and Transportation Allowance',
                'display_order' => 2,
            ],
        ];

        foreach ($defaults as $row) {
            AllowanceType::firstOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['is_active' => true])
            );
        }

        $this->syncLegacyEmployeePera();
    }

    /**
     * Migrate standing PERA amounts from employees.pera into employee_allowances.
     */
    private function syncLegacyEmployeePera(): void
    {
        $peraType = AllowanceType::where('code', 'PERA')->first();
        if (! $peraType) {
            return;
        }

        Employee::where('pera', '>', 0)->chunkById(100, function ($employees) use ($peraType) {
            foreach ($employees as $employee) {
                EmployeeAllowance::firstOrCreate(
                    [
                        'employee_id'       => $employee->id,
                        'allowance_type_id' => $peraType->id,
                    ],
                    [
                        'amount'           => $employee->pera,
                        'effectivity_date' => now()->startOfYear()->toDateString(),
                        'is_active'        => true,
                        'remarks'          => 'Migrated from legacy employees.pera column',
                    ]
                );
            }
        });
    }
}

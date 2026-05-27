<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payroll\Models\DeductionType;
use Illuminate\Support\Facades\DB;

class DeductionTypeUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // Add PHILHEALTH_EMPLOYER and PHILHEALTH_EMPLOYEE deduction types
        $philhealthTypes = [
            [
                'code' => 'PHILHEALTH_EMPLOYEE',
                'name' => 'PhilHealth Employee Share',
                'category' => 'philhealth',
                'display_order' => 6,
                'is_computed' => false,
                'is_locked' => false,
                'is_active' => true,
                'percentage' => 2.50, // 50% of 5% premium
                'notes' => 'Employee share of PhilHealth premium (50% of total 5%)',
            ],
            [
                'code' => 'PHILHEALTH_EMPLOYER',
                'name' => 'PhilHealth Employer Share',
                'category' => 'philhealth',
                'display_order' => 7,
                'is_computed' => false,
                'is_locked' => true, // Employer share should be locked
                'is_active' => true,
                'percentage' => 2.50, // 50% of 5% premium
                'notes' => 'Employer share of PhilHealth premium (50% of total 5%)',
            ],
        ];

        foreach ($philhealthTypes as $type) {
            DeductionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        // Update existing PHILHEALTH to be computed (formula-driven)
        $existingPhilhealth = DeductionType::where('code', 'PHILHEALTH')->first();
        if ($existingPhilhealth) {
            $existingPhilhealth->update([
                'is_computed' => true,
                'is_locked' => false,
                'percentage' => null,
                'notes' => 'PhilHealth Mandatory Premium - Formula computed (5% of basic, floor ₱500, ceiling ₱5,000)',
            ]);
        }

        // Update GSIS types - lock only GSIS, unlock others
        // Set GSIS government share to 12% and employee share to 9%
        $gsisEmployee = DeductionType::where('code', 'GSIS_LIFE_RETIREMENT')->first();
        if ($gsisEmployee) {
            $gsisEmployee->update([
                'is_locked' => true, // Lock GSIS
                'percentage' => 9.00,
                'notes' => 'GSIS Life & Retirement - Employee Share (9% of basic)',
            ]);
        }

        // Add GSIS_GOVERNMENT_SHARE type
        DeductionType::updateOrCreate(
            ['code' => 'GSIS_GOVERNMENT_SHARE'],
            [
                'name' => 'GSIS Government Share',
                'category' => 'gsis',
                'display_order' => 8,
                'is_computed' => false,
                'is_locked' => true, // Lock GSIS
                'is_active' => true,
                'percentage' => 12.00,
                'notes' => 'GSIS Government Share (12% of basic)',
            ]
        );

        // Unlock withholding tax
        $wht = DeductionType::where('code', 'WITHHOLDING_TAX')->first();
        if ($wht) {
            $wht->update([
                'is_locked' => false, // Unlock withholding tax
                'notes' => 'Withholding Tax (BIR TRAIN Law) - Employees can set min/max values',
            ]);
        }

        // Unlock other deduction types (non-GSIS)
        DeductionType::where('category', '!=', 'gsis')
            ->where('is_locked', true)
            ->update(['is_locked' => false]);
    }
}

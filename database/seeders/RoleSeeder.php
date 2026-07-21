<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Add every role your app actually uses here — this currently only
     * guarantees 'super_admin' exists so SuperAdminSeeder doesn't fail
     * on a fresh install. Extend the $roles array as you identify the
     * other roles used across the app (e.g. payroll_officer, hr_staff).
     */
    public function run(): void
    {
        $roles = [
            'super_admin',
            // 'payroll_officer',
            // 'hr_staff',
            // 'employee',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->command->info('Roles seeded.');
    }
}

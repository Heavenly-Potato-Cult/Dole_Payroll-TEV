<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            [
                'email' => 'admin@dole9.gov.ph',
            ],
            [
                'name' => 'Admin',
                'password' => bcrypt('pass123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super-admin role
        $user->assignRole('super_admin');

        $this->command->info('Super Admin user created or already exists.');
    }
}

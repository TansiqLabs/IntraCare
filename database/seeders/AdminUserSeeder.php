<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@intracare.local'],
            [
                'employee_id' => 'EMP-0001',
                'name' => 'System Administrator',
                // Do NOT wrap in Hash::make() — User model's 'hashed' cast handles it.
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $admin->forceFill(['email_verified_at' => now()])->save();

        $admin->assignRole('Admin');
    }
}

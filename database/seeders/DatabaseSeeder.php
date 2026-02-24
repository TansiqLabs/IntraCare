<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: Admin user is created via the web-based Setup Wizard (/setup).
     * RolesAndPermissionsSeeder is called by the setup wizard automatically.
     * This seeder is for manual/CLI use only.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
    }
}

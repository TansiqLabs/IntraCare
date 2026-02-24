<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_seeder_creates_expected_roles(): void
    {
        (new RolesAndPermissionsSeeder())->run();

        foreach (['Admin', 'Doctor', 'Nurse', 'Pathologist', 'Pharmacist', 'Receptionist'] as $roleName) {
            $this->assertTrue(Role::where('name', $roleName)->exists(), "Missing role: {$roleName}");
        }

        $this->assertGreaterThan(10, Permission::count(), 'Expected a non-trivial permission set.');
    }
}

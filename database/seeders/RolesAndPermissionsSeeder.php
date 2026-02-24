<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ──────────────────────────────────────────────
        // Define Permissions
        // ──────────────────────────────────────────────

        $permissions = [
            // Patient
            'view-patients',
            'create-patients',
            'edit-patients',
            'delete-patients',

            // Visits
            'view-visits',
            'create-visits',
            'edit-visits',
            'delete-visits',

            // Prescriptions
            'view-prescriptions',
            'create-prescriptions',
            'edit-prescriptions',

            // Lab Orders
            'view-lab-orders',
            'create-lab-orders',
            'edit-lab-orders',
            'delete-lab-orders',

            // Lab Reports
            'view-lab-reports',
            'enter-lab-results',
            'verify-lab-results',
            'print-lab-reports',

            // Lab Samples
            'collect-lab-samples',
            'receive-lab-samples',
            'reject-lab-samples',

            // Lab Catalog Management
            'manage-lab-catalog',

            // Invoices & Billing
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices',
            'receive-payments',

            // Pharmacy (future)
            'view-pharmacy',
            'manage-pharmacy-inventory',
            'dispense-drugs',

            // Queue & Token (future)
            'manage-queue',
            'view-queue-display',

            // Accounting (future)
            'view-accounting',
            'manage-accounting',

            // Administration
            'manage-users',
            'manage-roles',
            'view-audit-logs',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // ──────────────────────────────────────────────
        // Define Roles & Assign Permissions
        // ──────────────────────────────────────────────

        // Admin — everything
        $admin = Role::findOrCreate('Admin', 'web');
        $admin->givePermissionTo(Permission::all());

        // Doctor
        $doctor = Role::findOrCreate('Doctor', 'web');
        $doctor->givePermissionTo([
            'view-patients', 'create-patients', 'edit-patients',
            'view-visits', 'create-visits', 'edit-visits',
            'view-prescriptions', 'create-prescriptions', 'edit-prescriptions',
            'view-lab-orders', 'create-lab-orders',
            'view-lab-reports', 'print-lab-reports',
            'view-invoices',
        ]);

        // Nurse
        $nurse = Role::findOrCreate('Nurse', 'web');
        $nurse->givePermissionTo([
            'view-patients',
            'view-visits', 'edit-visits', // vitals entry
            'manage-queue', 'view-queue-display',
            'collect-lab-samples',
        ]);

        // Pathologist
        $pathologist = Role::findOrCreate('Pathologist', 'web');
        $pathologist->givePermissionTo([
            'view-patients',
            'view-lab-orders',
            'view-lab-reports', 'enter-lab-results', 'verify-lab-results', 'print-lab-reports',
            'collect-lab-samples', 'receive-lab-samples', 'reject-lab-samples',
            'manage-lab-catalog',
        ]);

        // Pharmacist
        $pharmacist = Role::findOrCreate('Pharmacist', 'web');
        $pharmacist->givePermissionTo([
            'view-patients',
            'view-prescriptions',
            'view-pharmacy', 'manage-pharmacy-inventory', 'dispense-drugs',
            'view-invoices', 'create-invoices', 'receive-payments',
        ]);

        // Receptionist
        $receptionist = Role::findOrCreate('Receptionist', 'web');
        $receptionist->givePermissionTo([
            'view-patients', 'create-patients', 'edit-patients',
            'view-visits', 'create-visits',
            'manage-queue', 'view-queue-display',
            'view-invoices', 'create-invoices', 'receive-payments',
            'view-lab-orders',
        ]);
    }
}

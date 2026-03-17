<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions aligned with Dayare modules (Operations, CRM, Settings).
     * Use in middleware: permission:manage businesses, or @can('manage businesses') in Blade.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Operations
            'manage businesses',
            'manage facilities',
            'manage inspectors',
            'manage animal intakes',
            'manage slaughter plans',
            'manage slaughter executions',
            'manage batches',
            'manage ante-mortem',
            'manage post-mortem',
            'manage certificates',
            'manage warehouse',
            'manage transport',
            'manage delivery confirmations',
            'view compliance',
            'view divisions',
            // CRM & HR
            'manage employees',
            'manage suppliers',
            'manage contracts',
            'view crm',
            'manage clients',
            'manage demands',
            'view recipients',
            // Settings
            'manage settings',
            'manage species',
            'manage units',
            // Tenant user management
            'manage tenant users',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->all();
        $operations = [
            'manage businesses', 'manage facilities', 'manage inspectors', 'manage animal intakes',
            'manage slaughter plans', 'manage slaughter executions', 'manage batches',
            'manage ante-mortem', 'manage post-mortem', 'manage certificates', 'manage warehouse',
            'manage transport', 'manage delivery confirmations', 'view compliance', 'view divisions',
        ];
        $crm = [
            'manage employees', 'manage suppliers', 'manage contracts', 'view crm',
            'manage clients', 'manage demands', 'view recipients',
        ];
        $settings = ['manage settings', 'manage species', 'manage units'];
        $tenantUsers = ['manage tenant users'];

        // Owner: full access (all permissions for their tenant)
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $owner->syncPermissions(array_merge($allPermissions, $tenantUsers));

        // Manager: operations + CRM, no settings
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions(array_merge($operations, $crm));

        // Staff: view-only for key areas (customize as needed)
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'view compliance', 'view divisions', 'view crm', 'view recipients',
            'manage animal intakes', 'manage slaughter plans', 'manage slaughter executions',
            'manage batches', 'manage ante-mortem', 'manage post-mortem', 'manage certificates',
            'manage warehouse', 'manage transport', 'manage delivery confirmations',
        ]);

        $this->command?->info('Roles and permissions seeded: owner, manager, staff.');
    }
}

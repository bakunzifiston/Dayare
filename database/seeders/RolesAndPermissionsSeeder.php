<?php

namespace Database\Seeders;

use App\Models\BusinessUser;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (BusinessUser::ACTION_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (BusinessUser::ROLE_PERMISSION_MAP as $role => $permissions) {
            $spatieRole = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $spatieRole->syncPermissions($permissions);
        }

        foreach (['owner', 'manager', 'staff'] as $legacyRole) {
            $legacy = Role::where('name', $legacyRole)->where('guard_name', 'web')->first();
            if ($legacy) {
                $legacy->syncPermissions([]);
            }
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', 'manage %')
            ->delete();

        $this->command?->info('Roles and permissions seeded: org_admin, operations_manager, compliance_officer, inspector, transport_manager.');
    }
}

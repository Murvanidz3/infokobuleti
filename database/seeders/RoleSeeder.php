<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and staff permissions. Safe to re-run: permissions are synced to the enum.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web')->syncPermissions(
                array_map(fn (PermissionEnum $permission): string => $permission->value, PermissionEnum::forRole($role)),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

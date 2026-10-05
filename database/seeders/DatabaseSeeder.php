<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        foreach (config('access.permissions') as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Admin', 'Manager', 'User'] as $name) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(
                    config('access.permissions')
                );
            }
        }

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Collect all distinct permissions defined across groups
        $adminPermissions = [];
        foreach (config('permission.admin', []) as $group => $permissions) {
            foreach ($permissions as $permission) {
                $adminPermissions[$permission] = $permission;
            }
        }

        $keuanganPermissions = [];
        foreach (config('permission.keuangan', []) as $group => $permissions) {
            foreach ($permissions as $permission) {
                $keuanganPermissions[$permission] = $permission;
            }
        }

        $pengurusPermissions = [];
        foreach (config('permission.pengurus', []) as $group => $permissions) {
            foreach ($permissions as $permission) {
                $pengurusPermissions[$permission] = $permission;
            }
        }

        $guruPermissions = [];
        foreach (config('permission.guru', []) as $group => $permissions) {
            foreach ($permissions as $permission) {
                $guruPermissions[$permission] = $permission;
            }
        }

        $santriPermissions = [];
        foreach (config('permission.santri', []) as $group => $permissions) {
            foreach ($permissions as $permission) {
                $santriPermissions[$permission] = $permission;
            }
        }

        // Create all permissions idempotently
        $allPermissions = array_unique(array_merge(
            array_keys($adminPermissions),
            array_keys($guruPermissions)
        ));

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Define roles and sync permissions
        $roles = [
            'Administrator' => array_values($adminPermissions),
            'Keuangan' => array_values($keuanganPermissions),
            'Pengurus' => array_values($pengurusPermissions),
            'Guru' => array_values($guruPermissions),
            'Santri' => array_values($santriPermissions),
            'Alumni' => [],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}

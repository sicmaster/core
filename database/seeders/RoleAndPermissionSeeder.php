<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotent: safe to run multiple times (firstOrCreate / syncPermissions).
     * No tenant_id — single-tenant (invariant #3).
     * Standard Eloquent only — DB-agnostic (invariant #2).
     */
    public function run(): void
    {
        // Reset cached roles and permissions so fresh data is picked up each run
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Permissions ---
        $accessAdmin = Permission::firstOrCreate(
            ['name' => 'access admin', 'guard_name' => 'web']
        );

        $resources = config('permissions.resources', []);
        $actions = config('permissions.actions', []);

        $allPermissions = collect([$accessAdmin]);
        $staffPermissions = collect([$accessAdmin]);

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$resource}.{$action}";
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web']
                );

                $allPermissions->push($permission);

                if ($action === 'read') {
                    $staffPermissions->push($permission);
                }
            }
        }

        // --- Roles ---
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff', 'guard_name' => 'web']
        );

        // --- Assign permissions (syncPermissions is idempotent) ---
        $adminRole->syncPermissions($allPermissions);
        $staffRole->syncPermissions($staffPermissions);
    }
}

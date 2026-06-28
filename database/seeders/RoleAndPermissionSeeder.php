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

        // --- Roles ---
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff', 'guard_name' => 'web']
        );

        // --- Assign permissions (syncPermissions is idempotent) ---
        $adminRole->syncPermissions([$accessAdmin]);
        $staffRole->syncPermissions([$accessAdmin]);
    }
}

<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Seed roles/permissions because RefreshDatabase migrates fresh each test
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    // Reset cached roles and permissions so fresh data is picked up each test
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('can assign admin role to a user', function () {
    $user = User::factory()->create();

    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
});

it('can assign staff role to a user', function () {
    $user = User::factory()->create();

    $user->assignRole('staff');

    expect($user->hasRole('staff'))->toBeTrue();
});

it('admin role has access admin permission', function () {
    $role = Role::findByName('admin', 'web');

    expect($role->hasPermissionTo('access admin'))->toBeTrue();
});

it('staff role has access admin permission', function () {
    $role = Role::findByName('staff', 'web');

    expect($role->hasPermissionTo('access admin'))->toBeTrue();
});

it('user with admin role has access admin permission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasPermissionTo('access admin'))->toBeTrue();
});

it('user with staff role has access admin permission', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');

    expect($user->hasPermissionTo('access admin'))->toBeTrue();
});

it('user with no role does not have access admin permission', function () {
    $user = User::factory()->create();

    expect($user->hasPermissionTo('access admin'))->toBeFalse();
});

it('seeder is idempotent and does not duplicate roles or permissions', function () {
    // Roles/permissions already seeded by RefreshDatabase + seeder via DatabaseSeeder
    // Run seeder a second time explicitly
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    expect(Role::where('name', 'admin')->count())->toBe(1);
    expect(Role::where('name', 'staff')->count())->toBe(1);
    expect(Permission::where('name', 'access admin')->count())->toBe(1);
});

<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

// DoD case 1: guest → redirect login
test('guests are redirected to login when accessing admin dashboard', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

// DoD case 2: user with admin role → 200
test('user with admin role can access admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

// DoD case 3: user with staff role → 200
test('user with staff role can access admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

// DoD case 4: authenticated user without role → 403
test('authenticated user without role is forbidden from admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin dashboard route has correct name', function () {
    expect(route('admin.dashboard'))->toBe(url('/admin/dashboard'));
});

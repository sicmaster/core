<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('admin can export users to csv', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.users.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition', 'attachment; filename="users.csv"');

    // The streamed response content isn't directly testable via normal assertSee
    // unless we capture it, but testing status and headers ensures the route works
    // and returns the right format.
    
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('ID,Name,Email,Roles,"Created At"');
    expect($content)->toContain($admin->email);
    expect($content)->toContain('admin');
});

test('admin can export roles to csv', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.roles.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition', 'attachment; filename="roles.csv"');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('ID,Name,"Users Count",Permissions');
    expect($content)->toContain('admin');
});

test('staff user with read permission can export', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.users.export'))
        ->assertOk();

    $this->actingAs($staff)
        ->get(route('admin.roles.export'))
        ->assertOk();
});

test('user without read permission cannot export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.export'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.roles.export'))
        ->assertForbidden();
});

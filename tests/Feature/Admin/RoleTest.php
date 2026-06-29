<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

// ── Access control ─────────────────────────────────────────────────────────────

test('guest is redirected to login when accessing roles index', function () {
    $this->get(route('admin.roles.index'))
        ->assertRedirect(route('login'));
});

test('user without permission is forbidden from roles index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('admin user can view the roles index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/roles/index')
                ->has('roles.data')
        );
});

test('staff user can view the roles index but cannot create, update, or delete', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    // Read allowed
    $this->actingAs($staff)
        ->get(route('admin.roles.index'))
        ->assertOk();

    // Create forbidden
    $this->actingAs($staff)
        ->get(route('admin.roles.create'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->post(route('admin.roles.store'), ['name' => 'New Role'])
        ->assertForbidden();

    // Update forbidden
    $role = Role::create(['name' => 'Test Role', 'guard_name' => 'web']);
    $this->actingAs($staff)
        ->get(route('admin.roles.edit', $role))
        ->assertForbidden();

    $this->actingAs($staff)
        ->put(route('admin.roles.update', $role), ['name' => 'Updated Role'])
        ->assertForbidden();

    // Delete forbidden
    $this->actingAs($staff)
        ->delete(route('admin.roles.destroy', $role))
        ->assertForbidden();
});

// ── Happy path ─────────────────────────────────────────────────────────────────

test('admin can create a role with permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Manager',
            'permissions' => ['users.read', 'roles.read'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseHas('roles', ['name' => 'Manager']);
    
    $role = Role::findByName('Manager');
    expect($role->hasPermissionTo('users.read'))->toBeTrue();
    expect($role->hasPermissionTo('roles.read'))->toBeTrue();
    expect($role->hasPermissionTo('users.create'))->toBeFalse();
});

test('admin can update a role and sync permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $role->givePermissionTo('users.read');

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'Super Editor',
            'permissions' => ['users.create', 'users.update'], // users.read removed
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseHas('roles', ['name' => 'Super Editor']);
    
    $role->refresh();
    expect($role->hasPermissionTo('users.create'))->toBeTrue();
    expect($role->hasPermissionTo('users.update'))->toBeTrue();
    expect($role->hasPermissionTo('users.read'))->toBeFalse();
});

test('admin role retains all permissions on update and cannot be renamed', function () {
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $adminRole = Role::findByName('admin');

    $this->actingAs($adminUser)
        ->put(route('admin.roles.update', $adminRole), [
            'name' => 'hacked-admin', // Attempt to rename
            'permissions' => [], // Attempt to remove all permissions
        ])
        ->assertRedirect(route('admin.roles.index'));

    $adminRole->refresh();
    expect($adminRole->name)->toBe('admin');
    
    // Check it has all permissions still
    $allPermissionsCount = Permission::count();
    expect($adminRole->permissions()->count())->toBe($allPermissionsCount);
});

test('admin can delete a role without users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'Deletable Role', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

// ── Validation & Security ───────────────────────────────────────────────────

test('name is required and unique', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Role::create(['name' => 'Existing Role', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Existing Role',
        ])
        ->assertSessionHasErrors('name');
});

test('permissions must be valid array of existing permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Valid Role',
            'permissions' => 'not-an-array',
        ])
        ->assertSessionHasErrors('permissions');

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Valid Role',
            'permissions' => ['invalid.permission'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

test('cannot delete the admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $adminRole = Role::findByName('admin');

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $adminRole))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['name' => 'admin']);
});

test('cannot delete a role assigned to users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'Used Role', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('roles', ['name' => 'Used Role']);
});

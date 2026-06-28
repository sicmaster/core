<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

// DoD case 1: admin user can view the user list
test('admin user can view the users index page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/users/index'));
});

// DoD case 2: users appear in the list
test('users index lists all non-deleted users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $other = User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 4) // 3 others + admin itself
        );
});

// DoD case 3: search filters by name
test('search filters users by name', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $admin->assignRole('admin');

    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Alice Smith')
        );
});

// DoD case 4: search filters by email
test('search filters users by email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->create(['email' => 'alice@example.com']);
    User::factory()->create(['email' => 'bob@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'alice@example']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'alice@example.com')
        );
});

// DoD case 5: soft-deleted users are excluded
test('soft-deleted users do not appear in the list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $deleted = User::factory()->create(['name' => 'Deleted User']);
    $deleted->delete(); // soft delete

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Deleted User']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 0)
        );
});

// DoD case 6: user without permission gets 403
test('user without access admin permission is forbidden', function () {
    $user = User::factory()->create(); // no role assigned

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

// DoD case 7: guest is redirected to login
test('guest is redirected to login when accessing users index', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

// DoD case 8: search term is passed back to the page
test('search query is returned as a prop', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'foo']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->where('search', 'foo')
        );
});

// ── Edge Cases ───────────────────────────────────────────────────────────────

test('staff user can view the users index page', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('search that matches nothing returns empty data', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'NonExistentUser123']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 0)
        );
});

test('pagination returns correct data on page 2', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create 20 more users (total 21 including admin)
    // Page 1 should have 15, Page 2 should have 6
    User::factory()->count(20)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->has('users.data', 6)
                ->where('users.current_page', 2)
        );
});

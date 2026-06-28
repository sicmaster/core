<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

// ── Access control ─────────────────────────────────────────────────────────────

test('guest is redirected to login when accessing create page', function () {
    $this->get(route('admin.users.create'))
        ->assertRedirect(route('login'));
});

test('user without permission is forbidden from create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.create'))
        ->assertForbidden();
});

test('admin user can view the create user page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/create')
                ->has('roles')
        );
});

// ── Happy path ─────────────────────────────────────────────────────────────────

test('admin can create a user with admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
});

test('newly created user has the assigned role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'staff',
        ]);

    $user = User::where('email', 'bob@example.com')->firstOrFail();
    expect($user->hasRole('staff'))->toBeTrue();
});

test('password is hashed and not stored as plain text', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Carol White',
            'email' => 'carol@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'staff',
        ]);

    $user = User::where('email', 'carol@example.com')->firstOrFail();
    expect(Hash::check('Password1!', $user->password))->toBeTrue();
    expect($user->password)->not->toBe('Password1!');
});

test('create redirects to users index with success flash', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Dave Brown',
            'email' => 'dave@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');
});

// ── Validation ─────────────────────────────────────────────────────────────────

test('name is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors('name');
});

test('email must be unique', function () {
    $admin = User::factory()->create(['email' => 'taken@example.com']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'staff',
        ])
        ->assertSessionHasErrors('email');
});

test('password must be confirmed', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'wrong',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors('password');
});

test('role must be a valid seeded role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Bad Role',
            'email' => 'badrole@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'superuser',
        ])
        ->assertSessionHasErrors('role');
});

test('user without permission cannot post to store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.users.store'), [
            'name' => 'Forbidden',
            'email' => 'forbidden@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'staff',
        ])
        ->assertForbidden();
});

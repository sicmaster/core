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

test('guest is redirected to login when accessing edit page', function () {
    $target = User::factory()->create();

    $this->get(route('admin.users.edit', $target))
        ->assertRedirect(route('login'));
});

test('user without permission is forbidden from edit page', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.edit', $target))
        ->assertForbidden();
});

test('admin user can view the edit page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $target))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/edit')
                ->has('roles')
                ->where('user.id', $target->id)
        );
});

// ── Pre-fill ───────────────────────────────────────────────────────────────────

test('edit page pre-fills user name and email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com']);
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $target))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('user.name', 'Alice Smith')
                ->where('user.email', 'alice@example.com')
                ->where('user.role', 'staff')
        );
});

// ── Happy path ─────────────────────────────────────────────────────────────────

test('admin can update a user name and email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'staff',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'New Name', 'email' => 'new@example.com']);
});

test('sending the same email does not trigger unique validation error (diff #1)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['email' => 'unchanged@example.com']);
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Same Email User',
            'email' => 'unchanged@example.com', // same as current
            'password' => '',
            'password_confirmation' => '',
            'role' => 'staff',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['id' => $target->id, 'email' => 'unchanged@example.com']);
});

test('password is unchanged when left blank (diff #2)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['password' => 'OriginalPass1!']);
    $target->assignRole('staff');

    $originalHash = $target->fresh()->password;

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'staff',
        ]);

    expect($target->fresh()->password)->toBe($originalHash);
    expect(Hash::check('OriginalPass1!', $target->fresh()->password))->toBeTrue();
});

test('password is updated when a new one is provided (diff #2)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['password' => 'OldPassword1!']);
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'NewPassword2@',
            'password_confirmation' => 'NewPassword2@',
            'role' => 'staff',
        ]);

    expect(Hash::check('NewPassword2@', $target->fresh()->password))->toBeTrue();
    expect(Hash::check('OldPassword1!', $target->fresh()->password))->toBeFalse();
});

test('role is synced — old role removed, new role assigned (diff #3)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'admin',
        ]);

    $fresh = $target->fresh();
    expect($fresh->hasRole('admin'))->toBeTrue();
    expect($fresh->hasRole('staff'))->toBeFalse();
});

// ── Validation ─────────────────────────────────────────────────────────────────

test('email must be unique across other users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    User::factory()->create(['email' => 'taken@example.com']);
    $target = User::factory()->create();
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => 'taken@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'staff',
        ])
        ->assertSessionHasErrors('email');
});

test('password must be confirmed when provided', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'wrong',
            'role' => 'staff',
        ])
        ->assertSessionHasErrors('password');
});

test('role must be a valid seeded role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();
    $target->assignRole('staff');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'superuser',
        ])
        ->assertSessionHasErrors('role');
});

test('user without permission cannot put to update', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->put(route('admin.users.update', $target), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'admin',
        ])
        ->assertForbidden();
});

// ── Edge Cases ───────────────────────────────────────────────────────────────

test('staff user cannot update another user', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $target = User::factory()->create(['name' => 'Old Name']);
    $target->assignRole('staff');

    $this->actingAs($staff)
        ->put(route('admin.users.update', $target), [
            'name' => 'Updated By Staff',
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'staff',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Old Name']);
});

test('submitting empty required fields returns validation errors (edit)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => '',
            'email' => '',
            'password' => '', // blank is ok for password in edit
            'password_confirmation' => '',
            'role' => '',
        ])
        ->assertSessionHasErrors(['name', 'email', 'role'])
        ->assertSessionDoesntHaveErrors(['password']);
});

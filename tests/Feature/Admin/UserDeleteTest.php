<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

// ── Access control ─────────────────────────────────────────────────────────────

test('guest is redirected to login when trying to delete user', function () {
    $target = User::factory()->create();

    $this->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('login'));
});

test('user without permission is forbidden from deleting user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('admin.users.destroy', $target))
        ->assertForbidden();
});

// ── Happy path ─────────────────────────────────────────────────────────────────

test('admin can delete a user (soft delete)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

test('deleted user does not appear in the index list anymore', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create(['name' => 'To Be Deleted']);

    // Soft delete it
    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $target));

    // Verify it is not in the list
    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/users/index')
                ->where('users.data', function ($users) use ($target) {
                    return collect($users)->doesntContain('id', $target->id);
                })
        );
});

// ── Validation / Security ────────────────────────────────────────────────────

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    // Verify admin is still in the database and not deleted
    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'deleted_at' => null,
    ]);
});

// ── Edge Cases ───────────────────────────────────────────────────────────────

test('staff user can delete another user', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $target = User::factory()->create();

    $this->actingAs($staff)
        ->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.users.index'));

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

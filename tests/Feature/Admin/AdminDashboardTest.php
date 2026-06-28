<?php

use App\Models\User;

test('guests are redirected to login when accessing admin dashboard', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can access admin dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});

test('admin dashboard route has correct name', function () {
    expect(route('admin.dashboard'))->toBe(url('/admin/dashboard'));
});

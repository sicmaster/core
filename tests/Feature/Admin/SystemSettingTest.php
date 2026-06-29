<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('admin can view system settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.system-settings.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/system-settings/edit')
        ->has('settings', fn ($settings) => $settings
            ->where('site_name', 'Laravel Starter Kit')
            ->where('default_locale', 'th')
            ->etc()
        )
    );
});

test('staff can view system settings but cannot update', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.system-settings.edit'))->assertOk();

    $this->actingAs($staff)->put(route('admin.system-settings.update'), [
        'site_name' => 'Hacked Name',
        'enabled_locales' => ['th'],
        'default_locale' => 'th',
    ])->assertForbidden();
});

test('admin can update system settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.system-settings.update'), [
        'site_name' => 'New Name',
        'site_description' => 'New Desc',
        'contact_email' => 'admin@example.com',
        'contact_phone' => '1234567890',
        'enabled_locales' => ['th', 'en'],
        'default_locale' => 'en',
    ]);

    $response->assertRedirect();
    
    // Check DB
    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'New Name']);
    $this->assertDatabaseHas('settings', ['key' => 'default_locale', 'value' => 'en']);
    $this->assertDatabaseHas('settings', ['key' => 'enabled_locales', 'value' => 'th,en']);

    // Check Cache logic via helper
    expect(setting('site_name'))->toBe('New Name');
});

test('validation prevents invalid default locale', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.system-settings.update'), [
        'site_name' => 'Valid Name',
        'enabled_locales' => ['th'],
        'default_locale' => 'en', // 'en' is not in enabled_locales
    ]);

    $response->assertInvalid('default_locale');
});

test('validation prevents invalid enabled locales', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.system-settings.update'), [
        'site_name' => 'Valid Name',
        'enabled_locales' => ['th', 'jp'], // jp is not supported in config
        'default_locale' => 'th',
    ]);

    $response->assertInvalid('enabled_locales.1');
});

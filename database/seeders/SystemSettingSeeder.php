<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Laravel Starter Kit'],
            ['key' => 'site_description', 'value' => 'A starter kit for your next project.'],
            ['key' => 'contact_email', 'value' => 'hello@example.com'],
            ['key' => 'contact_phone', 'value' => null],
            ['key' => 'enabled_locales', 'value' => 'th,en'],
            ['key' => 'default_locale', 'value' => 'th'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}

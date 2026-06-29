<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    /**
     * Get a setting value by its key.
     * Caches all settings forever until updated.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('system_settings', function () {
            return Setting::pluck('value', 'key')->toArray();
        });

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }
}

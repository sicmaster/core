<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function edit(): Response
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        // Convert enabled_locales back to array for frontend
        $settings['enabled_locales'] = isset($settings['enabled_locales']) 
            ? array_values(array_filter(explode(',', $settings['enabled_locales']))) 
            : ['th'];

        return Inertia::render('admin/system-settings/edit', [
            'settings' => collect($settings)->only([
                'site_name',
                'site_description',
                'contact_email',
                'contact_phone',
                'enabled_locales',
                'default_locale',
            ])->toArray(),
            'supportedLocales' => config('locales.supported'),
        ]);
    }

    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Convert enabled_locales array back to comma-separated string for DB
        if (isset($validated['enabled_locales'])) {
            $validated['enabled_locales'] = implode(',', $validated['enabled_locales']);
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear settings cache
        Cache::forget('system_settings');

        return back()->with('success', 'System settings updated successfully.');
    }
}

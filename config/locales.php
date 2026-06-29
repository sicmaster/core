<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The list of locales that the application supports. This acts as the
    | master list of all translations available in the codebase and database.
    | To add a new language to the system, it must be added here first.
    | Admin can then choose to enable a subset of these via System Settings.
    |
    */
    'supported' => [
        'th' => 'ไทย',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale used when a requested translation is not available.
    | This value is usually overridden by the 'default_locale' setting from
    | the database, but this config serves as the ultimate fallback.
    |
    */
    'default' => 'th',
];

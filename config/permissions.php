<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The list of resources available in the system for permission management.
    | Add new resources here when developing features (ADR-0003). Do not
    | create resources dynamically via UI.
    |
    */
    'resources' => [
        'users',
        'roles',
        'settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    |
    | The standard actions that can be performed on a resource.
    | These will be combined with resources to generate permissions in the
    | format "resource.action" (e.g., "users.create", "users.read").
    |
    */
    'actions' => [
        'create',
        'read',
        'update',
        'delete',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Admin Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the system admin user that is
    | automatically created when the application starts.
    |
    */

    'system_admin' => [
        'name' => env('SYSTEM_ADMIN_NAME', 'System Admin'),
        'email' => env('SYSTEM_ADMIN_EMAIL', 'admin@plivo-status.com'),
        'password' => env('SYSTEM_ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the admin panel functionality.
    |
    */

    'panel' => [
        'title' => env('ADMIN_PANEL_TITLE', 'Plivo Status Admin'),
        'description' => env('ADMIN_PANEL_DESCRIPTION', 'System Administration Panel'),
        'logo' => env('ADMIN_PANEL_LOGO', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for admin routes and middleware.
    |
    */

    'routes' => [
        'prefix' => env('ADMIN_ROUTES_PREFIX', 'admin'),
        'middleware' => ['auth', 'admin'],
    ],
]; 
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authenticatable model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that can be banned. Must use the Bannable trait.
    |
    */

    'user_model' => env('FILAMENT_BAN_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Column names
    |--------------------------------------------------------------------------
    */

    'columns' => [
        'banned_at' => 'banned_at',
        'ban_reason' => 'ban_reason',
        'banned_by' => 'banned_by',
        'suspended_until' => 'suspended_until',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response when access is blocked
    |--------------------------------------------------------------------------
    |
    | web_redirect: route name or path used for browser requests (null = /login).
    | api_status: HTTP status for JSON / API requests.
    |
    */

    'web_redirect' => null,

    'api_status' => 403,

    /*
    |--------------------------------------------------------------------------
    | Logout on block
    |--------------------------------------------------------------------------
    |
    | When true, banned/suspended users are logged out when middleware fires.
    |
    */

    'logout' => true,

];

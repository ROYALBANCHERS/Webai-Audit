<?php

return [
    // Production-specific settings
    'debug' => false,
    'force_https' => true,

    // Cache configuration for production
    'cache' => [
        'default' => env('CACHE_DRIVER', 'redis'),
        'prefix' => 'webai_prod',
    ],

    // Session configuration for production
    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => true,
        'files' => storage_path('framework/sessions'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'webai_session',
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', null),
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    // Production security headers
    'security' => [
        'csp' => env('CSP_ENABLED', true),
        'hsts' => env('HSTS_ENABLED', true),
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
    ],

    // Logging configuration for production
    'log' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'warning'),
    ],
];

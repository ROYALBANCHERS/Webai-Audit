<?php

return [
    'name' => env('APP_NAME', 'WebAI Auditor'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => require __DIR__.'/../bootstrap/providers.php',
    'aliases' => [],

    // Production settings
    'force_https' => env('FORCE_HTTPS', env('APP_ENV') === 'production'),
    'admin_secret' => env('ADMIN_SECRET'),

    // API Configuration
    'api' => [
        'rate_limit' => env('API_RATE_LIMIT', 60),
        'rate_limit_period' => env('API_RATE_LIMIT_PERIOD', 1), // minutes
        'timeout' => env('API_TIMEOUT', 30),
    ],

    // Feature flags
    'features' => [
        'registration' => env('FEATURE_REGISTRATION', true),
        'email_verification' => env('FEATURE_EMAIL_VERIFICATION', false),
        'analytics' => env('FEATURE_ANALYTICS', false),
        'monitoring' => env('FEATURE_MONITORING', false),
    ],
];

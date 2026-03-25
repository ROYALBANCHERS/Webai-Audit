<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This config file is for storing the credentials for third party services
    | such as Mailgun, SparkPost, etc. This file provides a sane default
    | location for these services, so you don't have to use environment
    | variables or additional .env files.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TinyFish AgentQL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for TinyFish AgentQL web scraping service
    |
    */

    'tinyfish' => [
        'api_key' => env('TINYFISH_API_KEY', 'bS6r2HMf-VwhcyBARDoIxIyctP35l6zyMHc_SAC476RWDIoBjud71w'),
        'endpoint' => env('TINYFISH_ENDPOINT', 'https://api.agentql.com/v1/query'),
        'timeout' => env('TINYFISH_TIMEOUT', 60),
        'retry_attempts' => env('TINYFISH_RETRY_ATTEMPTS', 3),
        'user_agent' => 'WebAI-Audit/1.0 (+https://royalbanchers.com)',
    ],

];

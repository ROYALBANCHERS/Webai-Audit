<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Selenium Configuration
    |--------------------------------------------------------------------------
    */
    'selenium' => [
        'host' => env('SELENIUM_HOST', 'http://localhost:4444'),
        'browser' => env('BROWSER_NAME', 'chrome'),
        'headless' => env('BROWSER_HEADLESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawling Configuration
    |--------------------------------------------------------------------------
    */
    'crawler' => [
        'max_depth' => env('MAX_CRAWL_DEPTH', 3),
        'timeout' => env('CRAWL_TIMEOUT', 30),
        'user_agent' => 'WebAI Auditor/1.0 (+https://webai.com/auditor)',
        'follow_redirects' => true,
        'max_pages' => env('MAX_PAGES', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'screenshots' => env('SCREENSHOT_PATH', storage_path('app/screenshots')),
        'exports' => env('EXPORT_PATH', storage_path('app/exports')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tech Stack Detection Patterns
    |--------------------------------------------------------------------------
    */
    'tech_patterns' => [
        'frameworks' => [
            'react' => ['react', 'react-dom', 'reactjs'],
            'vue' => ['vue', 'vuejs', 'nuxt.js'],
            'angular' => ['angular', 'ng-app'],
            'next' => ['next.js', '__next'],
            'nuxt' => ['nuxt.js', '__nuxt'],
            'laravel' => ['laravel', 'csrf-token'],
            'wordpress' => ['wp-content', 'wordpress'],
            'shopify' => ['shopify', 'shopify-theme'],
            'django' => ['csrfmiddlewaretoken'],
            'rails' => ['rails', 'ruby_on_rails'],
            'express' => ['express'],
            'spring' => ['spring-framework'],
        ],
        'libraries' => [
            'jquery' => ['jquery'],
            'bootstrap' => ['bootstrap'],
            'tailwind' => ['tailwind'],
            'three.js' => ['three', 'three.js'],
            'gsap' => ['gsap'],
            'd3' => ['d3.js', 'd3js'],
            'chart.js' => ['chart.js'],
            'moment' => ['moment.js'],
        ],
        'analytics' => [
            'google_analytics' => ['googletagmanager.com', 'google-analytics.com', 'ga(', 'gtag('],
            'mixpanel' => ['mixpanel'],
            'hotjar' => ['hotjar'],
            'segment' => ['segment.com', 'analytics.js'],
            'amplitude' => ['amplitude'],
        ],
        'cdn' => [
            'cloudflare' => ['cloudflare', 'cf-ray'],
            'aws_cloudfront' => ['cloudfront'],
            'akamai' => ['akamai'],
            'fastly' => ['fastly'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Analysis Weights
    |--------------------------------------------------------------------------
    */
    'seo_weights' => [
        'title' => 15,
        'meta_description' => 15,
        'headings' => 15,
        'images_alt' => 10,
        'canonical' => 10,
        'robots' => 5,
        'sitemap' => 5,
        'schema' => 10,
        'speed' => 10,
        'mobile' => 5,
        'ssl' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub API Configuration
    |--------------------------------------------------------------------------
    */
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'api_url' => 'https://api.github.com',
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Competitor Analysis Configuration
    |--------------------------------------------------------------------------
    */
    'competitors' => [
        'max_results' => 10,
        'similarity_threshold' => 0.5,
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Review Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default review driver that will be used
    | when calling driver methods without explicitly specifying one.
    |
    */

    'default' => env('LARA_REVIEWS_DRIVER', 'tripadvisor'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure review cache TTL (in seconds) for aggregated summary stats
    | and component responses to improve performance.
    |
    */

    'cache' => [
        'enabled' => env('LARA_REVIEWS_CACHE_ENABLED', true),
        'ttl' => env('LARA_REVIEWS_CACHE_TTL', 86400), // 24 hours
        'store' => env('LARA_REVIEWS_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configure sync batch sizes, auto-sync interval, and timeout limits.
    |
    */

    'sync' => [
        'queue' => env('LARA_REVIEWS_QUEUE', 'default'),
        'chunk_size' => 50,
        'timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Drivers & Credentials
    |--------------------------------------------------------------------------
    |
    | Define configuration and API keys for each review platform driver.
    |
    */

    'drivers' => [

        'tripadvisor' => [
            'class' => \LaraReviews\Drivers\TripAdvisorDriver::class,
            'api_key' => env('TRIPADVISOR_API_KEY'),
            'api_base_url' => env('TRIPADVISOR_API_BASE', 'https://api.content.tripadvisor.com/api/v1/location'),
            'lang' => env('TRIPADVISOR_LANG', 'en'),
        ],

        'viator' => [
            'class' => \LaraReviews\Drivers\ViatorDriver::class,
            'api_key' => env('VIATOR_API_KEY'),
            'api_base_url' => env('VIATOR_API_BASE', 'https://api.viator.com/partner/reviews'),
            'currency' => env('VIATOR_CURRENCY', 'USD'),
        ],

        'getyourguide' => [
            'class' => \LaraReviews\Drivers\GetYourGuideDriver::class,
            'api_key' => env('GETYOURGUIDE_API_KEY'),
            'api_base_url' => env('GETYOURGUIDE_API_BASE', 'https://api.getyourguide.com/v1/tours'),
        ],

        'google' => [
            'class' => \LaraReviews\Drivers\GoogleDriver::class,
            'api_key' => env('GOOGLE_PLACES_API_KEY'),
            'api_base_url' => env('GOOGLE_PLACES_API_BASE', 'https://maps.googleapis.com/maps/api/place'),
        ],

        'custom' => [
            'class' => \LaraReviews\Drivers\CustomDriver::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | UI & Component Customization
    |--------------------------------------------------------------------------
    |
    | Define visual options for Blade components.
    |
    */

    'ui' => [
        'per_page' => 10,
        'show_platform_badges' => true,
        'show_verified_badge' => true,
        'show_photos' => true,
        'theme' => 'light', // light, dark, system
        'platform_colors' => [
            'tripadvisor' => '#00AF87',
            'viator' => '#008577',
            'getyourguide' => '#FF5533',
            'google' => '#4285F4',
            'custom' => '#6C757D',
        ],
        'platform_names' => [
            'tripadvisor' => 'TripAdvisor',
            'viator' => 'Viator',
            'getyourguide' => 'GetYourGuide',
            'google' => 'Google Reviews',
            'custom' => 'Verified Review',
        ],
    ],

];

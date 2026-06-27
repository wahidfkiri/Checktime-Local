<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
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

    'zones' => [
    'base_url' => env('CHECKTIME_BASE_URL', 'http://54.37.15.111'),
    'sync_interval' => env('ZONES_SYNC_INTERVAL', 3600), // 1 heure
    'client_sync_interval' => env('ZONES_CLIENT_SYNC_INTERVAL', 3600), // 1 heure par client
    'batch_size' => env('ZONES_BATCH_SIZE', 100),
    'max_pages' => env('ZONES_MAX_PAGES', 50),
    'cleanup_days' => env('ZONES_CLEANUP_DAYS', 7),
],

];

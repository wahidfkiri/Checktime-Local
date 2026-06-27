<?php

return [
    'fastway' => [
        'username' => env('FASTWAY_SMS_USERNAME'),
        'password' => env('FASTWAY_SMS_PASSWORD'),
        'api_key' => env('FASTWAY_SMS_API_KEY'),
        'base_url' => env('FASTWAY_SMS_BASE_URL', 'https://fastway-sms.net/api/v1/'),
        'default_sender' => env('FASTWAY_SMS_DEFAULT_SENDER', 'CHECKTIME'),
        'country_code' => env('FASTWAY_SMS_COUNTRY_CODE', '229'),
        'enable_accent' => env('FASTWAY_SMS_ENABLE_ACCENT', true),
        'timeout' => env('FASTWAY_SMS_TIMEOUT', 30),
        'retry_attempts' => env('FASTWAY_SMS_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('FASTWAY_SMS_RETRY_DELAY', 1000),
        'sms_cost' => env('FASTWAY_SMS_COST', 10),
        'balance_cache_ttl' => env('FASTWAY_BALANCE_CACHE_TTL', 300),
    ],

    'test_mode' => env('SMS_TEST_MODE', false),
    'test_recipient' => env('SMS_TEST_RECIPIENT', null),
    'enable_balance_check' => env('SMS_ENABLE_BALANCE_CHECK', true),
    'low_balance_threshold' => env('SMS_LOW_BALANCE_THRESHOLD', 100),
];
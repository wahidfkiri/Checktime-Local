<?php

return [
    'base_url' => env('CHECKTIME_BASE_URL', 'http://54.37.15.111'),
    'token' => env('CHECKTIME_TOKEN', ''),
    
    'timeout' => env('CHECKTIME_TIMEOUT', 30),
    'retry_attempts' => env('CHECKTIME_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('CHECKTIME_RETRY_DELAY', 100),
    
    'cache' => [
        'token_ttl' => env('CHECKTIME_TOKEN_TTL', 3500), // 58 minutes en secondes
        'response_ttl' => env('CHECKTIME_RESPONSE_TTL', 300), // 5 minutes
    ],
];
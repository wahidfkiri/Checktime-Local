<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Connexion à l'appareil biométrique
    |--------------------------------------------------------------------------
    |
    | L'URL de base et le token d'accès NE SONT PLUS lus depuis .env : ils sont
    | stockés en base dans la table `settings` (group "company", clés `api_url`
    | et `api_token`) et doivent être lus via Setting::apiUrl() / Setting::apiToken().
    |
    | La valeur ci-dessous n'est qu'un repli utilisé tant qu'aucune URL n'a été
    | enregistrée par l'installeur ou l'écran des paramètres.
    |
    */
    'base_url' => 'http://54.37.15.111',

    'timeout' => env('CHECKTIME_TIMEOUT', 30),
    'retry_attempts' => env('CHECKTIME_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('CHECKTIME_RETRY_DELAY', 100),

    'cache' => [
        'token_ttl' => env('CHECKTIME_TOKEN_TTL', 3500), // 58 minutes en secondes
        'response_ttl' => env('CHECKTIME_RESPONSE_TTL', 300), // 5 minutes
    ],
];

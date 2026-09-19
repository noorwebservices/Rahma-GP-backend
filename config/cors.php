<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Le frontend (https://rahmadelivery.com) et l'API (https://app.rahmadelivery.com)
    | sont sur des sous-domaines différents : les origines doivent être explicitement
    | autorisées. L'authentification se fait par jeton Bearer (JWT), donc
    | supports_credentials reste à false.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://rahmadelivery.com',
        'https://www.rahmadelivery.com',
        'http://localhost:5173',
        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];

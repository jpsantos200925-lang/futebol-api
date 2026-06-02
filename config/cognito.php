<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS Cognito
    |--------------------------------------------------------------------------
    |
    | Configurações do AWS Cognito para autenticação da API.
    | Em ambiente local, o endpoint aponta para o LocalStack.
    | Em produção, remova COGNITO_ENDPOINT do .env para usar o endpoint real.
    |
    */

    'user_pool_id' => env('COGNITO_USER_POOL_ID'),

    'client_id' => env('COGNITO_CLIENT_ID'),

    'region' => env('COGNITO_REGION', 'us-east-1'),

    // Null em produção; aponta para LocalStack em desenvolvimento local
    'endpoint' => env('COGNITO_ENDPOINT'),

    // Credenciais usadas pelo SDK para assinar as chamadas à API do Cognito.
    // Em produção use IAM roles; em dev local qualquer valor serve para o LocalStack.
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID', 'test'),
        'secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache
    |--------------------------------------------------------------------------
    |
    | TTL em segundos para cache das chaves públicas JWKS do Cognito.
    | As chaves raramente mudam, então um valor alto é adequado.
    |
    */
    'jwks_cache_ttl' => env('COGNITO_JWKS_CACHE_TTL', 3600),

];

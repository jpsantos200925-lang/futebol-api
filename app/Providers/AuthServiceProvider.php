<?php

namespace App\Providers;

use App\Guards\CognitoGuard;
use App\Guards\CognitoUserProvider;
use App\Services\Cognito\CognitoClient;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        // Registra o provider que busca usuários pelo cognito_sub no banco local
        Auth::provider('cognito-eloquent', function ($app, array $config) {
            return new CognitoUserProvider($config['model']);
        });

        // Registra o guard que valida o JWT do Cognito em cada requisição
        Auth::extend('cognito', function ($app, string $name, array $config) {
            return new CognitoGuard(
                provider: Auth::createUserProvider($config['provider']),
                request: $app->make('request'),
                cognitoClient: $app->make(CognitoClient::class),
            );
        });
    }
}

<?php

namespace App\Services\Auth;

use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class LogoutService
{
    public function __construct(private CognitoClient $cognitoClient) {}

    public function execute(string $accessToken): array
    {
        try {
            $this->cognitoClient->globalSignOut($accessToken);
            return [[], 'Logout realizado com sucesso.', 200];
        } catch (CognitoIdentityProviderException) {
            return [[], 'Token inválido ou já expirado.', 401];
        }
    }
}

<?php

namespace App\Services\Auth;

use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class RefreshTokenService
{
    public function __construct(private CognitoClient $cognitoClient) {}

    public function execute(string $refreshToken): array
    {
        try {
            $tokens = $this->cognitoClient->refreshToken($refreshToken);

            return [
                [
                    'access_token' => $tokens['AccessToken'],
                    'id_token'     => $tokens['IdToken'] ?? null,
                    'expires_in'   => $tokens['ExpiresIn'] ?? 3600,
                    'token_type'   => $tokens['TokenType'] ?? 'Bearer',
                ],
                'Token renovado com sucesso.',
                200,
            ];
        } catch (CognitoIdentityProviderException) {
            return [[], 'Refresh token inválido ou expirado.', 401];
        }
    }
}

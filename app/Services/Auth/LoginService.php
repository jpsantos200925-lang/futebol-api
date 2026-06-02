<?php

namespace App\Services\Auth;

use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class LoginService
{
    public function __construct(private CognitoClient $cognitoClient) {}

    public function execute(array $credentials): array
    {
        try {
            $tokens = $this->cognitoClient->authenticateUser(
                email: $credentials['email'],
                password: $credentials['password'],
            );

            return [
                [
                    'access_token'  => $tokens['AccessToken'],
                    'id_token'      => $tokens['IdToken'],
                    'refresh_token' => $tokens['RefreshToken'] ?? null,
                    'expires_in'    => $tokens['ExpiresIn'] ?? 3600,
                    'token_type'    => $tokens['TokenType'] ?? 'Bearer',
                ],
                'Authorized.',
                200,
            ];
        } catch (CognitoIdentityProviderException $e) {
            return [[], $this->mapCognitoError($e), 401];
        }
    }

    private function mapCognitoError(CognitoIdentityProviderException $e): string
    {
        return match ($e->getAwsErrorCode()) {
            'NotAuthorizedException'  => 'E-mail ou senha inválidos.',
            'UserNotFoundException'   => 'E-mail ou senha inválidos.',
            'UserNotConfirmedException' => 'Usuário não confirmado.',
            default                   => 'Unauthorized.',
        };
    }
}

<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterService
{
    public function __construct(private CognitoClient $cognitoClient) {}

    public function execute(array $data): array
    {
        try {
            $cognitoSub = $this->cognitoClient->createUser(
                email: $data['email'],
                password: $data['password'],
                name: $data['name'],
            );
        } catch (CognitoIdentityProviderException $e) {
            return [[], $this->mapCognitoError($e), 422];
        }

        try {
            $user = DB::transaction(function () use ($data, $cognitoSub) {
                return User::create([
                    'name'        => $data['name'],
                    'email'       => $data['email'],
                    'cognito_sub' => $cognitoSub,
                ]);
            });

            return [
                ['user' => $user->only(['id', 'name', 'email'])],
                'Usuário criado com sucesso.',
                201,
            ];
        } catch (\Throwable $e) {
            Log::error('register.db_transaction_failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            // Compensa: remove o usuário do Cognito para evitar órfão
            try {
                $this->cognitoClient->deleteUser($data['email']);
            } catch (\Throwable) {
                // best-effort; não propaga
            }

            return [[], 'Erro ao criar usuário.', 500];
        }
    }

    private function mapCognitoError(CognitoIdentityProviderException $e): string
    {
        return match ($e->getAwsErrorCode()) {
            'UsernameExistsException'    => 'E-mail já cadastrado.',
            'InvalidPasswordException'   => 'A senha não atende aos requisitos mínimos.',
            'InvalidParameterException'  => 'Parâmetros inválidos.',
            default                      => 'Erro ao criar usuário.',
        };
    }
}

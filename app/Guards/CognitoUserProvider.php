<?php

namespace App\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;

class CognitoUserProvider implements UserProvider
{
    public function __construct(private string $model) {}

    /**
     * Busca o usuário pelo cognito_sub (identificador único no Cognito).
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->newModelQuery()->where('cognito_sub', $identifier)->first();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return $this->newModelQuery()->where('email', $credentials['email'] ?? '')->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // A validação de senha é feita pelo Cognito; aqui apenas confirmamos
        // que o usuário existe no banco local.
        return $user !== null;
    }

    /** @phpstan-ignore-next-line */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}

    private function newModelQuery(): \Illuminate\Database\Eloquent\Builder
    {
        /** @var Model $model */
        $model = new $this->model();
        return $model->newQuery();
    }
}

<?php

namespace App\Guards;

use App\Services\Cognito\CognitoClient;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CognitoGuard implements Guard
{
    private ?Authenticatable $user = null;
    private bool $resolved = false;

    public function __construct(
        private UserProvider $provider,
        private Request $request,
        private CognitoClient $cognitoClient,
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $token = $this->extractBearerToken();

        if (!$token) {
            return null;
        }

        try {
            $payload = $this->cognitoClient->verifyToken($token);
            $this->user = $this->provider->retrieveById($payload->sub);
        } catch (ExpiredException) {
            // Token expirado — cliente deve renovar, silêncio intencional
        } catch (SignatureInvalidException) {
            Log::warning('cognito.guard.invalid_signature', ['ip' => $this->request->ip()]);
        } catch (\Throwable $e) {
            Log::error('cognito.guard.verification_failed', [
                'error' => $e->getMessage(),
                'ip'    => $this->request->ip(),
            ]);
        }

        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user     = $user;
        $this->resolved = true;
    }

    private function extractBearerToken(): ?string
    {
        $header = $this->request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
}

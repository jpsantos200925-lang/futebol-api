<?php

namespace App\Services\Cognito;

use App\Infra\CognitoJwksHttp;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnexpectedValueException;

class CognitoClient
{
    private CognitoIdentityProviderClient $client;

    private string $userPoolId;

    private string $clientId;

    private string $region;

    private ?string $endpoint;

    public function __construct(private CognitoJwksHttp $jwksHttp = new CognitoJwksHttp())
    {
        $this->userPoolId = config('cognito.user_pool_id');
        $this->clientId   = config('cognito.client_id');
        $this->region     = config('cognito.region');
        $this->endpoint   = config('cognito.endpoint');

        $config = [
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => config('cognito.credentials'),
        ];

        if ($this->endpoint) {
            $config['endpoint'] = $this->endpoint;
        }

        $this->client = new CognitoIdentityProviderClient($config);
    }

    /**
     * Autentica o usuário com email e senha via USER_PASSWORD_AUTH.
     * Retorna AccessToken, IdToken e RefreshToken em caso de sucesso.
     *
     * @throws CognitoIdentityProviderException
     */
    public function authenticateUser(string $email, string $password): array
    {
        $result = $this->client->initiateAuth([
            'AuthFlow'       => 'USER_PASSWORD_AUTH',
            'ClientId'       => $this->clientId,
            'AuthParameters' => [
                'USERNAME' => $email,
                'PASSWORD' => $password,
            ],
        ]);

        return $result->get('AuthenticationResult');
    }

    /**
     * Renova o AccessToken a partir de um RefreshToken válido.
     *
     * @throws CognitoIdentityProviderException
     */
    public function refreshToken(string $refreshToken): array
    {
        $result = $this->client->initiateAuth([
            'AuthFlow'       => 'REFRESH_TOKEN_AUTH',
            'ClientId'       => $this->clientId,
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
            ],
        ]);

        return $result->get('AuthenticationResult');
    }

    /**
     * Invalida todos os tokens do usuário no Cognito (logout global).
     *
     * @throws CognitoIdentityProviderException
     */
    public function globalSignOut(string $accessToken): void
    {
        $this->client->globalSignOut([
            'AccessToken' => $accessToken,
        ]);
    }

    /**
     * Cria um usuário no User Pool e define sua senha permanente.
     * Usa adminCreateUser + adminSetUserPassword para evitar fluxo de
     * verificação por e-mail, facilitando o uso em desenvolvimento local.
     *
     * @throws CognitoIdentityProviderException
     */
    public function createUser(string $email, string $password, string $name): string
    {
        $result = $this->client->adminCreateUser([
            'UserPoolId'        => $this->userPoolId,
            'Username'          => $email,
            'TemporaryPassword' => Str::random(16) . '@Tmp1',
            'UserAttributes'    => [
                ['Name' => 'email',          'Value' => $email],
                ['Name' => 'email_verified', 'Value' => 'true'],
                ['Name' => 'name',           'Value' => $name],
            ],
            'MessageAction' => 'SUPPRESS',
        ]);

        $sub = collect($result->get('User')['Attributes'])
            ->firstWhere('Name', 'sub')['Value'];

        $this->client->adminSetUserPassword([
            'UserPoolId' => $this->userPoolId,
            'Username'   => $email,
            'Password'   => $password,
            'Permanent'  => true,
        ]);

        return $sub;
    }

    /**
     * Cria um novo User Pool e retorna seu ID.
     * Usado apenas pelo comando artisan cognito:setup.
     *
     * @throws CognitoIdentityProviderException
     */
    public function createUserPool(string $poolName): string
    {
        $result = $this->client->createUserPool([
            'PoolName'               => $poolName,
            'UsernameAttributes'     => ['email'],
            'AutoVerifiedAttributes' => ['email'],
            'Policies'               => [
                'PasswordPolicy' => [
                    'MinimumLength'    => 8,
                    'RequireUppercase' => false,
                    'RequireLowercase' => false,
                    'RequireNumbers'   => false,
                    'RequireSymbols'   => false,
                ],
            ],
        ]);

        return $result->get('UserPool')['Id'];
    }

    /**
     * Remove um usuário do User Pool pelo e-mail (username).
     * Usado para compensar falhas de persistência local após criação no Cognito.
     *
     * @throws CognitoIdentityProviderException
     */
    public function deleteUser(string $email): void
    {
        $this->client->adminDeleteUser([
            'UserPoolId' => $this->userPoolId,
            'Username'   => $email,
        ]);
    }

    /**
     * Cria um App Client no User Pool e retorna o ClientId.
     * Usado apenas pelo comando artisan cognito:setup.
     *
     * @throws CognitoIdentityProviderException
     */
    public function createUserPoolClient(string $poolId, string $clientName): string
    {
        $result = $this->client->createUserPoolClient([
            'UserPoolId'        => $poolId,
            'ClientName'        => $clientName,
            'GenerateSecret'    => false,
            'ExplicitAuthFlows' => [
                'ALLOW_USER_PASSWORD_AUTH',
                'ALLOW_REFRESH_TOKEN_AUTH',
                'ALLOW_USER_SRP_AUTH',
            ],
        ]);

        return $result->get('UserPoolClient')['ClientId'];
    }

    /**
     * Valida o AccessToken JWT emitido pelo Cognito.
     * Verifica assinatura, expiração e claim token_use = 'access'.
     * Retorna o payload decodificado ou lança exceção em caso de token inválido.
     *
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     * @throws UnexpectedValueException
     */
    public function verifyToken(string $accessToken): object
    {
        $keys = $this->getJwks();

        $decoded = JWT::decode($accessToken, JWK::parseKeySet($keys, 'RS256'));

        if (($decoded->token_use ?? '') !== 'access') {
            throw new UnexpectedValueException('O token não é um AccessToken.');
        }

        $expectedIss = $this->endpoint
            ? rtrim($this->endpoint, '/') . '/' . $this->userPoolId
            : "https://cognito-idp.{$this->region}.amazonaws.com/{$this->userPoolId}";

        if (($decoded->iss ?? '') !== $expectedIss) {
            throw new UnexpectedValueException('Token issuer inválido.');
        }

        if (($decoded->client_id ?? '') !== $this->clientId) {
            throw new UnexpectedValueException('Token client_id inválido.');
        }

        return $decoded;
    }

    /**
     * Busca e armazena em cache as chaves públicas JWKS do Cognito.
     */
    private function getJwks(): array
    {
        $cacheKey = 'cognito_jwks_' . md5($this->userPoolId);
        $ttl      = config('cognito.jwks_cache_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () {
            $url = $this->endpoint
                ? rtrim($this->endpoint, '/') . '/' . $this->userPoolId . '/.well-known/jwks.json'
                : "https://cognito-idp.{$this->region}.amazonaws.com/{$this->userPoolId}/.well-known/jwks.json";

            return $this->jwksHttp->fetch($url);
        });
    }
}

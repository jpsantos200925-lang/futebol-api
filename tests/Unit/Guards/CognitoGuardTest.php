<?php

namespace Tests\Unit\Guards;

use App\Guards\CognitoGuard;
use App\Models\User;
use App\Services\Cognito\CognitoClient;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CognitoGuardTest extends TestCase
{
    private MockInterface $provider;
    private MockInterface $cognitoClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider      = Mockery::mock(UserProvider::class);
        $this->cognitoClient = Mockery::mock(CognitoClient::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeGuard(Request $request): CognitoGuard
    {
        return new CognitoGuard($this->provider, $request, $this->cognitoClient);
    }

    private function requestWithBearer(string $token): Request
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$token}");
        return $request;
    }

    public function test_autentica_usuario_com_bearer_token_valido(): void
    {
        $payload       = (object) ['sub' => 'cognito-sub-123'];
        $user          = new User(['name' => 'Test', 'cognito_sub' => 'cognito-sub-123']);
        $user->id      = 1;

        $this->cognitoClient->shouldReceive('verifyToken')
            ->once()
            ->with('valid.jwt.token')
            ->andReturn($payload);

        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with('cognito-sub-123')
            ->andReturn($user);

        $guard = $this->makeGuard($this->requestWithBearer('valid.jwt.token'));

        $this->assertNotNull($guard->user());
        $this->assertTrue($guard->check());
    }

    public function test_retorna_null_sem_header_authorization(): void
    {
        $request = Request::create('/api/test', 'GET');
        $guard   = $this->makeGuard($request);

        $this->assertNull($guard->user());
        $this->assertFalse($guard->check());
    }

    public function test_retorna_null_com_token_invalido(): void
    {
        $this->cognitoClient->shouldReceive('verifyToken')
            ->once()
            ->andThrow(new \RuntimeException('Invalid token'));

        $guard = $this->makeGuard($this->requestWithBearer('bad.token'));

        $this->assertNull($guard->user());
    }

    public function test_retorna_null_quando_usuario_nao_existe_no_banco(): void
    {
        $payload = (object) ['sub' => 'sub-inexistente'];

        $this->cognitoClient->shouldReceive('verifyToken')
            ->once()
            ->andReturn($payload);

        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with('sub-inexistente')
            ->andReturn(null);

        $guard = $this->makeGuard($this->requestWithBearer('token.sem.usuario'));

        $this->assertNull($guard->user());
    }

    public function test_check_retorna_true_para_usuario_autenticado(): void
    {
        $payload  = (object) ['sub' => 'sub-ok'];
        $user     = new User(['name' => 'Test']);
        $user->id = 1;

        $this->cognitoClient->shouldReceive('verifyToken')->once()->andReturn($payload);
        $this->provider->shouldReceive('retrieveById')->once()->andReturn($user);

        $guard = $this->makeGuard($this->requestWithBearer('ok.token'));

        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
    }
}

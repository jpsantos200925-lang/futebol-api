<?php

namespace Tests\Feature\Http;

use App\Models\User;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockCognito(): \Mockery\MockInterface
    {
        return $this->mock(CognitoClient::class);
    }

    private function makeCognitoException(string $code): \Mockery\MockInterface
    {
        $e = Mockery::mock(CognitoIdentityProviderException::class);
        $e->shouldReceive('getAwsErrorCode')->andReturn($code);
        return $e;
    }

    // -------------------------------------------------------
    // POST /api/v1/register
    // -------------------------------------------------------

    public function test_register_cria_usuario_com_dados_validos(): void
    {
        $this->mockCognito()
            ->shouldReceive('createUser')
            ->once()
            ->andReturn('fake-cognito-sub');

        $response = $this->postJson('/api/v1/register', [
            'name'     => 'João Silva',
            'email'    => 'joao@test.com',
            'password' => 'Senha@123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'joao@test.com')
            ->assertJsonPath('data.user.name', 'João Silva');

        $this->assertDatabaseHas('users', ['email' => 'joao@test.com']);
    }

    public function test_register_retorna_422_para_email_ja_cadastrado(): void
    {
        User::factory()->create(['email' => 'existente@test.com']);

        $response = $this->postJson('/api/v1/register', [
            'name'     => 'Duplicado',
            'email'    => 'existente@test.com',
            'password' => 'Senha@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_retorna_422_sem_nome(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'email'    => 'teste@test.com',
            'password' => 'Senha@123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------
    // POST /api/v1/login
    // -------------------------------------------------------

    public function test_login_retorna_tokens_para_credenciais_validas(): void
    {
        $this->mockCognito()
            ->shouldReceive('authenticateUser')
            ->once()
            ->andReturn([
                'AccessToken'  => 'access-token',
                'IdToken'      => 'id-token',
                'RefreshToken' => 'refresh-token',
                'ExpiresIn'    => 3600,
                'TokenType'    => 'Bearer',
            ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'user@test.com',
            'password' => 'Senha@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.access_token', 'access-token')
            ->assertJsonPath('data.refresh_token', 'refresh-token');
    }

    public function test_login_retorna_401_para_credenciais_invalidas(): void
    {
        $this->mockCognito()
            ->shouldReceive('authenticateUser')
            ->once()
            ->andThrow($this->makeCognitoException('NotAuthorizedException'));

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'user@test.com',
            'password' => 'errada',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_retorna_422_sem_email(): void
    {
        $response = $this->postJson('/api/v1/login', ['password' => 'Senha@123']);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_retorna_422_sem_password(): void
    {
        $response = $this->postJson('/api/v1/login', ['email' => 'user@test.com']);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------
    // POST /api/v1/logout
    // -------------------------------------------------------

    public function test_logout_retorna_200_com_token_valido(): void
    {
        $this->mockCognito()
            ->shouldReceive('globalSignOut')
            ->once()
            ->with('valid-token');

        $response = $this->postJson('/api/v1/logout', ['access_token' => 'valid-token']);

        $response->assertStatus(200);
    }

    public function test_logout_retorna_422_sem_access_token(): void
    {
        $response = $this->postJson('/api/v1/logout', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['access_token']);
    }

    // -------------------------------------------------------
    // POST /api/v1/refresh
    // -------------------------------------------------------

    public function test_refresh_retorna_novos_tokens(): void
    {
        $this->mockCognito()
            ->shouldReceive('refreshToken')
            ->once()
            ->with('valid-refresh')
            ->andReturn([
                'AccessToken' => 'new-access',
                'IdToken'     => 'new-id',
                'ExpiresIn'   => 3600,
                'TokenType'   => 'Bearer',
            ]);

        $response = $this->postJson('/api/v1/refresh', ['refresh_token' => 'valid-refresh']);

        $response->assertStatus(200)
            ->assertJsonPath('data.access_token', 'new-access');
    }

    public function test_refresh_retorna_422_sem_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/refresh', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['refresh_token']);
    }
}

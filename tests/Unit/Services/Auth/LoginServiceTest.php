<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\LoginService;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginServiceTest extends TestCase
{
    private MockInterface $cognitoClient;
    private LoginService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cognitoClient = Mockery::mock(CognitoClient::class);
        $this->service       = new LoginService($this->cognitoClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeCognitoException(string $code): MockInterface
    {
        $e = Mockery::mock(CognitoIdentityProviderException::class);
        $e->shouldReceive('getAwsErrorCode')->andReturn($code);
        return $e;
    }

    public function test_retorna_tokens_para_credenciais_validas(): void
    {
        $this->cognitoClient->shouldReceive('authenticateUser')
            ->once()
            ->with('user@test.com', 'senha123')
            ->andReturn([
                'AccessToken'  => 'access-token',
                'IdToken'      => 'id-token',
                'RefreshToken' => 'refresh-token',
                'ExpiresIn'    => 3600,
                'TokenType'    => 'Bearer',
            ]);

        [$data, $message, $status] = $this->service->execute([
            'email'    => 'user@test.com',
            'password' => 'senha123',
        ]);

        $this->assertEquals(200, $status);
        $this->assertEquals('access-token', $data['access_token']);
        $this->assertEquals('id-token', $data['id_token']);
        $this->assertEquals('refresh-token', $data['refresh_token']);
    }

    public function test_retorna_erro_401_para_credenciais_invalidas(): void
    {
        $this->cognitoClient->shouldReceive('authenticateUser')
            ->once()
            ->andThrow($this->makeCognitoException('NotAuthorizedException'));

        [$data, $message, $status] = $this->service->execute([
            'email'    => 'user@test.com',
            'password' => 'errada',
        ]);

        $this->assertEquals(401, $status);
        $this->assertEmpty($data);
        $this->assertStringContainsString('inválidos', $message);
    }

    public function test_retorna_erro_para_usuario_inexistente(): void
    {
        $this->cognitoClient->shouldReceive('authenticateUser')
            ->once()
            ->andThrow($this->makeCognitoException('UserNotFoundException'));

        [$data, $message, $status] = $this->service->execute([
            'email'    => 'naoexiste@test.com',
            'password' => 'qualquer',
        ]);

        $this->assertEquals(401, $status);
        $this->assertEmpty($data);
    }

    public function test_retorna_erro_para_usuario_nao_confirmado(): void
    {
        $this->cognitoClient->shouldReceive('authenticateUser')
            ->once()
            ->andThrow($this->makeCognitoException('UserNotConfirmedException'));

        [$data, $message, $status] = $this->service->execute([
            'email'    => 'naoconfirmado@test.com',
            'password' => 'senha123',
        ]);

        $this->assertEquals(401, $status);
        $this->assertStringContainsString('confirmado', $message);
    }
}

<?php

namespace Tests\Unit\Services\Auth;

use App\Models\User;
use App\Services\Auth\RegisterService;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $cognitoClient;
    private RegisterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cognitoClient = Mockery::mock(CognitoClient::class);
        $this->service       = new RegisterService($this->cognitoClient);
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

    public function test_cria_usuario_no_cognito_e_no_banco(): void
    {
        $this->cognitoClient->shouldReceive('createUser')
            ->once()
            ->with('novo@test.com', 'Senha@123', 'João')
            ->andReturn('cognito-sub-uuid');

        [$data, $message, $status] = $this->service->execute([
            'name'     => 'João',
            'email'    => 'novo@test.com',
            'password' => 'Senha@123',
        ]);

        $this->assertEquals(201, $status);
        $this->assertEquals('João', $data['user']['name']);
        $this->assertEquals('novo@test.com', $data['user']['email']);
        $this->assertDatabaseHas('users', ['email' => 'novo@test.com', 'cognito_sub' => 'cognito-sub-uuid']);
    }

    public function test_faz_rollback_se_cognito_falhar(): void
    {
        $this->cognitoClient->shouldReceive('createUser')
            ->once()
            ->andThrow($this->makeCognitoException('InvalidPasswordException'));

        $this->service->execute([
            'name'     => 'Falha',
            'email'    => 'falha@test.com',
            'password' => 'fraca',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'falha@test.com']);
    }

    public function test_retorna_erro_para_email_duplicado_no_cognito(): void
    {
        $this->cognitoClient->shouldReceive('createUser')
            ->once()
            ->andThrow($this->makeCognitoException('UsernameExistsException'));

        [$data, $message, $status] = $this->service->execute([
            'name'     => 'João',
            'email'    => 'duplicado@test.com',
            'password' => 'Senha@123',
        ]);

        $this->assertEquals(422, $status);
        $this->assertStringContainsString('cadastrado', $message);
        $this->assertEmpty($data);
    }

    public function test_retorna_erro_para_senha_invalida_no_cognito(): void
    {
        $this->cognitoClient->shouldReceive('createUser')
            ->once()
            ->andThrow($this->makeCognitoException('InvalidPasswordException'));

        [$data, $message, $status] = $this->service->execute([
            'name'     => 'João',
            'email'    => 'joao@test.com',
            'password' => '123',
        ]);

        $this->assertEquals(422, $status);
        $this->assertStringContainsString('requisitos', $message);
    }
}

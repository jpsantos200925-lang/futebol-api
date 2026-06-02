<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\LogoutService;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LogoutServiceTest extends TestCase
{
    private MockInterface $cognitoClient;
    private LogoutService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cognitoClient = Mockery::mock(CognitoClient::class);
        $this->service       = new LogoutService($this->cognitoClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_faz_logout_com_access_token_valido(): void
    {
        $this->cognitoClient->shouldReceive('globalSignOut')
            ->once()
            ->with('valid-access-token');

        [$data, $message, $status] = $this->service->execute('valid-access-token');

        $this->assertEquals(200, $status);
        $this->assertEmpty($data);
        $this->assertStringContainsString('sucesso', $message);
    }

    public function test_retorna_erro_401_com_token_invalido(): void
    {
        $e = Mockery::mock(CognitoIdentityProviderException::class);

        $this->cognitoClient->shouldReceive('globalSignOut')
            ->once()
            ->andThrow($e);

        [$data, $message, $status] = $this->service->execute('invalid-token');

        $this->assertEquals(401, $status);
        $this->assertEmpty($data);
    }
}

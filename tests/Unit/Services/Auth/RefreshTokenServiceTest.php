<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\RefreshTokenService;
use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RefreshTokenServiceTest extends TestCase
{
    private MockInterface $cognitoClient;
    private RefreshTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cognitoClient = Mockery::mock(CognitoClient::class);
        $this->service       = new RefreshTokenService($this->cognitoClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_retorna_novos_tokens_com_refresh_token_valido(): void
    {
        $this->cognitoClient->shouldReceive('refreshToken')
            ->once()
            ->with('valid-refresh-token')
            ->andReturn([
                'AccessToken' => 'new-access-token',
                'IdToken'     => 'new-id-token',
                'ExpiresIn'   => 3600,
                'TokenType'   => 'Bearer',
            ]);

        [$data, $message, $status] = $this->service->execute('valid-refresh-token');

        $this->assertEquals(200, $status);
        $this->assertEquals('new-access-token', $data['access_token']);
        $this->assertStringContainsString('renovado', $message);
    }

    public function test_retorna_erro_401_com_refresh_token_invalido(): void
    {
        $e = Mockery::mock(CognitoIdentityProviderException::class);

        $this->cognitoClient->shouldReceive('refreshToken')
            ->once()
            ->andThrow($e);

        [$data, $message, $status] = $this->service->execute('invalid-refresh-token');

        $this->assertEquals(401, $status);
        $this->assertEmpty($data);
    }
}

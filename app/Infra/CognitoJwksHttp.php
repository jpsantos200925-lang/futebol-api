<?php

namespace App\Infra;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CognitoJwksHttp
{
    public function fetch(string $url): array
    {
        $response = Http::timeout(5)->get($url);
        if (!$response->successful()) {
            throw new RuntimeException(
                "Não foi possível buscar as chaves JWKS do Cognito. Status: {$response->status()}"
            );
        }

        $data = $response->json();
        if (!isset($data['keys']) || !is_array($data['keys'])) {
            throw new RuntimeException('Resposta JWKS inválida do Cognito.');
        }

        return $data;
    }
}

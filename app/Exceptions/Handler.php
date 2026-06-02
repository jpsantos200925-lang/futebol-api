<?php

namespace App\Exceptions;

use App\Exceptions\Infrastructure\CognitoUnavailableException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Log::error('unhandled_exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        });

        $this->renderable(function (CognitoUnavailableException $e) {
            Log::critical('cognito.unavailable', ['error' => $e->getMessage()]);
            return response()->json(['data' => [], 'message' => 'Serviço de autenticação indisponível.'], 503);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() &&
                !($e instanceof AuthenticationException) &&
                !($e instanceof ValidationException)) {
                return response()->json(['data' => [], 'message' => 'Erro interno. Tente novamente.'], 500);
            }
        });
    }

    // Garante resposta JSON 401 em vez de redirecionar para route('login'),
    // que não existe em uma API pura.
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}

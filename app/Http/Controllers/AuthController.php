<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use App\Services\Auth\RefreshTokenService;
use App\Services\Auth\RegisterService;

class AuthController extends Controller
{
    public function __construct(
        private LoginService $loginService,
        private LogoutService $logoutService,
        private RefreshTokenService $refreshTokenService,
        private RegisterService $registerService,
    ) {}

    public function login(LoginRequest $request)
    {
        [$data, $message, $status] = $this->loginService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function logout(LogoutRequest $request)
    {
        [$data, $message, $status] = $this->logoutService->execute($request->validated()['access_token']);
        return $this->return_default($data, $message, $status);
    }

    public function refresh(RefreshTokenRequest $request)
    {
        [$data, $message, $status] = $this->refreshTokenService->execute($request->validated()['refresh_token']);
        return $this->return_default($data, $message, $status);
    }

    public function register(RegisterRequest $request)
    {
        [$data, $message, $status] = $this->registerService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }
}

<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:cognito')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware('throttle:10,1')->group(function () {
    // Rotas públicas de autenticação (máx. 10 req/minuto por IP)
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);
});

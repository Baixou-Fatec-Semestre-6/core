<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::prefix('auth')->group(function () {
    // Public
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('products')->group(function () {
        Route::get('/catalog', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'track']);
        Route::get('/{id}/price-history', [ProductController::class, 'priceHistory']);
    });

});

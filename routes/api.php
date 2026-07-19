<?php

use App\Http\Controllers\Api\V1\ActivationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Authentication & Activation routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/customers/activate', [ActivationController::class, 'activate']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // User logout
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Customer registration
        Route::post('/customers', [CustomerController::class, 'register'])
            ->middleware('role:cashier,admin');

        // Order capture
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware('role:cashier,admin');

        // Customer profile details
        Route::get('/customers/me', [CustomerController::class, 'me'])
            ->middleware('role:customer');
    });
});

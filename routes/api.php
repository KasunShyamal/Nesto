<?php

use App\Http\Controllers\Api\V1\ActivationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\LoyaltyDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Authentication & Activation routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/customers/activate', [ActivationController::class, 'activate']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // User logout
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Customer registration & listing
        Route::post('/customers', [CustomerController::class, 'register'])
            ->middleware('role:cashier,admin');
        Route::get('/customers', [CustomerController::class, 'index'])
            ->middleware('role:cashier,admin');

        // Order capture & listing
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware('role:cashier,admin');
        Route::get('/orders', [OrderController::class, 'index'])
            ->middleware('role:cashier,admin');

        // Customer profile details
        Route::get('/customers/me', [CustomerController::class, 'me'])
            ->middleware('role:customer');

        // Loyalty details & tracking
        Route::get('/loyalty/balance', [LoyaltyDashboardController::class, 'balance'])
            ->middleware('role:customer');

        Route::get('/loyalty/transactions', [LoyaltyDashboardController::class, 'transactions'])
            ->middleware('role:customer');

        Route::get('/loyalty/dashboard', [LoyaltyDashboardController::class, 'dashboard'])
            ->middleware('role:customer');
    });
});

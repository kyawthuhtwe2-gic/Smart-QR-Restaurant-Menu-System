<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MenuItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth (public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    });

    // Admin (protected)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::post('categories/reorder', [CategoryController::class, 'reorder']);
        Route::apiResource('categories', CategoryController::class);

        Route::patch('menu-items/{menuItem}/availability', [MenuItemController::class, 'updateAvailability']);
        Route::apiResource('menu-items', MenuItemController::class)->parameters(['menu-items' => 'menuItem']);
    });
});

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MenuItemController;
use App\Http\Controllers\Api\V1\PublicMenuController;
use App\Http\Controllers\Api\V1\RestaurantTableController;
use App\Http\Controllers\Api\V1\TableQrController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth (public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    });

    // Public customer menu (no login, rate limited)
    Route::prefix('public/menu/{token}')->middleware('throttle:public-menu')->group(function () {
        Route::get('/', [PublicMenuController::class, 'show']);
        Route::get('categories', [PublicMenuController::class, 'categories']);
        Route::get('items', [PublicMenuController::class, 'items']);
    });

    // Admin (protected)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::post('categories/reorder', [CategoryController::class, 'reorder']);
        Route::apiResource('categories', CategoryController::class);

        Route::patch('tables/{table}/status', [RestaurantTableController::class, 'updateStatus']);
        Route::get('tables/{table}/qr', [TableQrController::class, 'show']);
        Route::get('tables/{table}/qr/print', [TableQrController::class, 'printData']);
        Route::get('tables/{table}/qr/download', [TableQrController::class, 'download']);
        Route::post('tables/{table}/qr/regenerate', [TableQrController::class, 'regenerate'])->middleware('throttle:30,1');
        Route::apiResource('tables', RestaurantTableController::class);

        Route::patch('menu-items/{menuItem}/availability', [MenuItemController::class, 'updateAvailability']);
        Route::apiResource('menu-items', MenuItemController::class)->parameters(['menu-items' => 'menuItem']);
    });
});

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Middleware\EnsureApiPasswordIsChanged;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Legacy endpoints (kept unchanged for existing clients)
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Versioned API for the native iOS client
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [V1AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', EnsureApiPasswordIsChanged::class])->group(function () {
        Route::post('/auth/logout', [V1AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [V1AuthController::class, 'me'])->name('auth.me');
        Route::patch('/auth/profile', [V1AuthController::class, 'updateProfile'])->name('auth.profile');
        Route::post('/auth/change-password', [V1AuthController::class, 'changePassword'])->name('auth.change-password');
    });
});

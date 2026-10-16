<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\JournalController as V1JournalController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Middleware\CheckScheduleLock;
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

        Route::get('/config', [ConfigController::class, 'index'])->name('config');
        Route::get('/classes', [LookupController::class, 'classes'])->name('classes.index');
        Route::get('/rooms', [LookupController::class, 'rooms'])->name('rooms.index');
        Route::get('/teachers', [LookupController::class, 'teachers'])->middleware('role:1')->name('teachers.index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('role:1,2')->group(function () {
            Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::post('/schedules', [ScheduleController::class, 'store'])->middleware(CheckScheduleLock::class)->name('schedules.store');
            Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->middleware(CheckScheduleLock::class)->name('schedules.update');
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->middleware(CheckScheduleLock::class)->name('schedules.destroy');
        });

        Route::middleware('role:2')->group(function () {
            Route::get('/journals/prepare', [V1JournalController::class, 'prepare'])->name('journals.prepare');
            Route::post('/journals', [V1JournalController::class, 'store'])->name('journals.store');
            Route::put('/journals/{journal}/attendances', [V1JournalController::class, 'updateAttendances'])->name('journals.attendances.update');
        });

        Route::middleware('role:1,2,3')->group(function () {
            Route::get('/journals', [V1JournalController::class, 'index'])->name('journals.index');
            Route::get('/journals/{journal}', [V1JournalController::class, 'show'])->name('journals.show');
            Route::get('/journals/{journal}/photo', [V1JournalController::class, 'photo'])->name('journals.photo');
        });
    });
});

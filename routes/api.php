<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AttendanceController as V1AttendanceController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\ClassController as V1ClassController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExamController as V1ExamController;
use App\Http\Controllers\Api\V1\JournalController as V1JournalController;
use App\Http\Controllers\Api\V1\LeaveRequestController as V1LeaveRequestController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\UserController as V1UserController;
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
        Route::get('/classes', [V1ClassController::class, 'index'])->name('classes.index');
        Route::get('/rooms', [LookupController::class, 'rooms'])->name('rooms.index');
        Route::get('/teachers', [LookupController::class, 'teachers'])->middleware('role:1')->name('teachers.index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/exams', [V1ExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [V1ExamController::class, 'show'])->name('exams.show');
        Route::get('/exams/{exam}/download', [V1ExamController::class, 'download'])->name('exams.download');

        Route::middleware('role:1,2')->group(function () {
            Route::post('/exams', [V1ExamController::class, 'store'])->name('exams.store');
            Route::put('/exams/{exam}', [V1ExamController::class, 'update'])->name('exams.update');
            Route::delete('/exams/{exam}', [V1ExamController::class, 'destroy'])->name('exams.destroy');

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

        Route::middleware('role:1,3,4')->group(function () {
            Route::get('/leave-requests', [V1LeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::get('/leave-requests/{leaveRequest}', [V1LeaveRequestController::class, 'show'])->name('leave-requests.show');
            Route::get('/leave-requests/{leaveRequest}/document', [V1LeaveRequestController::class, 'document'])->name('leave-requests.document');
        });
        Route::post('/leave-requests', [V1LeaveRequestController::class, 'store'])->middleware('role:4')->name('leave-requests.store');
        Route::patch('/leave-requests/{leaveRequest}/status', [V1LeaveRequestController::class, 'updateStatus'])->middleware('role:1,3')->name('leave-requests.update-status');

        Route::get('/attendance', [V1AttendanceController::class, 'index'])->middleware('role:4')->name('attendance.index');

        Route::middleware('role:1,3')->group(function () {
            Route::post('/classes', [V1ClassController::class, 'store'])->name('classes.store');
            Route::get('/classes/{class}', [V1ClassController::class, 'show'])->name('classes.show');
            Route::put('/classes/{class}', [V1ClassController::class, 'update'])->name('classes.update');
            Route::delete('/classes/{class}', [V1ClassController::class, 'destroy'])->name('classes.destroy');
            Route::get('/classes/{class}/attendance', [V1ClassController::class, 'attendance'])->name('classes.attendance');
            Route::get('/classes/{class}/export', [V1ClassController::class, 'export'])->name('classes.export');

            Route::get('/users', [V1UserController::class, 'index'])->name('users.index');
            Route::post('/users', [V1UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}', [V1UserController::class, 'show'])->name('users.show');
            Route::put('/users/{user}', [V1UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [V1UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('/users/{user}/reset-password', [V1UserController::class, 'resetPassword'])->name('users.reset-password');
        });

        Route::middleware('role:1')->group(function () {
            Route::patch('/system/cycle', [SystemController::class, 'cycle'])->name('system.cycle');
            Route::patch('/system/lock', [SystemController::class, 'lock'])->name('system.lock');
            Route::get('/system/unfilled-journals', [SystemController::class, 'unfilledJournals'])->name('system.unfilled-journals');
        });
    });
});

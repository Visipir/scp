<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AcademicCalendarController as V1AcademicCalendarController;
use App\Http\Controllers\Api\V1\AttendanceController as V1AttendanceController;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\ChatController as V1ChatController;
use App\Http\Controllers\Api\V1\ClassController as V1ClassController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExamController as V1ExamController;
use App\Http\Controllers\Api\V1\IneffectiveDayController as V1IneffectiveDayController;
use App\Http\Controllers\Api\V1\JournalController as V1JournalController;
use App\Http\Controllers\Api\V1\LeaveRequestController as V1LeaveRequestController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\RoomScheduleController as V1RoomScheduleController;
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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
Route::post('/forgot-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
Route::post('/reset-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', EnsureApiPasswordIsChanged::class])->group(function () {
    Route::get('/schedule-swaps', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'index']);
    Route::get('/schedule-swaps/candidates', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'candidates']);
    Route::post('/schedule-swaps', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'store']);
    Route::post('/schedule-swaps/{scheduleSwap}/approve', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'approve']);
    Route::post('/schedule-swaps/{scheduleSwap}/reject', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'reject']);
    Route::post('/schedule-swaps/{scheduleSwap}/cancel', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'cancel']);

    Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);

    Route::middleware('role:2')->get('/journals/available-schedules', [V1JournalController::class, 'availableSchedules']);
});

// Versioned API for the native iOS client
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [V1AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'forgotPassword'])
        ->middleware('throttle:forgot-password')
        ->name('auth.forgot-password');
    Route::post('/auth/reset-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('auth.reset-password');

    Route::middleware(['auth:sanctum', EnsureApiPasswordIsChanged::class])->group(function () {
        Route::post('/auth/logout', [V1AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [V1AuthController::class, 'me'])->name('auth.me');
        Route::patch('/auth/profile', [V1AuthController::class, 'updateProfile'])->name('auth.profile');
        Route::post('/auth/email', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'requestChange'])->name('auth.email.request');
        Route::post('/auth/email/verify', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'verify'])->middleware('throttle:6,1')->name('auth.email.verify');
        Route::delete('/auth/email', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'remove'])->name('auth.email.remove');
        Route::post('/profile/email', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'requestChange']);
        Route::post('/profile/email/verify', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'verify'])->middleware('throttle:6,1');
        Route::delete('/profile/email', [\App\Http\Controllers\Api\V1\ProfileEmailController::class, 'remove']);
        Route::post('/auth/change-password', [V1AuthController::class, 'changePassword'])->name('auth.change-password');

        Route::get('/config', [ConfigController::class, 'index'])->name('config');
        Route::get('/classes', [V1ClassController::class, 'index'])->name('classes.index');
        Route::get('/rooms', [LookupController::class, 'rooms'])->name('rooms.index');
        Route::get('/teachers', [LookupController::class, 'teachers'])->middleware('role:1')->name('teachers.index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/chat/message', [V1ChatController::class, 'sendMessage'])
            ->middleware('throttle:api-chat')
            ->name('chat.message');

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
            Route::get('/journals/available-schedules', [V1JournalController::class, 'availableSchedules'])->name('journals.available-schedules');
            Route::get('/journals/prepare', [V1JournalController::class, 'prepare'])->name('journals.prepare');
            Route::post('/journals', [V1JournalController::class, 'store'])->name('journals.store');
            Route::put('/journals/{journal}', [V1JournalController::class, 'update'])->name('journals.update');
            Route::put('/journals/{journal}/attendances', [V1JournalController::class, 'updateAttendances'])->name('journals.attendances.update');
        });

        Route::middleware('role:1,2,3')->group(function () {
            Route::get('/journals', [V1JournalController::class, 'index'])->name('journals.index');
            Route::get('/journals/{journal}', [V1JournalController::class, 'show'])->name('journals.show');
            Route::get('/journals/{journal}/photo', [V1JournalController::class, 'photo'])->name('journals.photo');

            Route::get('/room-schedules', [V1RoomScheduleController::class, 'index'])->name('room-schedules.index');
            Route::post('/room-schedules', [V1RoomScheduleController::class, 'store'])->name('room-schedules.store');
            Route::delete('/room-schedules/{roomSchedule}', [V1RoomScheduleController::class, 'destroy'])->name('room-schedules.destroy');
        });

        Route::middleware('role:1,3,4')->group(function () {
            Route::get('/leave-requests', [V1LeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::get('/leave-requests/{leaveRequest}', [V1LeaveRequestController::class, 'show'])->name('leave-requests.show');
            Route::get('/leave-requests/{leaveRequest}/document', [V1LeaveRequestController::class, 'document'])->name('leave-requests.document');
        });
        Route::post('/leave-requests', [V1LeaveRequestController::class, 'store'])->middleware(['role:4', 'throttle:10,1'])->name('leave-requests.store');
        Route::patch('/leave-requests/{leaveRequest}/status', [V1LeaveRequestController::class, 'updateStatus'])->middleware('role:1,3')->name('leave-requests.update-status');

        Route::get('/attendance', [V1AttendanceController::class, 'index'])->middleware('role:4')->name('attendance.index');

        Route::middleware('role:1,2,3')->group(function () {
            Route::get('/classes/{class}', [V1ClassController::class, 'show'])->name('classes.show');
            Route::get('/classes/{class}/students', [V1ClassController::class, 'students'])->name('classes.students');
            Route::get('/classes/{class}/attendance', [V1ClassController::class, 'attendance'])->name('classes.attendance');
            Route::get('/classes/{class}/export', [V1ClassController::class, 'export'])->name('classes.export');
            Route::get('/classes/{class}/login-cards', [V1ClassController::class, 'exportLoginCards'])->name('classes.login-cards');
        });

        Route::middleware('role:1,3')->group(function () {
            Route::post('/classes', [V1ClassController::class, 'store'])->name('classes.store');
            Route::put('/classes/{class}', [V1ClassController::class, 'update'])->name('classes.update');
            Route::delete('/classes/{class}', [V1ClassController::class, 'destroy'])->name('classes.destroy');

            Route::get('/academic-calendar', [V1AcademicCalendarController::class, 'index'])->name('academic-calendar.index');
            Route::post('/academic-calendar', [V1AcademicCalendarController::class, 'store'])->name('academic-calendar.store');
            Route::delete('/academic-calendar/{academicCalendar}', [V1AcademicCalendarController::class, 'destroy'])->name('academic-calendar.destroy');

            Route::get('/ineffective-days', [V1IneffectiveDayController::class, 'index'])->name('ineffective-days.index');
            Route::post('/ineffective-days', [V1IneffectiveDayController::class, 'store'])->name('ineffective-days.store');
            Route::delete('/ineffective-days/{ineffectiveDay}', [V1IneffectiveDayController::class, 'destroy'])->name('ineffective-days.destroy');

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
            Route::get('/system/email-settings', [\App\Http\Controllers\Api\V1\SystemEmailController::class, 'show'])->name('system.email-settings.show');
            Route::post('/system/email-settings', [\App\Http\Controllers\Api\V1\SystemEmailController::class, 'update'])->name('system.email-settings.update');
            Route::post('/system/email-settings/test', [\App\Http\Controllers\Api\V1\SystemEmailController::class, 'sendTest'])->name('system.email-settings.test');
            Route::get('/waka/swap-report', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'wakaReport'])->name('waka.swap-report');
            Route::get('/waka/swap-report/{id}', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'wakaShow'])->name('waka.swap-report.show');
            Route::get('/waka/swap-logs', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'wakaLogs'])->name('waka.swap-logs');
        });

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

        // Schedule Swaps
        Route::get('/schedule-swaps', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'index'])->name('schedule-swaps.index');
        Route::get('/schedule-swaps/candidates', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'candidates'])->name('schedule-swaps.candidates');
        Route::post('/schedule-swaps', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'store'])->name('schedule-swaps.store');
        Route::post('/schedule-swaps/{scheduleSwap}/approve', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'approve'])->name('schedule-swaps.approve');
        Route::post('/schedule-swaps/{scheduleSwap}/reject', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'reject'])->name('schedule-swaps.reject');
        Route::post('/schedule-swaps/{scheduleSwap}/cancel', [\App\Http\Controllers\Api\V1\ScheduleSwapController::class, 'cancel'])->name('schedule-swaps.cancel');
    });
});

<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleImportController;
use App\Http\Controllers\SystemConfigurationController;
use App\Http\Middleware\CheckScheduleLock;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login-background', [\App\Http\Controllers\Waka\LoginBackgroundController::class, 'stream'])->name('login.background');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/email', [\App\Http\Controllers\ProfileEmailController::class, 'requestChange'])->name('profile.email.request');
    Route::post('/profile/email/verify', [\App\Http\Controllers\ProfileEmailController::class, 'verify'])->middleware('throttle:6,1')->name('profile.email.verify');
    Route::delete('/profile/email', [\App\Http\Controllers\ProfileEmailController::class, 'remove'])->name('profile.email.remove');
    Route::post('/chat/message', [ChatController::class, 'sendMessage'])->name('chat.message');
});

Route::middleware(['auth', 'role:1'])->group(function () {
    Route::prefix('system-configuration')->name('system-configuration.')->group(function () {
        Route::get('/', [SystemConfigurationController::class, 'edit'])->name('edit');
        Route::patch('/toggle-cycle', [SystemConfigurationController::class, 'toggleCycle'])->name('toggle-cycle');
        Route::patch('/toggle-lock', [SystemConfigurationController::class, 'toggleLock'])->name('toggle-lock');
        Route::get('/email', [\App\Http\Controllers\SystemEmailController::class, 'edit'])->name('email');
        Route::post('/email', [\App\Http\Controllers\SystemEmailController::class, 'update'])->name('email.update');
        Route::post('/email/test', [\App\Http\Controllers\SystemEmailController::class, 'sendTest'])->name('email.test');
    });

    Route::prefix('waka')->name('waka.')->group(function () {
        Route::get('/settings', [SystemConfigurationController::class, 'edit'])->name('settings');
        Route::post('/settings/gemini', [SystemConfigurationController::class, 'updateGeminiConfig'])->name('settings.gemini');
        Route::get('/settings/login-background', [\App\Http\Controllers\Waka\LoginBackgroundController::class, 'index'])->name('settings.login-background');
        Route::post('/settings/login-background', [\App\Http\Controllers\Waka\LoginBackgroundController::class, 'update'])->middleware('throttle:10,1')->name('settings.login-background.update');
        Route::delete('/settings/login-background', [\App\Http\Controllers\Waka\LoginBackgroundController::class, 'destroy'])->name('settings.login-background.destroy');
        Route::post('/schedules/import-pdf', [ScheduleImportController::class, 'import'])->name('schedules.import');
        Route::get('/schedules/import', [ScheduleImportController::class, 'index'])->name('schedules.import.index');
        Route::post('/schedules/import/preview', [ScheduleImportController::class, 'preview'])->name('schedules.import.preview');
        Route::post('/schedules/import/commit', [ScheduleImportController::class, 'commit'])->name('schedules.import.commit');
        Route::get('/import-pegawai', [\App\Http\Controllers\EmployeeImportController::class, 'index'])->name('import-pegawai.index');
        Route::post('/import-pegawai/preview', [\App\Http\Controllers\EmployeeImportController::class, 'preview'])->name('import-pegawai.preview');
        Route::post('/import-pegawai/commit', [\App\Http\Controllers\EmployeeImportController::class, 'commit'])->name('import-pegawai.commit');
        Route::get('/import-wali-bk', [\App\Http\Controllers\HomeroomImportController::class, 'index'])->name('import-wali-bk.index');
        Route::post('/import-wali-bk/preview', [\App\Http\Controllers\HomeroomImportController::class, 'preview'])->name('import-wali-bk.preview');
        Route::post('/import-wali-bk/commit', [\App\Http\Controllers\HomeroomImportController::class, 'commit'])->name('import-wali-bk.commit');
        Route::post('/import-email', [\App\Http\Controllers\TeacherEmailImportController::class, 'preview'])->name('import-email');
    });
});

Route::middleware(['auth', 'role:1'])->prefix('ai-importer')->name('ai-importer.')->group(function () {
    Route::post('/process', [\App\Http\Controllers\AiImporterController::class, 'process'])->name('process');
    Route::post('/store', [\App\Http\Controllers\AiImporterController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'role:1,2,3'])->prefix('classes')->name('classes.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SchoolClassController::class, 'index'])->name('index');
    Route::get('/{class}', [\App\Http\Controllers\SchoolClassController::class, 'show'])->name('show');
    Route::get('/{class}/students', [\App\Http\Controllers\SchoolClassController::class, 'students'])->name('students');
    Route::get('/{class}/export', [\App\Http\Controllers\SchoolClassController::class, 'exportAttendance'])->name('export');
    Route::get('/{class}/login-cards', [\App\Http\Controllers\SchoolClassController::class, 'exportLoginCards'])->name('login-cards');
});

Route::middleware(['auth', 'role:1,3'])->prefix('classes')->name('classes.')->group(function () {
    Route::post('/', [\App\Http\Controllers\SchoolClassController::class, 'store'])->name('store');
    Route::put('/{class}', [\App\Http\Controllers\SchoolClassController::class, 'update'])->name('update');
    Route::put('/{class}/assignments', [\App\Http\Controllers\SchoolClassController::class, 'updateAssignments'])->name('assignments.update');
    Route::delete('/{class}', [\App\Http\Controllers\SchoolClassController::class, 'destroy'])->name('destroy');
    Route::post('/import-students', [\App\Http\Controllers\StudentImportController::class, 'import'])->name('import-students');
});

Route::middleware(['auth', 'role:1,3'])->prefix('ineffective-days')->name('ineffective-days.')->group(function () {
    Route::get('/', [\App\Http\Controllers\IneffectiveDayController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\IneffectiveDayController::class, 'store'])->name('store');
    Route::delete('/{ineffectiveDay}', [\App\Http\Controllers\IneffectiveDayController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:1,3'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\UserController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    Route::patch('/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('reset-password');
    Route::post('/bulk-reset-passwords', [\App\Http\Controllers\UserController::class, 'bulkResetTeacherPasswords'])->name('bulk-reset-passwords')->middleware('role:1');
});

Route::middleware(['auth', 'role:1,2'])->prefix('schedules')->name('schedules.')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('index');
    Route::post('/import-pdf', [ScheduleImportController::class, 'import'])->name('import')->middleware('role:1');
    Route::get('/create', [ScheduleController::class, 'create'])->name('create')->middleware(CheckScheduleLock::class);
    Route::post('/', [ScheduleController::class, 'store'])->name('store')->middleware(CheckScheduleLock::class);
    Route::get('/{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit')->middleware(CheckScheduleLock::class);
    Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update')->middleware(CheckScheduleLock::class);
    Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy')->middleware(CheckScheduleLock::class);
});

Route::middleware(['auth'])->prefix('journals')->name('journals.')->group(function () {
    Route::get('/', [JournalController::class, 'index'])->name('index')->middleware('role:1,2,3');
    Route::middleware('role:2')->group(function () {
        Route::get('/create', [JournalController::class, 'create'])->name('create');
        Route::get('/leaves', [JournalController::class, 'leaves'])->name('leaves');
        Route::post('/', [JournalController::class, 'store'])->name('store');
        Route::put('/{journal}', [JournalController::class, 'update'])->name('update');
    });
    Route::get('/{journal}', [JournalController::class, 'show'])->name('show')->middleware('role:1,2,3');
    Route::get('/{journal}/photo', [JournalController::class, 'photo'])->name('photo')->middleware('role:1,2,3');
});

Route::middleware(['auth', 'role:2'])->prefix('attendances')->name('attendances.')->group(function () {
    Route::get('/{journal}/edit', [AttendanceController::class, 'edit'])->name('edit');
    Route::put('/{journal}', [AttendanceController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'role:4'])
    ->get('/attendance', [\App\Http\Controllers\StudentAttendanceController::class, 'index'])
    ->name('student.attendance.index');

Route::middleware(['auth'])->prefix('leave-requests')->name('leave-requests.')->group(function () {
    Route::middleware('role:1,3,4')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::get('/create', [LeaveRequestController::class, 'create'])->name('create')->middleware('role:4');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('store')->middleware(['role:4', 'throttle:10,1']);
        Route::get('/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('show');
        Route::get('/{leaveRequest}/document', [LeaveRequestController::class, 'document'])->name('document');
    });
    Route::middleware('role:1,3')->group(function () {
        Route::patch('/{leaveRequest}/status', [LeaveRequestController::class, 'updateStatus'])->name('update-status');
    });
});

Route::middleware(["auth", "role:1,2,3"])->group(function () {
    Route::get("/room-schedules", [\App\Http\Controllers\RoomScheduleController::class, "index"])->name("room-schedules.index");
    Route::get("/room-schedules/create", [\App\Http\Controllers\RoomScheduleController::class, "create"])->name("room-schedules.create");
    Route::post("/room-schedules", [\App\Http\Controllers\RoomScheduleController::class, "store"])->name("room-schedules.store");
    Route::delete("/room-schedules/{roomSchedule}", [\App\Http\Controllers\RoomScheduleController::class, "destroy"])->name("room-schedules.destroy");
    Route::post("/room-schedules/import", [\App\Http\Controllers\RoomScheduleController::class, "import"])->name("room-schedules.import");
});

Route::middleware(['auth', 'role:1,2'])->prefix('schedule-swaps')->name('schedule-swaps.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ScheduleSwapController::class, 'index'])->name('index');
    Route::get('/candidates', [\App\Http\Controllers\ScheduleSwapController::class, 'candidates'])->name('candidates');
    Route::get('/export-csv', [\App\Http\Controllers\ScheduleSwapController::class, 'exportCsv'])->name('export-csv');
    Route::get('/print', [\App\Http\Controllers\ScheduleSwapController::class, 'printReport'])->name('print');
    Route::get('/{scheduleSwap}', [\App\Http\Controllers\ScheduleSwapController::class, 'show'])->name('show');
    Route::post('/', [\App\Http\Controllers\ScheduleSwapController::class, 'store'])->name('store');
    Route::post('/{scheduleSwap}/approve', [\App\Http\Controllers\ScheduleSwapController::class, 'approve'])->name('approve');
    Route::post('/{scheduleSwap}/reject', [\App\Http\Controllers\ScheduleSwapController::class, 'reject'])->name('reject');
    Route::post('/{scheduleSwap}/cancel', [\App\Http\Controllers\ScheduleSwapController::class, 'cancel'])->name('cancel');
});

Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:1,3'])->prefix('academic-calendar')->name('academic-calendar.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AcademicCalendarController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\AcademicCalendarController::class, 'store'])->name('store');
    Route::delete('/{academicCalendar}', [\App\Http\Controllers\AcademicCalendarController::class, 'destroy'])->name('destroy');
    Route::post('/import', [\App\Http\Controllers\AcademicCalendarController::class, 'import'])->name('import');
});

Route::middleware(['auth'])->prefix('exams')->name('exams.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ExamController::class, 'index'])->name('index');
    Route::middleware('role:1,2')->group(function () {
        Route::get('/create', [\App\Http\Controllers\ExamController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ExamController::class, 'store'])->name('store');
        Route::get('/{exam}/edit', [\App\Http\Controllers\ExamController::class, 'edit'])->name('edit');
        Route::put('/{exam}', [\App\Http\Controllers\ExamController::class, 'update'])->name('update');
        Route::delete('/{exam}', [\App\Http\Controllers\ExamController::class, 'destroy'])->name('destroy');
    });
    Route::get('/{exam}', [\App\Http\Controllers\ExamController::class, 'show'])->name('show');
    Route::get('/{exam}/download', [\App\Http\Controllers\ExamController::class, 'download'])->name('download');
});

<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        // Attendance routes (Mahasiswa) with rate limiting
        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/attendance/history', [AttendanceController::class, 'history']);
        Route::get('/schedules/today', [AttendanceController::class, 'todaySchedules']);

        // Admin routes
        Route::middleware('admin')->group(function () {
            // Location management
            Route::get('/locations', [LocationController::class, 'index']);
            Route::post('/locations', [LocationController::class, 'store']);
            Route::put('/locations/{id}', [LocationController::class, 'update']);

            // Schedule management
            Route::get('/schedules', [ScheduleController::class, 'index']);
            Route::post('/schedules', [ScheduleController::class, 'store']);

            // Reports
            Route::get('/reports/attendance', [ReportController::class, 'attendanceReport']);
            Route::get('/reports/attendance/export', [ReportController::class, 'exportExcel']);
        });
    });
});

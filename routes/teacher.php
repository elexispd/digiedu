<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TeacherPortalController;

// Teacher Portal Routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'teacherAnalytics'])->name('api.teacher.dashboard');
    
    // Classes
    Route::get('/classes', [TeacherPortalController::class, 'myClasses'])->name('api.teacher.classes');
    Route::get('/classes/{id}', [TeacherPortalController::class, 'showClass'])->name('api.teacher.classes.show');
    Route::get('/classes/{id}/students', [TeacherPortalController::class, 'classStudents'])->name('api.teacher.classes.students');

    // Timetable
    Route::get('/timetable', [TeacherPortalController::class, 'myTimetable'])->name('api.teacher.timetable');
    
    // Two-Factor Authentication
    Route::get('/2fa/status', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'status'])->name('api.teacher.2fa.status');
    Route::post('/2fa/send-otp', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'sendEnableOtp'])->name('api.teacher.2fa.send-otp');
    Route::post('/2fa/verify-enable', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'verifyEnable'])->name('api.teacher.2fa.verify-enable');
    Route::post('/2fa/disable', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'disable'])->name('api.teacher.2fa.disable');
});

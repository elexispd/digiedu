<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GuardianPortalController;

// Unauthenticated Activation Routes
Route::post('/activation/send-code', [GuardianPortalController::class, 'sendActivationCode'])->name('api.guardian.activation.send');
Route::post('/activation/verify', [GuardianPortalController::class, 'verifyActivationCode'])->name('api.guardian.activation.verify');

Route::middleware(['auth:sanctum', 'role:parent'])->group(function () {
    Route::post('/activation/reset-password', [GuardianPortalController::class, 'resetPassword'])->name('api.guardian.activation.reset-password');
    
    // The following routes could be grouped under a middleware that checks if they are activated, 
    // but for now they are accessible to authenticated guardians.
    Route::middleware(['active'])->group(function () {
        Route::put('/preferences', [GuardianPortalController::class, 'updatePreferences'])->name('api.guardian.preferences.update');
        Route::get('/my-students', [GuardianPortalController::class, 'myStudents'])->name('api.guardian.students.index');
        Route::get('/students/{student}/dashboard', [GuardianPortalController::class, 'studentDashboard'])->name('api.guardian.students.dashboard');
        Route::get('/students/{student}/badges', [GuardianPortalController::class, 'studentBadges'])->name('api.guardian.students.badges');
        Route::get('/students/{student}/behaviors', [GuardianPortalController::class, 'studentBehaviors'])->name('api.guardian.students.behaviors');
        Route::get('/students/{student}/history', [GuardianPortalController::class, 'studentAcademicHistory'])->name('api.guardian.students.history');
        Route::get('/students/{student}/performance', [GuardianPortalController::class, 'studentAcademicPerformance'])->name('api.guardian.students.performance');
        Route::get('/students/{student}/attendance', [GuardianPortalController::class, 'studentAttendance'])->name('api.guardian.students.attendance');
        Route::get('/students/{student}/timetable', [GuardianPortalController::class, 'studentTimetable'])->name('api.guardian.students.timetable');

        Route::get('/announcements', [GuardianPortalController::class, 'announcements'])->name('api.guardian.announcements.index');
        Route::get('/announcements/count', [GuardianPortalController::class, 'announcementsCount'])->name('api.guardian.announcements.count');
        Route::get('/announcements/unread-count', [GuardianPortalController::class, 'announcementsCount'])->name('api.guardian.announcements.unread-count');
        Route::put('/announcements/read-all', [GuardianPortalController::class, 'markAllAnnouncementsRead'])->name('api.guardian.announcements.read-all');
        Route::put('/announcements/{id}/read', [GuardianPortalController::class, 'markAnnouncementRead'])->name('api.guardian.announcements.read');

        Route::get('/notifications', [GuardianPortalController::class, 'announcements'])->name('api.guardian.notifications.index');
        Route::get('/notifications/count', [GuardianPortalController::class, 'announcementsCount'])->name('api.guardian.notifications.count');
        Route::get('/notifications/unread-count', [GuardianPortalController::class, 'announcementsCount'])->name('api.guardian.notifications.unread-count');
        Route::put('/notifications/read-all', [GuardianPortalController::class, 'markAllAnnouncementsRead'])->name('api.guardian.notifications.read-all');
        Route::put('/notifications/{id}/read', [GuardianPortalController::class, 'markAnnouncementRead'])->name('api.guardian.notifications.read');

        // Two-Factor Authentication
        Route::get('/2fa/status', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'status'])->name('api.guardian.2fa.status');
        Route::post('/2fa/send-otp', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'sendEnableOtp'])->name('api.guardian.2fa.send-otp');
        Route::post('/2fa/verify-enable', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'verifyEnable'])->name('api.guardian.2fa.verify-enable');
        Route::post('/2fa/disable', [\App\Http\Controllers\Api\Auth\TwoFactorController::class, 'disable'])->name('api.guardian.2fa.disable');
    });
});

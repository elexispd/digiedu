<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GuardianPortalController;

// Guardian Portal Routes
Route::middleware(['auth:sanctum', 'role:parent'])->group(function () {
    Route::post('/activation/send-code', [GuardianPortalController::class, 'sendActivationCode'])->name('api.guardian.activation.send');
    Route::post('/activation/verify', [GuardianPortalController::class, 'verifyActivationCode'])->name('api.guardian.activation.verify');
    Route::post('/activation/reset-password', [GuardianPortalController::class, 'resetPassword'])->name('api.guardian.activation.reset-password');
    
    // The following routes could be grouped under a middleware that checks if they are activated, 
    // but for now they are accessible to authenticated guardians.
    Route::middleware(['active'])->group(function () {
        Route::put('/preferences', [GuardianPortalController::class, 'updatePreferences'])->name('api.guardian.preferences.update');
        Route::get('/my-students', [GuardianPortalController::class, 'myStudents'])->name('api.guardian.students.index');
        Route::get('/students/{student}/dashboard', [GuardianPortalController::class, 'studentDashboard'])->name('api.guardian.students.dashboard');
        Route::get('/students/{student}/behaviors', [GuardianPortalController::class, 'studentBehaviors'])->name('api.guardian.students.behaviors');
        Route::get('/students/{student}/performance', [GuardianPortalController::class, 'studentAcademicPerformance'])->name('api.guardian.students.performance');
        Route::get('/students/{student}/attendance', [GuardianPortalController::class, 'studentAttendance'])->name('api.guardian.students.attendance');

        Route::get('/announcements', [GuardianPortalController::class, 'announcements'])->name('api.guardian.announcements.index');
        Route::put('/announcements/read-all', [GuardianPortalController::class, 'markAllAnnouncementsRead'])->name('api.guardian.announcements.read-all');
        Route::put('/announcements/{id}/read', [GuardianPortalController::class, 'markAnnouncementRead'])->name('api.guardian.announcements.read');
    });
});

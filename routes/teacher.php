<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TeacherPortalController;

// Teacher Portal Routes
Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'teacherAnalytics'])->name('api.teacher.dashboard');
    
    // Classes
    Route::get('/classes', [TeacherPortalController::class, 'myClasses'])->name('api.teacher.classes');
    
});

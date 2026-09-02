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
    
});

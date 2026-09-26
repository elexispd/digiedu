<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\PermissionsController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\VerifyOtpController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\LoginController;

Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', LoginController::class)->name('api.auth.login');
        Route::post('/forgot-password', ForgotPasswordController::class)->name('api.password.email');
        Route::post('/verify-otp', VerifyOtpController::class)->name('api.password.verify-otp');
        Route::post('/reset-password', ResetPasswordController::class)->name('api.password.update');
        Route::post('/staff/activation', [\App\Http\Controllers\Api\Auth\StaffActivationController::class, 'activate'])->name('api.auth.staff.activate');

        
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
            Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
            Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('api.profile.password.update');
            Route::get('/profile/notification-settings', [ProfileController::class, 'getNotificationSettings'])->name('api.profile.notification-settings.show');
            Route::put('/profile/notification-settings', [ProfileController::class, 'updateNotificationSettings'])->name('api.profile.notification-settings.update');
        });
    });

    // Roles & Permissions Routes (Admin Only)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/permissions', [PermissionsController::class, 'index'])->name('api.permissions.index');
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('roles')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('api.roles.index');
        Route::post('/', [RolesController::class, 'store'])->name('api.roles.store');
        Route::get('/{id}', [RolesController::class, 'show'])->name('api.roles.show');
        Route::put('/{id}', [RolesController::class, 'update'])->name('api.roles.update');
        Route::delete('/{id}', [RolesController::class, 'destroy'])->name('api.roles.destroy');
    });

    // Classes and Class Arms Routes (Admin Only)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::apiResource('badges', \App\Http\Controllers\Api\BadgeController::class);
        Route::get('/classes/categories', [\App\Http\Controllers\Api\SchoolClassController::class, 'getCategories'])->name('api.classes.categories');
        Route::apiResource('classes', \App\Http\Controllers\Api\SchoolClassController::class);
        
        // Custom route for adding an arm to a specific class
        Route::post('/classes/{classId}/arms', [\App\Http\Controllers\Api\ClassArmController::class, 'store'])->name('api.classes.arms.store');
        
        // Routes for updating and deleting specific arms directly
        Route::put('/arms/{id}', [\App\Http\Controllers\Api\ClassArmController::class, 'update'])->name('api.arms.update');
        Route::delete('/arms/{id}', [\App\Http\Controllers\Api\ClassArmController::class, 'destroy'])->name('api.arms.destroy');
        // Academic Sessions Routes
        Route::get('/sessions/terms', [\App\Http\Controllers\Api\AcademicSessionController::class, 'getTerms'])->name('api.sessions.terms');
        Route::apiResource('sessions', \App\Http\Controllers\Api\AcademicSessionController::class)->except(['show']);
        Route::post('/sessions/{id}/set-current', [\App\Http\Controllers\Api\AcademicSessionController::class, 'setCurrent'])->name('api.sessions.set-current');

        // School Profile (Onboarding Step 1)
        Route::get('/school-profile', [\App\Http\Controllers\Api\SchoolProfileController::class, 'index'])->name('api.school-profile.index');
        Route::post('/school-profile', [\App\Http\Controllers\Api\SchoolProfileController::class, 'store'])->name('api.school-profile.store');
        Route::put('/school-profile', [\App\Http\Controllers\Api\SchoolProfileController::class, 'update'])->name('api.school-profile.update');

        // Onboarding Progress State Tracker
        Route::get('/onboarding-progress', [\App\Http\Controllers\Api\OnboardingProgressController::class, 'index'])->name('api.onboarding-progress.index');
        Route::put('/onboarding-progress', [\App\Http\Controllers\Api\OnboardingProgressController::class, 'update'])->name('api.onboarding-progress.update');

        // Academic Settings (Step 3)
        Route::get('/academic-settings', [\App\Http\Controllers\Api\AcademicSettingController::class, 'index'])->name('api.academic-settings.index');
        Route::put('/academic-settings', [\App\Http\Controllers\Api\AcademicSettingController::class, 'update'])->name('api.academic-settings.update');

        // Grade Rules (Step 3)
        Route::get('/grade-rules', [\App\Http\Controllers\Api\GradeRuleController::class, 'index'])->name('api.grade-rules.index');
        Route::post('/grade-rules/bulk', [\App\Http\Controllers\Api\GradeRuleController::class, 'bulkStore'])->name('api.grade-rules.bulk');
        Route::delete('/grade-rules/{id}', [\App\Http\Controllers\Api\GradeRuleController::class, 'destroy'])->name('api.grade-rules.destroy');

        // Result Configurations (Step 3 - Admin setup)
        Route::post('/result-configurations/bulk', [\App\Http\Controllers\Api\ResultConfigurationController::class, 'bulkStore'])->name('api.result-configurations.bulk');

        // Initial Administrators (Step 4)
        Route::get('/administrators', [\App\Http\Controllers\Api\AdministratorController::class, 'index'])->name('api.administrators.index');
        Route::post('/administrators', [\App\Http\Controllers\Api\AdministratorController::class, 'store'])->name('api.administrators.store');
        Route::post('/administrators/bulk', [\App\Http\Controllers\Api\AdministratorController::class, 'bulkStore'])->name('api.administrators.bulk');
        Route::put('/administrators/{id}', [\App\Http\Controllers\Api\AdministratorController::class, 'update'])->name('api.administrators.update');
        Route::delete('/administrators/{id}', [\App\Http\Controllers\Api\AdministratorController::class, 'destroy'])->name('api.administrators.destroy');

        // Guardians
        Route::get('/guardians/search', [\App\Http\Controllers\Api\GuardianController::class, 'search'])->name('api.guardians.search');
        Route::post('/guardians/import', [\App\Http\Controllers\Api\GuardianController::class, 'import'])->name('api.guardians.import');
        Route::post('/guardians/{guardian}/students', [\App\Http\Controllers\Api\GuardianController::class, 'assignStudents'])->name('api.guardians.students.assign');
        Route::post('/guardians/{guardian}/send-reset-pin', [\App\Http\Controllers\Api\GuardianController::class, 'sendResetPin'])->name('api.guardians.send-reset-pin');
        Route::post('/guardians/{guardian}/reset-password', [\App\Http\Controllers\Api\GuardianController::class, 'resetPassword'])->name('api.guardians.reset-password');
        Route::apiResource('guardians', \App\Http\Controllers\Api\GuardianController::class);

        // Subjects
        Route::get('/subjects/search', [\App\Http\Controllers\Api\SubjectController::class, 'search'])->name('api.subjects.search');
        Route::post('/subjects/{subject}/assign', [\App\Http\Controllers\Api\SubjectController::class, 'assignTeachers'])->name('api.subjects.assign');
        Route::post('/subjects/{subject}/unassign', [\App\Http\Controllers\Api\SubjectController::class, 'unassignTeachers'])->name('api.subjects.unassign');
        Route::get('/subjects/{subject}/teachers', [\App\Http\Controllers\Api\SubjectController::class, 'teachers'])->name('api.subjects.teachers');
        Route::apiResource('subjects', \App\Http\Controllers\Api\SubjectController::class);

        // Conducts (Behavior Logging)
        Route::get('/conducts/insights', [\App\Http\Controllers\Api\ConductLogController::class, 'insights'])->name('api.conducts.insights');
        Route::get('/students/{student}/conducts', [\App\Http\Controllers\Api\ConductLogController::class, 'studentConducts'])->name('api.students.conducts');
        Route::apiResource('conducts', \App\Http\Controllers\Api\ConductLogController::class)->except(['create', 'edit']);

        // Extracurricular Activities
        Route::post('/activities/{activity}/enroll', [\App\Http\Controllers\Api\ExtracurricularActivityController::class, 'enrollStudents'])->name('api.activities.enroll');
        Route::post('/activities/{activity}/unenroll', [\App\Http\Controllers\Api\ExtracurricularActivityController::class, 'unenrollStudents'])->name('api.activities.unenroll');
        Route::get('/activities/{activity}/roster', [\App\Http\Controllers\Api\ExtracurricularActivityController::class, 'roster'])->name('api.activities.roster');
        Route::apiResource('activities', \App\Http\Controllers\Api\ExtracurricularActivityController::class);

        // Announcements Management (Admin Only)
        Route::apiResource('announcements', \App\Http\Controllers\Api\AnnouncementController::class)->only(['store', 'update', 'destroy']);

        // Dashboard Analytics (Admin counts)
        Route::get('/dashboard/counts', [\App\Http\Controllers\Api\DashboardController::class, 'analyticCounts'])->name('api.dashboard.counts');
        Route::get('/dashboard/status-analytics', [\App\Http\Controllers\Api\DashboardController::class, 'userStatusAnalytics'])->name('api.dashboard.status-analytics');

        // Student Management & Mutating Operations (Admin Only)
        Route::post('/students/{id}/badges/assign', [\App\Http\Controllers\Api\StudentController::class, 'assignBadge'])->name('api.students.badges.assign');
        Route::delete('/students/{id}/badges/{badge_id}', [\App\Http\Controllers\Api\StudentController::class, 'revokeBadge'])->name('api.students.badges.revoke');
        Route::put('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'update'])->name('api.students.update');
        Route::delete('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'destroy'])->name('api.students.destroy');

        // Dynamic Role-based User RUD (e.g. /users/teacher)
        Route::get('/users/{role}', [\App\Http\Controllers\Api\RoleUserController::class, 'index'])->name('api.users.role.index');
        Route::get('/users/{role}/{id}', [\App\Http\Controllers\Api\RoleUserController::class, 'show'])->name('api.users.role.show');
        Route::put('/users/{role}/{id}', [\App\Http\Controllers\Api\RoleUserController::class, 'update'])->name('api.users.role.update');
        Route::delete('/users/{role}/{id}', [\App\Http\Controllers\Api\RoleUserController::class, 'destroy'])->name('api.users.role.destroy');

        // Student Registration (Multi-step)
        Route::post('/students', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'storeBasicInfo'])->name('api.students.storeBasicInfo');
        Route::post('/students/import', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'import'])->name('api.students.import');
        Route::put('/students/{id}/academic', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'updateAcademicInfo'])->name('api.students.updateAcademicInfo');
        Route::put('/students/{id}/guardians', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'updateGuardians'])->name('api.students.updateGuardians');
        Route::post('/students/{id}/documents', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'storeDocument'])->name('api.students.storeDocument');
        Route::put('/students/{id}/account-setup', [\App\Http\Controllers\Api\StudentRegistrationController::class, 'updateAccountSetup'])->name('api.students.updateAccountSetup');
    });

    // Announcements & Students Read Routes (Accessible to Admin & Teacher)
    Route::middleware(['auth:sanctum', 'role:admin|teacher'])->group(function () {
        // Announcements Read
        Route::get('/announcements/count', [\App\Http\Controllers\Api\AnnouncementController::class, 'count'])->name('api.announcements.count');
        Route::get('/announcements/unread-count', [\App\Http\Controllers\Api\AnnouncementController::class, 'count'])->name('api.announcements.unread-count');
        Route::get('/announcements', [\App\Http\Controllers\Api\AnnouncementController::class, 'index'])->name('api.announcements.index');
        Route::get('/announcements/{announcement}', [\App\Http\Controllers\Api\AnnouncementController::class, 'show'])->name('api.announcements.show');

        // Students Read
        Route::get('/students', [\App\Http\Controllers\Api\StudentController::class, 'index'])->name('api.students.index');
        Route::get('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'show'])->name('api.students.show');
        Route::get('/students/{id}/badges', [\App\Http\Controllers\Api\StudentController::class, 'badges'])->name('api.students.badges');
    });

    // Student Attendance & Results (Authorized via Gate permissions)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Result Configurations Lookup
        Route::get('/result-configurations', [\App\Http\Controllers\Api\ResultConfigurationController::class, 'index'])->name('api.result-configurations.index');

        // Student Attendance
        Route::post('/attendance/mark', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'markAttendance'])->name('api.attendance.mark');
        Route::get('/attendance/metrics', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'metrics'])->name('api.attendance.metrics');
        Route::get('/attendance/weekly-metrics', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'weeklyMetrics'])->name('api.attendance.weeklyMetrics');
        Route::get('/attendance/records', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'records'])->name('api.attendance.records');
        Route::get('/attendance/watchlist', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'watchlist'])->name('api.attendance.watchlist');
        
        // Student Results & Ranking
        Route::prefix('results')->group(function () {
            Route::get('/template', [\App\Http\Controllers\Api\ResultController::class, 'downloadTemplate'])->name('api.results.template');
            Route::post('/single', [\App\Http\Controllers\Api\ResultController::class, 'uploadSingle'])->name('api.results.single');
            Route::post('/bulk-manual', [\App\Http\Controllers\Api\ResultController::class, 'uploadBulkManual'])->name('api.results.bulk-manual');
            Route::post('/import', [\App\Http\Controllers\Api\ResultController::class, 'uploadImport'])->name('api.results.import');
            Route::post('/compute', [\App\Http\Controllers\Api\ResultController::class, 'computeRanking'])->name('api.results.compute');

            // Result Retrieval
            Route::get('/student/{student_id}', [\App\Http\Controllers\Api\ResultRetrievalController::class, 'getStudentTermResult'])->name('api.results.student');
            Route::get('/class/{school_class_id}/arm/{class_arm_id}', [\App\Http\Controllers\Api\ResultRetrievalController::class, 'getClassTermResults'])->name('api.results.class');
            Route::get('/class/{school_class_id}/arm/{class_arm_id}/mastersheet', [\App\Http\Controllers\Api\ResultRetrievalController::class, 'getMastersheet'])->name('api.results.mastersheet');
            Route::get('/class/{school_class_id}/arm/{class_arm_id}/subject/{subject_id}', [\App\Http\Controllers\Api\ResultRetrievalController::class, 'getClassSubjectScores'])->name('api.results.subject');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Messaging Routes — Unified for all roles (admin, teacher, guardian/parent)
    // Role-specific logic (contacts filtering, authorisation) is handled in the
    // MessageController based on the authenticated user's Spatie role.
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum'])->prefix('messages')->group(function () {
        Route::get('/unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount'])->name('api.messages.unread-count');
        Route::get('/contacts', [\App\Http\Controllers\Api\MessageController::class, 'contacts'])->name('api.messages.contacts');
        Route::get('/', [\App\Http\Controllers\Api\MessageController::class, 'index'])->name('api.messages.index');
        Route::post('/', [\App\Http\Controllers\Api\MessageController::class, 'store'])->name('api.messages.store');
        Route::get('/{conversation}', [\App\Http\Controllers\Api\MessageController::class, 'show'])->name('api.messages.show');
        Route::put('/{conversation}/read', [\App\Http\Controllers\Api\MessageController::class, 'markRead'])->name('api.messages.read');
    });

    // Timetable & Periods Management Module
    Route::middleware(['auth:sanctum'])->group(function () {
        // Periods management (CRUD & reordering)
        Route::prefix('timetables/periods')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'index'])->name('api.timetables.periods.index');
            Route::post('/', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'store'])->name('api.timetables.periods.store');
            Route::post('/reorder', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'reorder'])->name('api.timetables.periods.reorder');
            Route::get('/{id}', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'show'])->name('api.timetables.periods.show');
            Route::put('/{id}', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'update'])->name('api.timetables.periods.update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\TimetablePeriodController::class, 'destroy'])->name('api.timetables.periods.destroy');
        });

        // Timetable Views & Slot Operations
        Route::prefix('timetables')->group(function () {
            Route::get('/by-teacher', [\App\Http\Controllers\Api\TimetableController::class, 'byTeacher'])->name('api.timetables.by-teacher');
            Route::get('/by-class', [\App\Http\Controllers\Api\TimetableController::class, 'byClass'])->name('api.timetables.by-class');
            Route::get('/master-grid', [\App\Http\Controllers\Api\TimetableController::class, 'masterGrid'])->name('api.timetables.master-grid');
            Route::post('/check-conflict', [\App\Http\Controllers\Api\TimetableController::class, 'checkConflict'])->name('api.timetables.check-conflict');
            Route::post('/', [\App\Http\Controllers\Api\TimetableController::class, 'store'])->name('api.timetables.store');
            Route::get('/{id}', [\App\Http\Controllers\Api\TimetableController::class, 'show'])->name('api.timetables.show');
            Route::put('/{id}', [\App\Http\Controllers\Api\TimetableController::class, 'update'])->name('api.timetables.update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\TimetableController::class, 'destroy'])->name('api.timetables.destroy');
        });
    });

    // Teacher Portal Routes (Separate File)
    Route::prefix('teacher')->group(base_path('routes/teacher.php'));

    // Guardian Portal Routes (Separate File)
    Route::prefix('guardian')->group(base_path('routes/guardian.php'));
});

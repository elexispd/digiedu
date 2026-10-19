<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }

            // Automatic role-based access for teachers to core teacher abilities
            if ($user->hasRole('teacher') && in_array($ability, [
                'view-students',
                'manage-attendance',
                'view-attendance',
                'view-classes',
                'manage-classes',
                'view-subjects',
                'view-results',
                'upload-results',
                'view-conducts',
                'manage-conducts',
                'view-activities',
                'view-timetable',
                'view-announcements',
                'assign-badges',
            ])) {
                return true;
            }

            // Automatic role-based access for parents to core parent abilities
            if ($user->hasRole('parent') && in_array($ability, [
                'view-timetable',
                'view-results',
                'view-attendance',
                'view-announcements',
            ])) {
                return true;
            }

            return null; // Fallback to Spatie permission checks
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}

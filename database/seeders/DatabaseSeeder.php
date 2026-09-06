<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Seed default Spatie Roles
        $roles = ['admin', 'teacher', 'parent', 'student'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Seed default Spatie Permissions
        $permissions = [
            'view-admin-dashboard',
            'manage-users',
            'manage-school-settings',
            'broadcast-emergencies',
            'export-school-reports',
            'view-students',
            'update-students',
            'delete-students',
            'view-attendance',
            'manage-attendance',
            'view-classes',
            'manage-classes',
            'view-guardians',
            'manage-guardians',
            'view-subjects',
            'manage-subjects',
            'create-subjects',
            'update-subjects',
            'delete-subjects',
            'assign-subject-teachers',
            'view-conducts',
            'manage-conducts',
            'view-activities',
            'manage-activities',
            'view-announcements',
            'manage-announcements',
            'view-timetable',
            'manage-timetable',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 3. Associate permissions with roles
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
        }

        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacherRole->syncPermissions([
            'view-students',
            'view-attendance',
            'manage-attendance',
            'view-conducts',
            'manage-conducts',
            'view-activities',
            'view-timetable',
        ]);

        // 4. Seed a bootstrap Admin User
        $adminUser = User::firstOrCreate([
            'email' => 'admin@eduguardian.app'
        ], [
            'name' => 'System Administrator',
            'phone' => '+2348000000000',
            'password' => Hash::make('Password123!'),
            'biometric_enabled' => false,
        ]);

        // Assign the 'admin' role directly via Spatie
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }
    }
}

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use App\Models\Role;

// Create permissions
$permissions = [
    'manage-results',
    'upload-results',
    'view-results'
];

foreach ($permissions as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
}

$admin = Role::where('name', 'admin')->first();
if ($admin) {
    $admin->givePermissionTo(['manage-results', 'upload-results', 'view-results']);
}

$teacher = Role::where('name', 'teacher')->first();
if ($teacher) {
    $teacher->givePermissionTo(['upload-results', 'view-results']);
}

$parent = Role::where('name', 'parent')->first();
if ($parent) {
    $parent->givePermissionTo(['view-results']);
}

$studentRole = Role::where('name', 'student')->first();
if ($studentRole) {
    $studentRole->givePermissionTo(['view-results']);
}

echo "Permissions seeded and assigned successfully.\n";

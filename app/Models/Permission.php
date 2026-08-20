<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    protected $hidden = [
        'guard_name',
        'created_at',
        'updated_at',
        'pivot',
    ];
}

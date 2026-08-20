<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassArm extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_class_id',
        'name',
        'class_capacity'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }
}

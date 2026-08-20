<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\ClassCategoryEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'category',
        'lead_teacher_id'
    ];

    protected function casts(): array
    {
        return [
            'category' => ClassCategoryEnum::class,
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = ['created_at', 'updated_at'];

    public function arms(): HasMany
    {
        return $this->hasMany(ClassArm::class, 'school_class_id');
    }

    public function leadTeacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_teacher_id');
    }
}

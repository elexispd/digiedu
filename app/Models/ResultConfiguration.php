<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ResultConfiguration extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_category',
        'assessment_type',
        'percentage',
        'is_exam',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_exam' => 'boolean'
        ];
    }
}

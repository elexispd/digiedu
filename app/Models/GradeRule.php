<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GradeRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_category',
        'grade',
        'min_score',
        'max_score',
        'remark'
    ];
}

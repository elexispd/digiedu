<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\TermEnum;

class AcademicSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'is_current',
        'current_term'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'current_term' => TermEnum::class,
        ];
    }
}

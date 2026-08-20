<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'academic_session_id',
        'terms_per_year',
        'pass_threshold',
        'grading_standard'
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'document_type',
        'file_path',
        'original_name'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

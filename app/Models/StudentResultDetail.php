<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentResultDetail extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_result_id',
        'result_configuration_id',
        'score',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function studentResult()
    {
        return $this->belongsTo(StudentResult::class, 'student_result_id');
    }

    public function resultConfiguration()
    {
        return $this->belongsTo(ResultConfiguration::class, 'result_configuration_id');
    }
}

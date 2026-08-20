<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SchoolProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_name',
        'slogan',
        'official_email',
        'phone_number',
        'physical_address',
        'logo_url'
    ];
}

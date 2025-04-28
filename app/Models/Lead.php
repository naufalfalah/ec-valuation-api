<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'form_type',
        'source_url',
        'ip',
        'name',
        'phone_number',
        'email',
    ];
}

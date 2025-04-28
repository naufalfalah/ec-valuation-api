<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadDetail extends Model
{
    protected $fillable = [
        'lead_id',
        'lead_form_key',
        'lead_form_value',
    ];
}

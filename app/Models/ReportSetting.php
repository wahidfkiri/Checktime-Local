<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSetting extends Model
{
    protected $fillable = ['period','email_list','sms_enabled'];

    protected $casts = [
        'email_list' => 'array',
        'sms_enabled' => 'boolean'
    ];
}
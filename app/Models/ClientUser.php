<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientUser extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'receive_report_emails'
    ];

    protected $casts = [
        'receive_report_emails' => 'array',
    ];
}

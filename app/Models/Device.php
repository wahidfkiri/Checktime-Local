<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['device_sn', 'ip', 'alias', 'terminal_name', 'area_name', 'last_sync'];
 
    protected $casts = [
        'alias' => 'encrypted',
        'terminal_name' => 'encrypted',
        'area_name' => 'encrypted',
        'ip' => 'encrypted',
    ];
}

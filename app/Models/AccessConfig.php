<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'general_token',
    ];
}

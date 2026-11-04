<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegularizationCause extends Model
{
    protected $fillable = ['label', 'is_protected'];

    protected $casts = [
        'is_protected' => 'boolean',
    ];

    public function regularizations()
    {
        return $this->hasMany(AttendanceRegularization::class, 'cause_id');
    }
}

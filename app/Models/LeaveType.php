<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveType extends Model
{
    protected $fillable = ['name','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

     /**
     * Get the leaves for the leave type.
     */ 

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'type_id');
    }
}

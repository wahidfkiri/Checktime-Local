<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = ['code', 'name', 'area_id'];
    
    // protected $casts = [
    //     'name' => 'encrypted',
    // ];
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

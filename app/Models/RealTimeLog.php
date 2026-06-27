<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealTimeLog extends Model
{
    protected $fillable = ['employee_id','punch_time','punch_state','device_sn'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
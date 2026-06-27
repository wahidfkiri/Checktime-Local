<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRotation extends Model
{
    protected $fillable = [
        'employee_id',
        'start_datetime',
        'end_datetime',
        'rotation_type',
        'work_hours',
        'rest_hours',
        'is_recurring',
        'recurrence_end_date',
        'description',
        'is_active'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'recurrence_end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_datetime', '<=', now())
                    ->where('end_datetime', '>=', now());
    }

    // Méthodes utiles
    public function getDurationHoursAttribute()
    {
        return $this->start_datetime->diffInHours($this->end_datetime);
    }

    public function getNextRotationAttribute()
    {
        if (!$this->is_recurring) {
            return null;
        }
        
        $totalCycle = $this->work_hours + $this->rest_hours;
        return $this->end_datetime->copy()->addHours($this->rest_hours);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkHourType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'break_minutes',
        'is_overnight',
        'has_break',
        'break_times',
        'is_active'
    ];

    protected $casts = [
        'break_times' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_overnight' => 'boolean',
        'has_break' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relations
    public function employeeSchedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'work_hour_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Méthodes utiles
    public function getTotalWorkMinutesAttribute()
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        
        if ($this->is_overnight && $end < $start) {
            $end = strtotime($this->end_time . ' +1 day');
        }
        
        $totalMinutes = ($end - $start) / 60;
        return $totalMinutes - $this->break_minutes;
    }

    public function getTotalWorkHoursAttribute()
    {
        return $this->total_work_minutes / 60;
    }

    public function getFormattedHoursAttribute()
    {
        return date('H:i', strtotime($this->start_time)) . ' - ' . date('H:i', strtotime($this->end_time));
    }
}
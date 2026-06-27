<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPlanning extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_id',
        'planning_date',
        'day_name',
        'planned_start_time',
        'planned_end_time',
        'planned_break_minutes',
        'is_working_day',
        'is_holiday',
        'notes'
    ];

    protected $casts = [
        'planning_date' => 'date',
        'planned_start_time' => 'datetime:H:i',
        'planned_end_time' => 'datetime:H:i',
        'is_working_day' => 'boolean',
        'is_holiday' => 'boolean'
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'schedule_id');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('planning_date', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('planning_date', [$startDate, $endDate]);
    }

    public function scopeWorkingDays($query)
    {
        return $query->where('is_working_day', true);
    }

    // Méthodes utiles
    public function getTotalWorkMinutesAttribute()
    {
        $start = strtotime($this->planned_start_time);
        $end = strtotime($this->planned_end_time);
        
        if ($end < $start) {
            $end = strtotime($this->planned_end_time . ' +1 day');
        }
        
        return (($end - $start) / 60) - $this->planned_break_minutes;
    }

    public function getFormattedHoursAttribute()
    {
        return date('H:i', strtotime($this->planned_start_time)) . ' - ' . 
               date('H:i', strtotime($this->planned_end_time));
    }
}
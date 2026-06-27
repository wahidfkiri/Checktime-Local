<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_type',
        'day_of_week',
        'work_hour_type_id',
        'schedule_date',
        'is_working_day',
        'start_time',
        'end_time',
        'break_minutes',
        'daily_hours', // Nouveau: pour rotation
        'repeat_weekly',
        'start_date',
        'end_date',
        'work_days_count', // Nouveau: pour rotation
        'rest_days_count', // Nouveau: pour rotation
        'notes',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'repeat_weekly' => 'boolean',
        'is_active' => 'boolean',
        'is_working_day' => 'boolean',
        'schedule_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
        'work_days_count' => 'integer',
        'rest_days_count' => 'integer',
        'daily_hours' => 'decimal:2'
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workHourType(): BelongsTo
    {
        return $this->belongsTo(WorkHourType::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Méthodes utiles
    public function getDayNameAttribute()
    {
        $days = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        return $days[$this->day_of_week] ?? null;
    }

    public function getFormattedTimeAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return date('H:i', strtotime($this->start_time)) . ' - ' . date('H:i', strtotime($this->end_time));
        }
        
        if ($this->workHourType) {
            return $this->workHourType->formatted_hours;
        }
        
        return null;
    }

    public function getTotalWorkMinutesAttribute()
    {
        if ($this->start_time && $this->end_time) {
            $start = strtotime($this->start_time);
            $end = strtotime($this->end_time);
            
            if ($end < $start) {
                $end = strtotime($this->end_time . ' +1 day');
            }
            
            return (($end - $start) / 60) - $this->break_minutes;
        }
        
        if ($this->workHourType) {
            return $this->workHourType->total_work_minutes;
        }
        
        return 0;
    }

    // Méthode pour calculer les heures de rotation
    public function calculateRotationHours()
    {
        if ($this->schedule_type !== 'rotation' || !$this->daily_hours) {
            return 0;
        }
        
        return $this->daily_hours * 60; // Convertir en minutes
    }

    // Nouvelle méthode pour vérifier si un planning rotation est actif pour une date
    public function isRotationActiveForDate($date)
    {
        if ($this->schedule_type !== 'rotation') {
            return false;
        }
        
        $date = Carbon::parse($date);
        $startDate = Carbon::parse($this->start_date);
        
        if ($date < $startDate || $date > Carbon::parse($this->end_date)) {
            return false;
        }
        
        // Calculer le nombre de jours depuis le début
        $daysSinceStart = $startDate->diffInDays($date);
        $cycleLength = $this->work_days_count + $this->rest_days_count;
        $positionInCycle = $daysSinceStart % $cycleLength;
        
        // Vérifier si c'est un jour de travail et pas un weekend
        if ($positionInCycle < $this->work_days_count) {
            $dayOfWeek = $date->dayOfWeekIso;
            return !($dayOfWeek == 6 || $dayOfWeek == 7); // Exclure samedi et dimanche
        }
        
        return false;
    }
}
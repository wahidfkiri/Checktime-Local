<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'emp_code',
        'first_name',
        'last_name',
        'area_name',
        'dept_name',
        'phone',
        'email',
        'status'
    ];

    protected $casts = [
    'first_name' => 'encrypted',
    'area_name' => 'encrypted',
    'dept_name' => 'encrypted',
];


    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function area(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }


public function getFullNameAttribute()
{
    return trim($this->first_name . ' ' . ($this->last_name ?? ''));
}

   

    public function schedules()
{
    return $this->hasMany(EmployeeSchedule::class);
}

public function scheduleRotations()
{
    return $this->hasMany(ScheduleRotation::class);
}

public function dailyPlannings()
{
    return $this->hasMany(DailyPlanning::class);
}

public function getTodayScheduleAttribute()
{
    return $this->schedules()->forDate(now())->first();
}

    public function rotations(): HasMany
    {
        return $this->hasMany(ScheduleRotation::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function realTimeLogs(): HasMany
    {
        return $this->hasMany(RealTimeLog::class);
    }

    public function dailyAttendance(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }
}

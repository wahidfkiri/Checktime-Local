<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $table = 'daily_attendance';

    protected $fillable = [
        'employee_id',
        'emp_code',
        'attendance_date',
        'check_in',
        'check_out',
        'working_hours',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'source',
        'shift_name',
        'planned_start',
        'planned_end',
        'total_punches',
        'punch_times',
        'work_hours',
        'break_hours',
        'effective_hours',
        'overtime_hours',
        'is_late',
        'is_early_leave',
        'early_minutes',
        'is_overtime',
        'is_short_work',
        'short_hours',
        'has_multiple_punches',
        'multiple_punches_count',
        'raw_data',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'working_hours' => 'decimal:2',
        'work_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'effective_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'planned_start' => 'datetime',
        'planned_end' => 'datetime',
        'is_late' => 'boolean',
        'is_early_leave' => 'boolean',
        'is_overtime' => 'boolean',
        'is_short_work' => 'boolean',
        'has_multiple_punches' => 'boolean',
        'total_punches' => 'integer',
        'multiple_punches_count' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactions()
    {
        return $this->hasMany(AttendanceTransaction::class);
    }

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }
}
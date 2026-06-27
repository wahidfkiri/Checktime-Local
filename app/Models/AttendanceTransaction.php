<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_attendance_id',
        'employee_id',
        'emp_code',
        'transaction_id',
        'punch_time',
        'punch_state',
        'verify_type',
        'work_code',
        'terminal_sn',
        'terminal_alias',
        'area_alias',
        'longitude',
        'latitude',
        'gps_location',
        'mobile',
        'source',
        'purpose',
        'crc',
        'is_attendance',
        'reserved',
        'upload_time',
        'sync_status',
        'sync_time',
        'temperature',
        'mask_flag',
        'company',
        'terminal',
        'processed'
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'upload_time' => 'datetime',
        'sync_time' => 'datetime',
        'is_attendance' => 'boolean',
        'mask_flag' => 'boolean',
        'processed' => 'boolean',
        'temperature' => 'decimal:2',
        'longitude' => 'decimal:10',
        'latitude' => 'decimal:10'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dailyAttendance()
    {
        return $this->belongsTo(DailyAttendance::class);
    }
}
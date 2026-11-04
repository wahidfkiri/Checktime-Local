<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRegularization extends Model
{
    protected $fillable = ['cause_id', 'employee_id', 'date', 'check_in', 'check_out', 'created_by'];

    protected $casts = [
        'date' => 'date',
    ];

    public function cause()
    {
        return $this->belongsTo(RegularizationCause::class, 'cause_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

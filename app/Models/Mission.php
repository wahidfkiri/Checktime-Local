<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use \App\Models\Concerns\FiltersActiveEmployees;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reference',
        'title',
        'description',
        'destination',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Missions dont la plage [start_date, end_date] chevauche la période.
     * start_date/end_date sont des DATETIME : on compare par date (whereDate),
     * sinon une mission commençant ou finissant le dernier jour de la période
     * avec une heure > 00:00 était écartée.
     */
    public function scopeOverlappingPeriod($query, $startDate, $endDate)
    {
        return $query
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}

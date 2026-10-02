<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use \App\Models\Concerns\FiltersActiveEmployees;
    protected $fillable = ['employee_id','type_id','start_date','end_date','reason','status'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'type_id');
    }

    /**
     * Congés dont la plage [start_date, end_date] chevauche la période.
     * Même test de chevauchement que pour les missions (comparaison par date).
     */
    public function scopeOverlappingPeriod($query, $startDate, $endDate)
    {
        return $query
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmployeeScheduledStatusChange extends Model
{
    protected $fillable = ['employee_id', 'status', 'effective_date', 'applied_at', 'created_by'];

    protected $casts = [
        'effective_date' => 'date',
        'applied_at'     => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('applied_at');
    }

    /**
     * Opérations arrivées à échéance. Une désactivation à la date D (dernier
     * jour pris en compte) s'applique une fois la journée D terminée, pour que
     * la synchronisation récupère les pointages de ce dernier jour ; une
     * activation s'applique dès sa date (premier jour pris en compte).
     */
    public function scopeDue($query, ?Carbon $today = null)
    {
        $today = ($today ?: Carbon::today())->toDateString();

        return $query->pending()->where(function ($q) use ($today) {
            $q->where(function ($q) use ($today) {
                $q->where('status', 'inactive')->where('effective_date', '<', $today);
            })->orWhere(function ($q) use ($today) {
                $q->where('status', 'active')->where('effective_date', '<=', $today);
            });
        });
    }
}

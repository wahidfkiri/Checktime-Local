<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'date_debut',
        'date_fin',
        'start_time',
        'end_time',
        'raison',
        'status',
        'duration_minutes'
    ];

    protected $casts = [
        'date' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Relation avec l'employé
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }


    /**
     * Scope pour les permissions en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les permissions approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope pour les permissions rejetées
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope : permissions dont la plage [date_debut, date_fin] chevauche la période donnée.
     * Utilise COALESCE(date_debut, date) / COALESCE(date_fin, date) pour rester robuste
     * si d'anciennes lignes n'ont pas encore de plage renseignée.
     */
    public function scopeOverlappingPeriod($query, $startDate, $endDate)
    {
        return $query
            ->whereRaw('COALESCE(date_debut, `date`) <= ?', [$endDate])
            ->whereRaw('COALESCE(date_fin, `date`) >= ?', [$startDate]);
    }

    /**
     * Date de début effective de la permission (plage ou, à défaut, date unique).
     */
    public function getEffectiveStartDate()
    {
        return $this->date_debut ?? $this->date;
    }

    /**
     * Date de fin effective de la permission (plage ou, à défaut, date unique).
     */
    public function getEffectiveEndDate()
    {
        return $this->date_fin ?? $this->date_debut ?? $this->date;
    }

    /**
     * Vérifier si la permission couvre un jour donné (plage de dates incluse).
     */
    public function coversDate($date): bool
    {
        $start = $this->getEffectiveStartDate();
        $end = $this->getEffectiveEndDate();

        if (!$start) {
            return false;
        }

        $target = \Carbon\Carbon::parse($date)->startOfDay();
        $startDay = \Carbon\Carbon::parse($start)->startOfDay();
        $endDay = \Carbon\Carbon::parse($end ?? $start)->startOfDay();

        return $target->between($startDay, $endDay);
    }

    /**
     * Calculer la durée automatiquement
     */
    public function calculateDuration()
    {
        if ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            return $start->diffInMinutes($end);
        }
        return null;
    }

    /**
     * Vérifier si la permission est pour aujourd'hui
     */
    public function isToday()
    {
        return $this->date->isToday();
    }

    /**
     * Vérifier si la permission est future
     */
    public function isFuture()
    {
        return $this->date->isFuture();
    }

    /**
     * Vérifier si la permission est passée
     */
    public function isPast()
    {
        return $this->date->isPast();
    }

    /**
     * Approuver la permission
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
        ]);
    }

    /**
     * Rejeter la permission
     */
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Mettre en attente la permission
     */
    public function setPending()
    {
        $this->update([
            'status' => 'pending',
        ]);
    }
}
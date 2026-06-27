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
        'start_time',
        'end_time',
        'raison',
        'status',
        'duration_minutes'
    ];

    protected $casts = [
        'date' => 'date',
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
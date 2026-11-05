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
        'status',
        'deactivated_at',
        'activated_at',
    ];

    protected $casts = [
    'first_name' => 'encrypted',
    'area_name' => 'encrypted',
    'dept_name' => 'encrypted',
    'deactivated_at' => 'date',
    'activated_at' => 'date',
];


    /**
     * Employés actifs uniquement. À utiliser dans toute liste, rapport,
     * statistique ou synchronisation : un employé désactivé
     * (status = 'inactive') doit y être masqué.
     */
    public function scopeActive($query)
    {
        return $query->where($query->getModel()->getTable() . '.status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Employés à inclure dans un rapport portant sur la période commençant à
     * $periodStart : les actifs, plus les employés désactivés dont la date de
     * désactivation (dernier jour pris en compte) tombe à partir du début de la
     * période. Les jours après la désactivation sont ensuite laissés vides par
     * AttendanceSupport::isOutOfService().
     */
    public function scopeActiveDuring($query, $periodStart)
    {
        $table = $query->getModel()->getTable();
        $start = \Carbon\Carbon::parse($periodStart)->format('Y-m-d');

        return $query->where(function ($q) use ($table, $start) {
            $q->where($table . '.status', 'active')
                ->orWhere(function ($q) use ($table, $start) {
                    $q->where($table . '.status', 'inactive')
                        ->whereNotNull($table . '.deactivated_at')
                        ->where($table . '.deactivated_at', '>=', $start);
                });
        });
    }

    /**
     * Actifs + employés désactivés avec une date de désactivation : pour les
     * listes de choix des pages de rapports (on doit pouvoir sélectionner un
     * employé désactivé pour consulter son historique).
     */
    public function scopeReportable($query)
    {
        $table = $query->getModel()->getTable();

        return $query->where(function ($q) use ($table) {
            $q->where($table . '.status', 'active')
                ->orWhereNotNull($table . '.deactivated_at');
        });
    }

    public function inactivePeriods(): HasMany
    {
        return $this->hasMany(EmployeeInactivePeriod::class)->orderBy('start_date');
    }

    /**
     * Active ou désactive l'employé à la date donnée et tient à jour
     * l'historique des périodes de désactivation.
     *
     *  - désactivation le D : D est le dernier jour pris en compte ; la période
     *    désactivée démarre le lendemain.
     *  - activation le A : A est le premier jour pris en compte ; la période
     *    désactivée en cours se termine la veille.
     * Rappeler avec le même statut corrige simplement la date.
     */
    public function applyStatus(string $status, $date, ?int $userId = null): void
    {
        $date = \Carbon\Carbon::parse($date)->startOfDay();

        \Illuminate\Support\Facades\DB::transaction(function () use ($status, $date, $userId) {
            $open = $this->inactivePeriods()->whereNull('end_date')->first();

            if ($status === 'inactive') {
                $start = $date->copy()->addDay();

                if ($open) {
                    $open->update(['start_date' => $start->toDateString()]);
                } else {
                    $this->inactivePeriods()->create([
                        'start_date' => $start->toDateString(),
                        'created_by' => $userId,
                    ]);
                }

                $this->status = 'inactive';
                $this->deactivated_at = $date->toDateString();
            } else {
                if ($open) {
                    $end = $date->copy()->subDay();

                    if ($end->lt(\Carbon\Carbon::parse($open->start_date))) {
                        // Réactivé avant même le début de la période : rien à garder.
                        $open->delete();
                    } else {
                        $open->update(['end_date' => $end->toDateString()]);
                    }
                }

                $this->status = 'active';
                $this->activated_at = $date->toDateString();
            }

            $this->save();
            $this->unsetRelation('inactivePeriods');
        });
    }

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

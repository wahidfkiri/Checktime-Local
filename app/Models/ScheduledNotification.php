<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    protected $fillable = [
        'command',
        'label',
        'description',
        'supports_recipients',
        'is_active',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'cron_expression',
        'run_at',
        'recipients',
        'last_run_at',
        'last_status',
    ];

    protected $casts = [
        'supports_recipients' => 'boolean',
        'is_active'           => 'boolean',
        'recipients'          => 'array',
        'day_of_week'         => 'integer',
        'day_of_month'        => 'integer',
        'run_at'              => 'datetime',
        'last_run_at'         => 'datetime',
    ];

    /**
     * Construit l'expression cron correspondant à la configuration de la tâche.
     */
    public function cronExpression(): string
    {
        if ($this->frequency === 'custom' && !empty($this->cron_expression)) {
            return trim($this->cron_expression);
        }

        // Date+heure précise (exécution unique).
        if ($this->frequency === 'once') {
            if (!$this->run_at) {
                return '0 0 31 2 *'; // 31 février : ne se déclenche jamais
            }
            return sprintf('%d %d %d %d *', $this->run_at->minute, $this->run_at->hour, $this->run_at->day, $this->run_at->month);
        }

        // Extraction heure/minute depuis "HH:MM"
        [$hour, $minute] = array_pad(explode(':', $this->time ?: '09:00'), 2, '00');
        $hour   = (int) $hour;
        $minute = (int) $minute;

        switch ($this->frequency) {
            case 'daily':
                return "{$minute} {$hour} * * *";

            case 'monthly':
                $dom = $this->day_of_month ?: 1;
                return "{$minute} {$hour} {$dom} * *";

            case 'weekly':
            default:
                // ISO: 1=Lundi .. 7=Dimanche  ->  cron: 0/7=Dimanche, 1..6=Lun..Sam
                $iso  = $this->day_of_week ?: 1;
                $cron = $iso === 7 ? 0 : $iso;
                return "{$minute} {$hour} * * {$cron}";
        }
    }

    /**
     * Libellé lisible de la planification (pour l'UI).
     */
    public function scheduleLabel(): string
    {
        if ($this->frequency === 'custom') {
            return 'Cron: ' . ($this->cron_expression ?: '—');
        }

        if ($this->frequency === 'once') {
            return $this->run_at
                ? 'Le ' . $this->run_at->format('d/m/Y à H:i') . ' (une fois)'
                : 'Date non définie';
        }

        $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

        switch ($this->frequency) {
            case 'daily':
                return "Tous les jours à {$this->time}";
            case 'monthly':
                return "Le {$this->day_of_month} de chaque mois à {$this->time}";
            case 'weekly':
            default:
                $day = $days[$this->day_of_week] ?? 'Lundi';
                return "Chaque {$day} à {$this->time}";
        }
    }
}

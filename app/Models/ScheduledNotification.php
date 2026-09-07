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
                // Un mois n'a pas toujours 29, 30 ou 31 jours : un jour fixe
                // à 29+ ne se déclencherait jamais certains mois (ex. jamais
                // en février pour 31). On élargit alors le champ jour à
                // 28-31 ; c'est isLastConfiguredDayDue() (voir Kernel) qui
                // affine ensuite en ne retenant que le dernier jour réel du
                // mois si $dom dépasse le nombre de jours du mois courant.
                $domField = $dom >= 29 ? '28-31' : (string) $dom;
                return "{$minute} {$hour} {$domField} * *";

            case 'weekly':
            default:
                // ISO: 1=Lundi .. 7=Dimanche  ->  cron: 0/7=Dimanche, 1..6=Lun..Sam
                $iso  = $this->day_of_week ?: 1;
                $cron = $iso === 7 ? 0 : $iso;
                return "{$minute} {$hour} * * {$cron}";
        }
    }

    /**
     * Pour une tâche mensuelle : le jour configuré est-il "dû" aujourd'hui ?
     * Un jour configuré au-delà du nombre de jours du mois courant (ex. 31
     * un mois de 30 jours, ou 29/30/31 en février) est ramené au dernier
     * jour réel du mois, plutôt que de ne jamais se déclencher ce mois-là.
     */
    public function isMonthlyDayDueToday(\Carbon\Carbon $now): bool
    {
        $configured   = $this->day_of_month ?: 1;
        $effectiveDom = min($configured, $now->daysInMonth);

        return $now->day === $effectiveDom;
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
                $dom = $this->day_of_month ?: 1;
                return $dom >= 29
                    ? "Le dernier jour de chaque mois à {$this->time}"
                    : "Le {$dom} de chaque mois à {$this->time}";
            case 'weekly':
            default:
                $day = $days[$this->day_of_week] ?? 'Lundi';
                return "Chaque {$day} à {$this->time}";
        }
    }
}

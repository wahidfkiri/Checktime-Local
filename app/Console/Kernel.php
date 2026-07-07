<?php

namespace App\Console;

use App\Jobs\SyncZonesJob;
use App\Models\ScheduledNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Synchronisation automatique toutes les heures
        $schedule->job(new SyncZonesJob(), 'zones', 'database')->hourly();

        // Synchronisation forcée tous les jours à 2h du matin
        $schedule->job(new SyncZonesJob(null, true), 'zones-high', 'database')->dailyAt('02:00');

        // Nettoyage hebdomadaire
        $schedule->command('zones:cleanup')->weekly();

        // Surveiller la queue
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();

        // Tâches de notification/rapports configurées depuis le profil (base de données).
        $this->scheduleNotificationJobs($schedule);
    }

    /**
     * Planifie dynamiquement les tâches de rapport activées dans l'interface.
     */
    protected function scheduleNotificationJobs(Schedule $schedule): void
    {
        try {
            if (!Schema::hasTable('scheduled_notifications')) {
                return;
            }

            $jobs = ScheduledNotification::where('is_active', true)->get();

            foreach ($jobs as $job) {
                // Date précise sans date définie : rien à planifier.
                if ($job->frequency === 'once' && !$job->run_at) {
                    continue;
                }

                $event = $schedule->command($job->command)
                    ->cron($job->cronExpression())
                    ->timezone('Africa/Casablanca')
                    ->withoutOverlapping();

                // Exécution unique : ne se déclenche que l'année prévue et une seule fois,
                // puis la tâche est désactivée automatiquement.
                if ($job->frequency === 'once') {
                    $scheduledYear = $job->run_at->year;
                    $alreadyRun    = !is_null($job->last_run_at);
                    $jobId         = $job->id;

                    $event->when(function () use ($scheduledYear, $alreadyRun) {
                        return !$alreadyRun && now('Africa/Casablanca')->year === $scheduledYear;
                    });

                    $event->after(function () use ($jobId) {
                        ScheduledNotification::where('id', $jobId)->update([
                            'last_run_at' => now(),
                            'is_active'   => false,
                            'last_status' => 'Exécutée (date unique)',
                        ]);
                    });
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Planification des tâches de notification impossible: ' . $e->getMessage());
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
    
//     protected $commands = [
//     \App\Console\Commands\ClearExpiredPasswordTokens::class,
//     \App\Console\Commands\SyncAttendanceCommand::class,
// ];
}

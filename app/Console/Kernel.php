<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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

        $schedule->command('attendance:send-weekly-reports')
        ->weeklyOn(Schedule::SATURDAY, '9:00')
        ->timezone('Africa/Casablanca'); 

         $schedule->command('reports:send-monthly-rh')
        ->monthlyOn(31, '9:00') // S'exécutera le 31 à 17h
        ->timezone('Africa/Casablanca');
        
         $schedule->command('attendance:send-weekly-sms')
        ->weeklyOn(Schedule::SATURDAY, '9:00')
        ->timezone('Africa/Casablanca'); 
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceSyncService;
use Carbon\Carbon;

class SyncAttendanceCommand extends Command
{
    protected $signature = 'attendance:sync 
                            {--date= : Date spécifique (format: Y-m-d)}
                            {--days-back=7 : Nombre de jours à synchroniser en arrière}
                            {--force : Forcer la synchronisation même si déjà faite}';
    
    protected $description = 'Synchroniser les pointages depuis l\'API externe';
    
    protected $attendanceService;
    
    public function __construct(AttendanceSyncService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }
    
    public function handle()
    {
        $this->info('Début de la synchronisation des pointages...');
        
        try {
            $this->attendanceService = app()->make(AttendanceSyncService::class);
            
            $specificDate = $this->option('date');
            $daysBack = (int) $this->option('days-back');
            
            if ($specificDate) {
                $date = Carbon::parse($specificDate);
                $this->info("Synchronisation pour la date: {$date->format('Y-m-d')}");
                
                $this->attendanceService->syncForDate($date);
                $this->info("Synchronisation terminée");
            } else {
                $this->info("Synchronisation des {$daysBack} derniers jours...");
                
                $this->attendanceService->syncAll($daysBack);
                $this->info("Synchronisation terminée");
            }
            
            $this->info('Synchronisation complétée avec succès!');
            
        } catch (\Exception $e) {
            $this->error('Erreur: ' . $e->getMessage());
            \Log::error('Erreur dans attendance:sync: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Facades\Sms;

class CheckSmsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:status {message_id : ID du message à vérifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier le statut d\'un SMS envoyé';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $messageId = $this->argument('message_id');
        
        $this->info('🔍 Vérification du statut du SMS...');
        $this->line('Message ID: ' . $messageId);
        $this->line('');
        
        $status = Sms::checkStatus($messageId);
        
        if ($status['success']) {
            $statusIcon = $status['delivered'] ? '✅' : '⏳';
            $statusText = $status['delivered'] ? 'LIVRÉ' : 'EN ATTENTE';
            
            $this->info($statusIcon . ' Statut: ' . $statusText);
            $this->line('Statut détaillé: ' . $status['status']);
            
            // Afficher les données brutes si verbose
            if ($this->option('verbose')) {
                $this->line('');
                $this->info('📋 Réponse complète:');
                $this->line(json_encode($status['raw_response'] ?? [], JSON_PRETTY_PRINT));
            }
        } else {
            $this->error('❌ Impossible de vérifier le statut: ' . $status['error']);
            return 1;
        }
        
        return 0;
    }
}
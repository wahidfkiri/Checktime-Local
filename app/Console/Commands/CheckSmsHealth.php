<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Facades\Sms;

class CheckSmsHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier la santé du service SMS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Vérification de la santé du service SMS...');
        
        $health = Sms::healthCheck();
        
        if ($health['healthy']) {
            $this->info('✅ Service SMS en bonne santé');
        } else {
            $this->error('❌ Problèmes détectés avec le service SMS');
        }
        
        $this->line('');
        
        // Afficher le résumé
        $this->table(
            ['Composant', 'Statut', 'Détails'],
            [
                [
                    'Connectivité API',
                    $health['ping']['success'] ? '✅ OK' : '❌ Échec',
                    $health['ping']['message'] ?? 'N/A'
                ],
                [
                    'Vérification solde',
                    $health['balance']['success'] ? '✅ OK' : '❌ Échec',
                    $health['balance']['balance_formatted'] ?? $health['balance']['error'] ?? 'N/A'
                ],
                [
                    'Temps de réponse',
                    '⚡ ' . $health['response_time_ms'] . 'ms',
                    $health['response_time_ms'] < 1000 ? 'Rapide' : 'Lent'
                ],
                [
                    'Clé API configurée',
                    !empty(config('sms.fastway.api_key')) ? '✅ Oui' : '❌ Non',
                    'Longueur: ' . strlen(config('sms.fastway.api_key'))
                ]
            ]
        );
        
        // Afficher les détails si demandé
        if ($this->option('verbose')) {
            $this->line('');
            $this->info('📋 Détails du rapport de santé:');
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
        }
        
        return $health['healthy'] ? 0 : 1;
    }
}
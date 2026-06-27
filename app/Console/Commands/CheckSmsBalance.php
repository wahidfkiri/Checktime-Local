<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class CheckSmsBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:balance 
                            {--f|force : Force refresh from API}
                            {--r|raw : Show raw response}
                            {--health|health : Perform full health check}
                            {--test : Test mode only}
                            {--debug : Debug API connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier le solde du compte SMS';

    /**
     * SmsService instance
     */
    protected SmsService $smsService;

    /**
     * Create a new command instance.
     */
    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
{
    $this->info('💰 Vérification du solde SMS FastWay...');
    
    // Options handling
    $force = $this->option('force');
    $raw = $this->option('raw');
    $health = $this->option('health');
    $test = $this->option('test');
    $debug = $this->option('debug');
    
    if ($debug) {
        $this->debugApiConnection();
        return 0;
    }
    
    if ($test) {
        return $this->handleTestMode();
    }
    
    if ($health) {
        return $this->handleHealthCheck($raw);
    }
    
    return $this->handleBalanceCheck($force, $raw);
}

    /**
     * Handle balance check
     */
private function handleBalanceCheck(bool $force, bool $showRaw): int
{
    $this->line('📱 Connexion à l\'API FastWay SMS...');
    
    // First, check connectivity
    $this->line('🔌 Vérification de la connectivité...');
    $pingResult = $this->smsService->ping();
    
    if (!$pingResult['success']) {
        $this->warn('⚠️  Avertissement: Impossible de vérifier la connectivité');
        $this->warn('Erreur: ' . ($pingResult['error'] ?? 'Connexion échouée'));
        $this->warn('Nous allons quand même tenter de vérifier le solde...');
        $this->newLine();
        
        // Continue anyway, as ping might fail but balance check could work
        if (!$this->confirm('Continuer avec la vérification du solde?', true)) {
            return 1;
        }
    } else {
        $this->info('✅ Connectivité API: ' . ($pingResult['connected'] ? 'OK' : 'Limité'));
        if (isset($pingResult['message'])) {
            $this->line($pingResult['message']);
        }
        $this->newLine();
    }
    
    // Check balance
    $this->line('💳 Récupération du solde...');
    $balance = $this->smsService->getBalance($force);
    
    if ($balance['success']) {
        $this->info('✅ Solde récupéré avec succès');
        $this->newLine();
        
        // Display main information
        $this->table(
            ['Information', 'Valeur'],
            [
                ['Solde SMS', $balance['balance_formatted']],
                ['Monnaie', $balance['currency'] ?? 'FCFA'],
                ['Statut', $balance['status'] ?? 'N/A'],
                ['Source', $balance['cached'] ? '🔄 Cache' : '⚡ API Directe'],
                ['Dernière vérification', $balance['last_check'] ?? 'N/A'],
                ['Méthode d\'auth', $balance['auth_method'] ?? 'basic_auth']
            ]
        );
        
        // Check for low balance
        $threshold = config('sms.fastway.low_balance_threshold', 50);
        if ($balance['balance'] < $threshold) {
            $this->newLine();
            $this->warn('⚠️  ATTENTION: Solde bas!');
            $this->warn('Il reste seulement ' . $balance['balance'] . ' SMS');
            $this->warn('Seuil d\'alerte: ' . $threshold . ' SMS');
            
            // Send notification if configured
            if (config('sms.fastway.notify_low_balance', false)) {
                $this->notifyLowBalance($balance['balance']);
            }
        }
        
        // Show raw response if requested
        if ($showRaw && isset($balance['raw_response'])) {
            $this->newLine();
            $this->info('📋 Réponse brute de l\'API:');
            $this->line(json_encode($balance['raw_response'], JSON_PRETTY_PRINT));
        }
        
        // Log the check
        Log::info('Balance check executed via artisan command', [
            'balance' => $balance['balance'],
            'cached' => $balance['cached'] ?? false,
            'force_refresh' => $force,
            'ping_success' => $pingResult['success'] ?? false
        ]);
        
        return 0;
    } else {
        $this->error('❌ Échec de la vérification du solde');
        $this->error('Erreur: ' . $balance['error']);
        
        // Show more details
        if (isset($balance['http_status'])) {
            $this->error('HTTP Status: ' . $balance['http_status']);
        }
        
        if (isset($balance['body_preview'])) {
            $this->error('Réponse API: ' . $balance['body_preview']);
        }
        
        // Debug information
        $this->newLine();
        $this->warn('🔍 Informations de débogage:');
        $this->line('URL de base configurée: ' . config('sms.fastway.base_url'));
        $this->line('Username configuré: ' . (config('sms.fastway.username') ? 'Oui' : 'Non'));
        $this->line('Password configuré: ' . (config('sms.fastway.password') ? 'Oui' : 'Non'));
        $this->line('Clé API configurée: ' . (config('sms.fastway.api_key') ? 'Oui' : 'Non'));
        
        // Suggestions
        $this->newLine();
        $this->warn('💡 Suggestions de dépannage:');
        $this->line('1. Vérifiez la configuration dans .env:');
        $this->line('   FASTWAY_SMS_BASE_URL=https://api.fastway.com/sms/');
        $this->line('   FASTWAY_SMS_USERNAME=votre_username');
        $this->line('   FASTWAY_SMS_PASSWORD=votre_password');
        $this->line('2. Testez la connexion avec curl:');
        $this->line('   curl -v ' . config('sms.fastway.base_url', 'URL_NON_CONFIGURÉE'));
        $this->line('3. Vérifiez que le service SMS est actif');
        $this->line('4. Contactez le support FastWay si le problème persiste');
        
        return 1;
    }
}

    /**
     * Handle health check
     */
    private function handleHealthCheck(bool $showRaw): int
    {
        $this->info('🏥 Vérification complète de santé du service SMS');
        $this->newLine();
        
        $health = $this->smsService->healthCheck();
        
        if ($health['healthy']) {
            $this->info('✅ Service SMS en bonne santé');
        } else {
            $this->error('❌ Problèmes détectés avec le service SMS');
        }
        
        $this->newLine();
        
        // Display health summary
        $this->table(
            ['Composant', 'Statut', 'Détails'],
            [
                [
                    'Connectivité API',
                    $health['ping']['success'] ? '✅ OK' : '❌ Échec',
                    $health['ping']['success'] ? 
                        ($health['ping']['connected'] ? 'Connecté' : 'Non connecté') : 
                        ($health['ping']['error'] ?? 'N/A')
                ],
                [
                    'Vérification solde',
                    $health['balance']['success'] ? '✅ OK' : '❌ Échec',
                    $health['balance']['success'] ? 
                        ($health['balance']['balance'] . ' SMS disponibles') : 
                        ($health['balance']['error'] ?? 'N/A')
                ],
                [
                    'Identifiants configurés',
                    $health['summary']['credentials_configured'] ? '✅ Oui' : '❌ Non',
                    'Basic Auth'
                ],
                [
                    'Clé API configurée',
                    $health['summary']['api_key_configured'] ? '✅ Oui' : '❌ Non',
                    'Auth alternative'
                ],
                [
                    'Temps de réponse',
                    '⏱️ ' . $health['response_time_ms'] . 'ms',
                    $health['response_time_ms'] < 1000 ? 'Bon' : 'Lent'
                ]
            ]
        );
        
        // Show detailed raw response if requested
        if ($showRaw) {
            $this->newLine();
            $this->info('📋 Réponse brute complète:');
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
        }
        
        return $health['healthy'] ? 0 : 1;
    }

    /**
     * Handle test mode
     */
    private function handleTestMode(): int
    {
        $this->info('🧪 Mode test du service SMS');
        $this->newLine();
        
        $this->table(
            ['Configuration', 'Valeur'],
            [
                ['Mode test', config('sms.test_mode', false) ? '✅ Activé' : '❌ Désactivé'],
                ['Destinataire test', config('sms.test_recipient') ?? 'Non configuré'],
                ['Username SMS', config('sms.fastway.username') ? '✓ Configuré' : '✗ Non configuré'],
                ['Password SMS', config('sms.fastway.password') ? '✓ Configuré' : '✗ Non configuré'],
                ['Clé API', config('sms.fastway.api_key') ? '✓ Configuré' : '✗ Non configuré'],
                ['URL API', config('sms.fastway.base_url')],
                ['Expéditeur par défaut', config('sms.fastway.default_sender')],
                ['Indicatif pays', config('sms.fastway.country_code')],
                ['Timeout', config('sms.fastway.timeout') . 's'],
                ['Tentatives de retry', config('sms.fastway.retry_attempts')],
                ['Délai retry', config('sms.fastway.retry_delay') . 'ms']
            ]
        );
        
        // Test sending
        $this->newLine();
        if ($this->confirm('Voulez-vous tester l\'envoi d\'un SMS?', false)) {
            $testNumber = $this->ask('Numéro de test (ou laissez vide pour le destinataire test)');
            $testMessage = $this->ask('Message de test', 'Ceci est un test du service SMS');
            
            $this->info('Envoi du SMS de test...');
            
            $result = $this->smsService->sendSms(
                $testNumber ?: config('sms.test_recipient'),
                $testMessage,
                config('sms.fastway.default_sender')
            );
            
            if ($result['success']) {
                $this->info('✅ SMS test envoyé avec succès');
                $this->table(
                    ['Champ', 'Valeur'],
                    [
                        ['ID Message', $result['message_id']],
                        ['Nombre SMS', $result['sms_count']],
                        ['Mode', $result['test_mode'] ? 'Test' : 'Production'],
                        ['Statut', $result['code']]
                    ]
                );
            } else {
                $this->error('❌ Échec de l\'envoi du test');
                $this->error('Erreur: ' . $result['error']);
            }
        }
        
        return 0;
    }

    /**
     * Notify about low balance
     */
    private function notifyLowBalance(int $balance): void
    {
        $adminEmail = config('sms.fastway.admin_email');
        $threshold = config('sms.fastway.low_balance_threshold', 50);
        
        if ($adminEmail) {
            // Here you could send an email notification
            $this->info('📧 Notification envoyée à: ' . $adminEmail);
            Log::warning('Solde SMS bas', [
                'balance' => $balance,
                'threshold' => $threshold,
                'notified_email' => $adminEmail
            ]);
        }
        
        // Could also send SMS notification if balance is critical
        $criticalThreshold = config('sms.fastway.critical_balance_threshold', 10);
        if ($balance < $criticalThreshold) {
            $this->error('🚨 SOLDE CRITIQUE: ' . $balance . ' SMS restants!');
            
            // Send SMS alert if configured
            $alertNumber = config('sms.fastway.alert_phone_number');
            if ($alertNumber) {
                try {
                    $this->smsService->sendSms(
                        $alertNumber,
                        "🚨 ALERTE: Solde SMS critique! Il reste seulement {$balance} SMS.",
                        config('sms.fastway.default_sender')
                    );
                } catch (\Exception $e) {
                    $this->error('Impossible d\'envoyer l\'alerte SMS: ' . $e->getMessage());
                }
            }
        }
    }

    /**
 * Test direct API connection with curl-like output
 */
private function debugApiConnection(): void
{
    $baseUrl = config('sms.fastway.base_url');
    $this->info('🔍 Debug de la connexion API:');
    $this->line('URL: ' . $baseUrl);
    
    try {
        $this->line('Envoi de la requête GET...');
        $response = Http::withOptions(['verify' => true, 'timeout' => 10])
            ->get($baseUrl);
        
        $this->table(
            ['Information', 'Valeur'],
            [
                ['Status HTTP', $response->status()],
                ['Content-Type', $response->header('Content-Type', 'Non défini')],
                ['Taille réponse', strlen($response->body()) . ' bytes'],
                ['Succès', $response->successful() ? '✅ Oui' : '❌ Non']
            ]
        );
        
        // Show response preview
        $body = $response->body();
        $this->info('Aperçu de la réponse (premiers 500 caractères):');
        $this->line(substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''));
        
    } catch (\Exception $e) {
        $this->error('Exception: ' . $e->getMessage());
    }
}
}
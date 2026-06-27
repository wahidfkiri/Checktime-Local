<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;

class TestSmsService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test 
                            {phone : Numéro de téléphone à tester (ex: 2250101010101)}
                            {--m|message= : Message à envoyer}
                            {--s|sender= : Sender ID à utiliser}
                            {--b|bulk : Envoyer à plusieurs numéros (séparés par des virgules)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le service SMS';

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
    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->option('message') ?? 'Ceci est un test du service SMS CHECKTIME. ' . date('Y-m-d H:i:s');
        $sender = $this->option('sender');
        $bulk = $this->option('bulk');
        
        $this->info('🧪 Test du service SMS FastWay...');
        
        // Vérifier le mode test global
        if (config('sms.test_mode', false)) {
            $this->warn('⚠️  MODE TEST ACTIVÉ - Les SMS ne seront pas envoyés réellement');
            $this->line('Destinataire réel: ' . config('sms.test_recipient', $phone));
        }
        
        $this->line('📞 Destinataire: ' . $phone);
        $this->line('📝 Message: ' . $message);
        $this->line('🏷️  Sender: ' . ($sender ?: config('sms.fastway.default_sender', 'Défaut')));
        $this->line('');
        
        // 1. Vérifier la connectivité
        $this->info('1. Vérification de la connectivité...');
        $ping = $this->smsService->ping();
        
        if ($ping['success']) {
            $this->info('   ✅ Connectivité: ' . ($ping['connected'] ? 'OK' : 'Partielle'));
            if (isset($ping['message'])) {
                $this->line('   ' . $ping['message']);
            }
        } else {
            $this->error('   ❌ Échec connectivité: ' . $ping['error']);
            $this->warn('   Nous allons quand même tenter d\'envoyer le SMS...');
            
            if (!$this->confirm('Continuer malgré l\'échec de connectivité?', false)) {
                return 1;
            }
        }
        
        // 2. Vérifier le solde
        $this->info('2. Vérification du solde...');
        $balance = $this->smsService->getBalance(true); // Force refresh
        
        if ($balance['success']) {
            $this->info('   ✅ Solde: ' . $balance['balance_formatted']);
            
            // Calculer le nombre de SMS nécessaires
            $smsCount = $this->calculateSmsCount($message);
            
            // Pour bulk, multiplier par le nombre de destinataires
            $recipientCount = 1;
            if ($bulk) {
                $recipients = array_filter(array_map('trim', explode(',', $phone)));
                $recipientCount = count($recipients);
                $smsCount *= $recipientCount;
            }
            
            if ($balance['balance'] >= $smsCount) {
                $this->info('   ✅ Solde suffisant pour ' . $smsCount . ' SMS (' . $recipientCount . ' destinataire(s))');
            } else {
                $this->error('   ❌ Solde insuffisant: besoin de ' . $smsCount . ' SMS, solde: ' . $balance['balance']);
                return 1;
            }
        } else {
            $this->error('   ❌ Échec vérification solde: ' . $balance['error']);
            
            // En mode test, on peut continuer
            if (config('sms.test_mode', false)) {
                $this->warn('   ⚠️  Mode test: continuation malgré l\'erreur de solde');
            } else {
                if (!$this->confirm('Continuer malgré l\'erreur de solde?', false)) {
                    return 1;
                }
            }
        }
        
        // 3. Envoyer le SMS
        $this->info('3. Envoi du SMS...');
        
        if ($bulk) {
            return $this->sendBulkSms($phone, $message, $sender);
        } else {
            return $this->sendSingleSms($phone, $message, $sender);
        }
    }
    
    /**
     * Envoyer un SMS unique
     */
    private function sendSingleSms($phone, $message, $sender)
    {
        $result = $this->smsService->sendSms($phone, $message, $sender);
        
        if ($result['success']) {
            $this->info('   ✅ SMS envoyé avec succès!');
            
            $rows = [
                ['Message ID', $result['message_id'] ?? 'N/A'],
                ['Nombre de SMS', $result['sms_count'] ?? 1],
                ['Statut', $result['code'] ?? 'SUCCESS']
            ];
            
            if (isset($result['test_mode']) && $result['test_mode']) {
                $rows[] = ['Mode', 'TEST (Non envoyé réellement)'];
            }
            
            if (isset($result['operator'])) {
                $rows[] = ['Opérateur', $result['operator']];
            }
            
            if (isset($result['country'])) {
                $rows[] = ['Pays', $result['country']];
            }
            
            $this->table(['Champ', 'Valeur'], $rows);
            
            // Si on a un message_id, on peut vérifier le statut
            if (!empty($result['message_id']) && $result['message_id'] !== 'TEST-' . time()) {
                $this->info('   ℹ️  Message ID: ' . $result['message_id']);
                
                if ($this->confirm('Voulez-vous vérifier le statut maintenant?', false)) {
                    $this->checkMessageStatus($result['message_id']);
                }
            }
            
            return 0;
        } else {
            $this->error('   ❌ Échec envoi SMS: ' . $result['error']);
            
            $this->table(
                ['Détails', 'Valeur'],
                [
                    ['Code erreur', $result['code'] ?? 'UNKNOWN'],
                    ['Numéro', $phone],
                    ['Taille message', strlen($message) . ' caractères'],
                    ['Nombre SMS estimé', $this->calculateSmsCount($message)]
                ]
            );
            
            // Suggestions de dépannage
            if (isset($result['code'])) {
                $this->warn('💡 Suggestions:');
                
                switch ($result['code']) {
                    case 'AUTHENTICATION_FAILED':
                        $this->line('   - Vérifiez vos identifiants dans .env');
                        $this->line('   - Vérifiez que la clé API est correcte');
                        break;
                    case 'INSUFFICIENT_BALANCE':
                        $this->line('   - Rechargez votre compte FastWay SMS');
                        break;
                    case 'INVALID_PHONE':
                        $this->line('   - Vérifiez le format du numéro');
                        $this->line('   - Format attendu: 225XXXXXXXXX');
                        break;
                    default:
                        $this->line('   - Contactez le support FastWay SMS');
                }
            }
            
            return 1;
        }
    }
    
    /**
     * Envoyer des SMS en masse
     */
    private function sendBulkSms($phones, $message, $sender)
    {
        $recipients = array_filter(array_map('trim', explode(',', $phones)));
        
        $this->info('   📨 Envoi en masse à ' . count($recipients) . ' destinataire(s)');
        
        $result = $this->smsService->sendBulkSms($recipients, $message, $sender);
        
        if ($result['success']) {
            $this->info('   ✅ Tous les SMS ont été envoyés avec succès!');
        } else {
            $this->warn('   ⚠️  ' . $result['error_count'] . ' erreur(s) sur ' . $result['total'] . ' envoi(s)');
        }
        
        $this->table(
            ['Statistiques', 'Valeur'],
            [
                ['Total destinataires', $result['total']],
                ['Succès', $result['success_count']],
                ['Échecs', $result['error_count']],
                ['Coût total (SMS)', $result['total_cost_sms']],
                ['Coût estimé (FCFA)', $result['estimated_cost_fcfa'] ?? 'N/A']
            ]
        );
        
        // Afficher les détails des erreurs
        if ($result['error_count'] > 0) {
            $this->warn('📋 Détails des erreurs:');
            
            $errorRows = [];
            foreach ($result['results'] as $item) {
                if (!$item['success']) {
                    $errorRows[] = [
                        $item['recipient'],
                        $item['error'] ?? 'Erreur inconnue',
                        $item['code'] ?? 'N/A'
                    ];
                }
            }
            
            $this->table(['Destinataire', 'Erreur', 'Code'], $errorRows);
        }
        
        return $result['error_count'] === 0 ? 0 : 1;
    }
    
    /**
     * Vérifier le statut d'un message
     */
    private function checkMessageStatus($messageId)
    {
        $this->info('   🔍 Vérification du statut pour Message ID: ' . $messageId);
        
        $status = $this->smsService->checkStatus($messageId);
        
        if ($status['success']) {
            $this->table(
                ['Statut', 'Valeur'],
                [
                    ['Message ID', $messageId],
                    ['Statut', $status['status']],
                    ['Livré', $status['delivered'] ? '✅ Oui' : '⏳ Non']
                ]
            );
        } else {
            $this->error('   ❌ Impossible de vérifier le statut: ' . $status['error']);
        }
    }
    
    /**
     * Calculer le nombre de SMS
     */
    private function calculateSmsCount($message)
    {
        $length = strlen($message);
        
        // Simple calcul: 1 SMS = 160 caractères sans accent
        if ($length <= 160) {
            return 1;
        }
        
        // Pour les SMS concaténés: 153 caractères par SMS après le premier
        $remaining = $length - 153;
        return 1 + ceil($remaining / 153);
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsService;

class DebugSms extends Command
{
    protected $signature = 'sms:debug';
    protected $description = 'Déboguer le service SMS';

    public function handle(SmsService $smsService)
    {
        $this->info('🔧 Débogage du service SMS...');
        $this->line('');

        // 1. Afficher la configuration
        $this->info('1. Configuration SMS:');
        $config = config('sms.fastway');
        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['API Key', substr($config['api_key'], 0, 20) . '...'],
                ['Base URL', $config['base_url']],
                ['Default Sender', $config['default_sender']],
                ['Country Code', $config['country_code']],
                ['Timeout', $config['timeout']],
                ['Test Mode', config('sms.test_mode') ? '✅ Oui' : '❌ Non'],
            ]
        );

        // 2. Tester la connexion (PING)
        $this->info('2. Test de connexion (PING)...');
        try {
            $response = $smsService->ping();
            $this->info('   URL: ' . str_replace('/sms/', '/', $config['base_url']) . 'ping');
            
            if ($response['success']) {
                $this->info('   ✅ Connectivité OK');
                $this->info('   Réponse: ' . ($response['data'] ?? 'PONG'));
                $this->info('   Serveur: ' . ($response['server'] ?? 'FASTWAY'));
            } else {
                $this->error('   ❌ Échec de connexion: ' . $response['error']);
                $this->info('   Status HTTP: ' . ($response['http_status'] ?? 'N/A'));
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Exception: ' . $e->getMessage());
        }

        $this->line('');

        // 3. Tester l'authentification avec une requête simple
        $this->info('3. Test d\'authentification...');
        $this->testAuthentication($smsService);

        $this->line('');

        // 4. Tester la récupération du solde
        $this->info('4. Test de récupération du solde...');
        $balance = $smsService->checkBalance();
        
        if ($balance['success']) {
            $this->info('   ✅ Solde récupéré avec succès');
            $this->info('   Solde: ' . $balance['balance_formatted']);
            $this->info('   Status: ' . $balance['status']);
        } else {
            $this->error('   ❌ Échec récupération solde: ' . $balance['error']);
            $this->info('   Code: ' . $balance['code']);
            $this->info('   HTTP Status: ' . ($balance['http_status'] ?? 'N/A'));
            
            // Afficher la réponse brute si disponible
            if (isset($balance['raw_response'])) {
                $this->info('   Réponse brute:');
                $this->line(json_encode($balance['raw_response'], JSON_PRETTY_PRINT));
            }
        }

        $this->line('');
        $this->info('✅ Débogage terminé');
    }

    private function testAuthentication($smsService)
    {
        $config = config('sms.fastway');
        $url = $config['base_url'] . 'balance';
        
        $this->info('   URL Balance: ' . $url);
        $this->info('   API Key: ' . substr($config['api_key'], 0, 30) . '...');
        
        // Test manuel avec Guzzle
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'verify' => false, // Désactiver temporairement la vérification SSL pour debug
            ]);
            
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($config['api_key'] . ':'),
                    'Accept' => 'application/json',
                ]
            ]);
            
            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            $this->info('   ✅ Requête HTTP réussie');
            $this->info('   Status HTTP: ' . $status);
            $this->info('   Réponse: ' . substr($body, 0, 200) . '...');
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->error('   ❌ Erreur Guzzle: ' . $e->getMessage());
            
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $this->error('   Status: ' . $response->getStatusCode());
                $this->error('   Body: ' . $response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Exception: ' . $e->getMessage());
        }
    }
}
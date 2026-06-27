<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SmsService
{
    protected $config;
    protected $username;
    protected $password;
    protected $apiKey;
    protected $baseUrl;
    protected $defaultSender;
    protected $countryCode;
    protected $timeout;
    protected $retryAttempts;
    protected $retryDelay;
    protected $testMode;
    protected $testRecipient;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->config = config('sms.fastway');
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->apiKey = $this->config['api_key'];
        $this->baseUrl = rtrim($this->config['base_url'], '/') . '/';
        $this->defaultSender = $this->config['default_sender'];
        $this->countryCode = $this->config['country_code'];
        $this->timeout = $this->config['timeout'];
        $this->retryAttempts = $this->config['retry_attempts'];
        $this->retryDelay = $this->config['retry_delay'];
        $this->testMode = config('sms.test_mode', false);
        $this->testRecipient = config('sms.test_recipient');
    }

    /**
     * Envoyer un SMS
     */
    public function sendSms($to, $message, $sender = null, $options = [])
    {
        try {
            // Mode test
            if ($this->testMode) {
                return $this->sendTestSms($to, $message, $sender, $options);
            }

            // Valider les paramètres
            $validation = $this->validateParameters($to, $message);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['error'], $to, $message);
            }

            // Vérifier les identifiants
            if (!$this->hasValidCredentials()) {
                return [
                    'success' => false,
                    'error' => 'Identifiants FastWay non configurés. Configurez FASTWAY_SMS_USERNAME et FASTWAY_SMS_PASSWORD dans .env',
                    'code' => 'CONFIGURATION_ERROR'
                ];
            }

            // Préparer les paramètres
            $params = $this->prepareParameters($to, $message, $sender, $options);

            // Envoyer avec retry
            return $this->sendWithRetry($params);

        } catch (\Exception $e) {
            Log::error('Erreur SMS service: ' . $e->getMessage(), [
                'to' => $to,
                'message' => substr($message, 0, 50) . '...',
                'error' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur interne du service SMS',
                'code' => 'INTERNAL_ERROR',
                'message_id' => null,
                'sms_count' => 0
            ];
        }
    }


/**
 * Vérifier la connectivité (PING)
 */
public function ping()
{
    try {
        // Try different endpoints for connectivity check
        $pingEndpoints = [
            $this->baseUrl . 'ping',
            str_replace('/sms/', '/', $this->baseUrl) . 'ping',
            $this->baseUrl,
            str_replace('/sms/', '/', $this->baseUrl)
        ];
        
        foreach ($pingEndpoints as $url) {
            Log::debug('Trying ping endpoint', ['url' => $url]);
            
            try {
                $response = Http::withOptions([
                        'verify' => true,
                        'timeout' => 10,
                    ])
                    ->get($url);
                
                Log::debug('Ping response', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body_start' => substr($response->body(), 0, 100)
                ]);
                
                // If we get any response (even non-200), the endpoint exists
                if ($response->status() < 500) {
                    // Try to parse as JSON
                    $body = $response->body();
                    
                    if ($this->isJson($body)) {
                        $data = $response->json();
                        
                        Log::info('SMS API connectivity established', [
                            'endpoint' => $url,
                            'status' => $response->status(),
                            'response' => $data
                        ]);
                        
                        return [
                            'success' => true,
                            'connected' => true,
                            'data' => $data,
                            'http_status' => $response->status(),
                            'endpoint' => $url,
                            'message' => 'Connectivité établie avec FASTWAY SMS'
                        ];
                    } else {
                        // Not JSON, but endpoint responded
                        Log::info('SMS API responded (non-JSON)', [
                            'endpoint' => $url,
                            'status' => $response->status(),
                            'content_type' => $response->header('Content-Type', 'unknown')
                        ]);
                        
                        return [
                            'success' => true,
                            'connected' => true,
                            'http_status' => $response->status(),
                            'endpoint' => $url,
                            'content_type' => $response->header('Content-Type', 'unknown'),
                            'message' => 'Endpoint répond mais ne retourne pas JSON'
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Ping endpoint failed', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        Log::warning('All ping endpoints failed');
        return [
            'success' => false,
            'connected' => false,
            'error' => 'Aucun endpoint de ping ne répond',
            'message' => 'Impossible d\'établir une connexion avec l\'API SMS'
        ];

    } catch (\Exception $e) {
        Log::error('SMS PING exception', ['error' => $e->getMessage()]);
        
        return [
            'success' => false,
            'connected' => false,
            'error' => 'Exception lors du ping: ' . $e->getMessage(),
            'exception' => get_class($e)
        ];
    }
}

    /**
     * Vérifier le solde du compte
     */
    // In App\Services\SmsService.php, update the checkBalance() method:

/**
 * Vérifier le solde du compte
 */
public function checkBalance()
{
    try {
        // Vérifier les identifiants
        if (!$this->hasValidCredentials()) {
            return [
                'success' => false,
                'error' => 'Identifiants FastWay non configurés',
                'code' => 'CONFIGURATION_ERROR'
            ];
        }

        $url = 'https://fastway-sms.net/api/v1/sms/balance';
        
        Log::debug('SMS Balance Check', [
            'url' => $url,
            'username' => $this->username,
            'base_url' => $this->baseUrl
        ]);

        // First try: x-api-key authentication (if available)
        if (!empty($this->apiKey)) {
            Log::debug('Trying x-api-key authentication first');
            $apiKeyResult = $this->tryApiKeyAuth($url);
            
            if ($apiKeyResult['success']) {
                return $apiKeyResult;
            }
            
            Log::warning('x-api-key auth failed, trying basic auth');
        }

        // Second try: Basic auth
        Log::debug('Trying basic auth authentication');
        $basicAuthResult = $this->tryBasicAuth($url);
        
        if ($basicAuthResult['success']) {
            return $basicAuthResult;
        }

        // Third try: Form data authentication (POST with credentials)
        Log::debug('Trying form data authentication');
        return $this->tryFormDataAuth($url);

    } catch (\Exception $e) {
        Log::error('SMS Balance exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'exception' => get_class($e)
        ];
    }
}

/**
 * Essayer l'authentification Basic Auth
 */
private function tryBasicAuth($url)
{
    try {
        $authHeader = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        
        $response = Http::withOptions([
                'verify' => true,
                'timeout' => $this->timeout,
            ])
            ->withHeaders([
                'Authorization' => $authHeader,
                'Accept' => 'application/json',
            ])
            ->get($url);

        $status = $response->status();
        $body = $response->body();
        
        Log::debug('Basic auth response', [
            'status' => $status,
            'body_start' => substr($body, 0, 200),
            'is_json' => $this->isJson($body),
        ]);

        // Check if response is HTML (means auth failed)
        if (str_contains($body, '<!DOCTYPE html>') || str_contains($body, '<html') || !$this->isJson($body)) {
            Log::warning('Basic auth returned HTML instead of JSON');
            return [
                'success' => false,
                'error' => 'Authentification Basic échouée (réponse HTML reçue)',
                'http_status' => $status,
                'auth_method' => 'basic_auth'
            ];
        }

        $data = $response->json();
        
        if ($response->successful() && isset($data['status']) && $data['status']) {
            $balance = $data['balance'] ?? 0;
            
            Log::info('SMS Balance retrieved with basic auth', [
                'balance' => $balance,
                'status' => $data['code'] ?? 'SUCCESS'
            ]);
            
            $this->cacheBalance($balance);
            
            return [
                'success' => true,
                'balance' => $balance,
                'balance_formatted' => number_format($balance, 0, ',', ' ') . ' FCFA',
                'currency' => 'FCFA',
                'status' => $data['code'] ?? 'SUCCESS',
                'message' => $data['description'] ?? 'Solde récupéré avec succès',
                'raw_response' => $data,
                'auth_method' => 'basic_auth'
            ];
        }

        $errorMessage = $data['description'] ?? 'Impossible de récupérer le solde';
        
        Log::error('Basic auth balance check failed', [
            'error' => $errorMessage,
            'status_code' => $status,
        ]);
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'code' => $data['code'] ?? 'BALANCE_CHECK_FAILED',
            'http_status' => $status,
            'auth_method' => 'basic_auth'
        ];

    } catch (\Exception $e) {
        Log::error('Basic auth exception', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => 'Exception avec basic auth: ' . $e->getMessage(),
            'auth_method' => 'basic_auth'
        ];
    }
}

/**
 * Essayer l'authentification par clé API
 */
private function tryApiKeyAuth($url)
{
    try {
        Log::debug('Trying x-api-key authentication', [
            'api_key_length' => strlen($this->apiKey)
        ]);

        $response = Http::withOptions(['verify' => true])
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->get($url);

        $body = $response->body();
        $status = $response->status();
        
        Log::debug('x-api-key auth response', [
            'status' => $status,
            'body_start' => substr($body, 0, 200),
            'is_json' => $this->isJson($body),
        ]);

        // Check if response is HTML
        if (str_contains($body, '<!DOCTYPE html>') || str_contains($body, '<html') || !$this->isJson($body)) {
            Log::warning('x-api-key returned HTML instead of JSON');
            return [
                'success' => false,
                'error' => 'Authentification API key échouée (réponse HTML reçue)',
                'http_status' => $status,
                'auth_method' => 'x-api-key'
            ];
        }
        
        if ($response->successful() && $this->isJson($body)) {
            $data = $response->json();
            
            if (isset($data['status']) && $data['status']) {
                $balance = $data['balance'] ?? 0;
                
                Log::info('SMS Balance retrieved with x-api-key', [
                    'balance' => $balance,
                ]);
                
                $this->cacheBalance($balance);
                
                return [
                    'success' => true,
                    'balance' => $balance,
                    'balance_formatted' => number_format($balance, 0, ',', ' ') . ' FCFA',
                    'currency' => 'FCFA',
                    'status' => $data['code'] ?? 'SUCCESS',
                    'message' => $data['description'] ?? 'Solde récupéré avec succès',
                    'raw_response' => $data,
                    'auth_method' => 'x-api-key'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Échec de l\'authentification par clé API',
            'http_status' => $status,
            'auth_method' => 'x-api-key'
        ];

    } catch (\Exception $e) {
        Log::error('API key auth exception', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => 'Exception avec clé API: ' . $e->getMessage(),
            'auth_method' => 'x-api-key'
        ];
    }
}

/**
 * Essayer l'authentification avec données de formulaire (POST)
 */
private function tryFormDataAuth($url)
{
    try {
        Log::debug('Trying form data authentication (POST)');
        
        $response = Http::withOptions(['verify' => true])
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->post($url, [
                'username' => $this->username,
                'password' => $this->password,
                'api_key' => $this->apiKey,
            ]);

        $body = $response->body();
        $status = $response->status();
        
        Log::debug('Form data auth response', [
            'status' => $status,
            'body_start' => substr($body, 0, 200),
            'is_json' => $this->isJson($body),
        ]);

        if ($response->successful() && $this->isJson($body)) {
            $data = $response->json();
            
            if (isset($data['status']) && $data['status']) {
                $balance = $data['balance'] ?? 0;
                
                Log::info('SMS Balance retrieved with form data auth', [
                    'balance' => $balance,
                ]);
                
                $this->cacheBalance($balance);
                
                return [
                    'success' => true,
                    'balance' => $balance,
                    'balance_formatted' => number_format($balance, 0, ',', ' ') . ' FCFA',
                    'currency' => 'FCFA',
                    'status' => $data['code'] ?? 'SUCCESS',
                    'message' => $data['description'] ?? 'Solde récupéré avec succès',
                    'raw_response' => $data,
                    'auth_method' => 'form_data'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Échec de l\'authentification par formulaire',
            'http_status' => $status,
            'body_preview' => substr($body, 0, 200),
            'auth_method' => 'form_data'
        ];

    } catch (\Exception $e) {
        Log::error('Form data auth exception', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'error' => 'Exception avec authentification formulaire: ' . $e->getMessage(),
            'auth_method' => 'form_data'
        ];
    }
}

/**
 * Cache the balance
 */
private function cacheBalance($balance)
{
    Cache::put('sms_balance', $balance, $this->config['balance_cache_ttl'] ?? 300);
    Cache::put('sms_balance_last_check', now(), $this->config['balance_cache_ttl'] ?? 300);
}

    

    /**
     * Obtenir le solde depuis le cache ou l'API
     */
    public function getBalance($forceRefresh = false)
    {
        // Si force refresh ou cache expiré, appeler l'API
        if ($forceRefresh || !Cache::has('sms_balance')) {
            return $this->checkBalance();
        }
        
        $balance = Cache::get('sms_balance', 0);
        $lastCheck = Cache::get('sms_balance_last_check');
        
        return [
            'success' => true,
            'balance' => $balance,
            'balance_formatted' => number_format($balance, 0, ',', ' ') . ' FCFA',
            'currency' => 'FCFA',
            'cached' => true,
            'last_check' => $lastCheck ? $lastCheck->format('Y-m-d H:i:s') : null,
            'message' => 'Solde récupéré depuis le cache'
        ];
    }

    /**
     * Vérifier si le solde est suffisant
     */
    public function hasSufficientBalance($smsCount = 1, $forceCheck = false)
    {
        $balanceResponse = $this->getBalance($forceCheck);
        
        if (!$balanceResponse['success']) {
            return [
                'success' => false,
                'has_sufficient_balance' => false,
                'error' => $balanceResponse['error'],
                'required_sms' => $smsCount
            ];
        }
        
        $balance = $balanceResponse['balance'];
        $hasSufficient = $balance >= $smsCount;
        
        return [
            'success' => true,
            'has_sufficient_balance' => $hasSufficient,
            'current_balance' => $balance,
            'required_balance' => $smsCount,
            'balance_formatted' => $balanceResponse['balance_formatted'],
            'shortage' => $hasSufficient ? 0 : $smsCount - $balance,
            'cached' => $balanceResponse['cached'] ?? false
        ];
    }

    /**
     * Vérifier la santé du service SMS
     */
    public function healthCheck()
    {
        $startTime = microtime(true);
        
        // 1. Vérifier la connectivité
        $pingResult = $this->ping();
        
        // 2. Vérifier le solde
        $balanceResult = $this->checkBalance();
        
        // 3. Calculer le temps de réponse
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Déterminer l'état global
        $isHealthy = $pingResult['success'] && $balanceResult['success'];
        
        $healthStatus = [
            'healthy' => $isHealthy,
            'response_time_ms' => $responseTime,
            'timestamp' => now()->toIso8601String(),
            'ping' => $pingResult,
            'balance' => $balanceResult,
            'summary' => [
                'connected' => $pingResult['success'],
                'has_balance' => $balanceResult['success'] && ($balanceResult['balance'] ?? 0) > 0,
                'balance_amount' => $balanceResult['balance'] ?? 0,
                'credentials_configured' => $this->hasValidCredentials(),
                'api_key_configured' => !empty($this->apiKey)
            ]
        ];
        
        if ($isHealthy) {
            Log::info('Health check SMS API: OK', [
                'response_time' => $responseTime . 'ms',
                'balance' => $balanceResult['balance'] ?? 0
            ]);
        } else {
            Log::warning('Health check SMS API: Échec', $healthStatus);
        }
        
        return $healthStatus;
    }

    /**
     * Vérifier les identifiants
     */
    private function hasValidCredentials()
    {
        return !empty($this->username) && !empty($this->password);
    }

    /**
     * Envoyer un SMS de test
     */
    private function sendTestSms($to, $message, $sender, $options)
    {
        $testTo = $this->testRecipient ?: $to;
        
        Log::info('📱 SMS TEST MODE - Non envoyé réellement', [
            'original_to' => $to,
            'test_to' => $testTo,
            'sender' => $sender ?: $this->defaultSender,
            'message' => $message,
            'length' => strlen($message),
            'sms_count' => $this->calculateSmsCount($message),
            'options' => $options
        ]);

        return [
            'success' => true,
            'message' => 'SMS envoyé en mode test',
            'code' => 'TEST_MODE',
            'message_id' => 'TEST-' . time() . '-' . rand(1000, 9999),
            'sms_count' => $this->calculateSmsCount($message),
            'test_mode' => true
        ];
    }

    /**
     * Valider les paramètres
     */
    private function validateParameters($to, $message)
    {
        if (empty($to)) {
            return [
                'valid' => false,
                'error' => 'Le numéro de téléphone est requis'
            ];
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $to);
        
        if (strlen($cleanTo) < 8 || strlen($cleanTo) > 15) {
            return [
                'valid' => false,
                'error' => 'Numéro de téléphone invalide'
            ];
        }

        if (empty($message)) {
            return [
                'valid' => false,
                'error' => 'Le message est requis'
            ];
        }

        if (strlen($message) > 480) {
            return [
                'valid' => false,
                'error' => 'Message trop long (max 3 SMS)'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Préparer les paramètres pour l'API
     */
    private function prepareParameters($to, $message, $sender, $options)
    {
        // Nettoyer le numéro
        $cleanTo = preg_replace('/[^0-9]/', '', $to);
        
        // Ajouter l'indicatif pays si nécessaire
        if (!str_starts_with($cleanTo, $this->countryCode)) {
            if (str_starts_with($cleanTo, '0')) {
                $cleanTo = substr($cleanTo, 1);
            }
            $cleanTo = $this->countryCode . $cleanTo;
        }

        // Sender ID
        $senderId = $sender ?: $this->defaultSender;
        $senderId = substr($senderId, 0, 11);

        // Paramètres de base
        $params = [
            'from' => $senderId,
            'to' => $cleanTo,
            'text' => $message,
            'accents' => $this->config['enable_accent'] ? 1 : 0
        ];

        // Options supplémentaires
        if (isset($options['send_at']) && $options['send_at']) {
            try {
                $sendAt = Carbon::parse($options['send_at']);
                $params['sendAt'] = $sendAt->toIso8601String();
            } catch (\Exception $e) {
                Log::warning('Date d\'envoi SMS invalide: ' . $options['send_at']);
            }
        }

        if (isset($options['message_id']) && $options['message_id']) {
            $params['messageId'] = $options['message_id'];
        }

        if (isset($options['dlr_url']) && $options['dlr_url']) {
            $params['dlr_url'] = $options['dlr_url'];
        }

        return $params;
    }

    /**
     * Envoyer avec retry
     */
    private function sendWithRetry($params)
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $response = $this->makeApiRequest($params);
                
                if ($response['success']) {
                    return $response;
                }

                if ($this->isUnrecoverableError($response['code'])) {
                    return $response;
                }

                $lastError = $response;
                $attempt++;

                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000);
                }

            } catch (\Exception $e) {
                $lastError = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'code' => 'REQUEST_FAILED'
                ];
                $attempt++;
                
                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        return array_merge($lastError, [
            'retry_attempts' => $attempt,
            'final_attempt' => true
        ]);
    }

    /**
     * Faire la requête API
     */
    private function makeApiRequest($params)
    {
        $url = 'https://fastway-sms.net/api/v1/sms/send';
        
        // Utiliser Basic auth
        $authHeader = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        
        Log::debug('SMS Send request', [
            'url' => $url,
            'to' => $params['to'],
            'from' => $params['from'],
            'message_length' => strlen($params['text']),
            'auth_method' => 'basic_auth'
        ]);

        $response = Http::withOptions([
                'verify' => true,
                'timeout' => $this->timeout,
                'connect_timeout' => 10,
            ])
            ->withHeaders([
                'Authorization' => $authHeader,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post($url, $params);

        // Log de la requête
        $this->logRequest($params, $response);

        if ($response->failed()) {
            return $this->handleApiError($response, $params);
        }

        return $this->parseApiResponse($response->json(), $params);
    }

    /**
     * Loguer la requête
     */
    private function logRequest($params, $response)
    {
        $logData = [
            'to' => $params['to'],
            'from' => $params['from'],
            'message_length' => strlen($params['text']),
            'sms_count' => $this->calculateSmsCount($params['text']),
            'status_code' => $response->status(),
            'response' => $response->successful() ? 'success' : 'error'
        ];

        if ($response->successful()) {
            Log::info('📱 SMS envoyé avec succès', $logData);
        } else {
            Log::warning('📱 Échec envoi SMS', array_merge($logData, [
                'response_body' => $response->body()
            ]));
        }
    }

    /**
     * Gérer les erreurs API
     */
    private function handleApiError($response, $params)
    {
        $statusCode = $response->status();
        $body = $response->json();

        $errorMap = [
            400 => 'BAD_REQUEST',
            401 => 'AUTHENTICATION_FAILED',
            402 => 'INSUFFICIENT_BALANCE',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            500 => 'INTERNAL_SERVER_ERROR'
        ];

        $errorCode = $errorMap[$statusCode] ?? 'UNKNOWN_ERROR';
        $errorMessage = $body['description'] ?? $response->body();

        return [
            'success' => false,
            'error' => $errorMessage,
            'code' => $errorCode,
            'message_id' => $body['messageId'] ?? null,
            'sms_count' => $this->calculateSmsCount($params['text']),
            'http_status' => $statusCode
        ];
    }

    /**
     * Parser la réponse API
     */
    private function parseApiResponse($data, $params)
    {
        if (empty($data)) {
            return [
                'success' => false,
                'error' => 'Réponse API vide',
                'code' => 'EMPTY_RESPONSE'
            ];
        }

        $success = $data['status'] ?? false;
        
        if ($success) {
            return [
                'success' => true,
                'message' => $data['message'] ?? 'SMS envoyé avec succès',
                'code' => $data['code'] ?? 'SUBMITTED',
                'message_id' => $data['messageId'] ?? null,
                'sms_count' => $data['smsCount'] ?? $this->calculateSmsCount($params['text']),
                'uuid' => $data['uuid'] ?? null,
                'operator' => $data['operator'] ?? null,
                'country' => $data['country'] ?? null,
                'raw_response' => $data
            ];
        } else {
            return [
                'success' => false,
                'error' => $data['description'] ?? 'Erreur inconnue',
                'code' => $data['code'] ?? 'UNKNOWN_ERROR',
                'message_id' => $data['messageId'] ?? null,
                'sms_count' => $this->calculateSmsCount($params['text']),
                'raw_response' => $data
            ];
        }
    }

    /**
     * Vérifier si l'erreur est récupérable
     */
    private function isUnrecoverableError($errorCode)
    {
        $unrecoverableErrors = [
            'INVALID_PHONE',
            'INVALID_SENDERID',
            'AUTHENTICATION_FAILED',
            'INSUFFICIENT_BALANCE',
            'FORBIDDEN',
            'SUSPENDED'
        ];

        return in_array($errorCode, $unrecoverableErrors);
    }

    /**
     * Calculer le nombre de SMS
     */
    private function calculateSmsCount($message)
    {
        $length = strlen($message);
        
        if ($this->config['enable_accent']) {
            if ($length <= 160) return 1;
            $remaining = $length - 153;
            return 1 + ceil($remaining / 153);
        }
        
        if ($length <= 160) return 1;
        $remaining = $length - 153;
        return 1 + ceil($remaining / 153);
    }

    /**
     * Créer une réponse d'erreur
     */
    private function createErrorResponse($error, $to, $message)
    {
        return [
            'success' => false,
            'error' => $error,
            'code' => 'VALIDATION_ERROR',
            'to' => $to,
            'message_preview' => substr($message, 0, 50) . '...',
            'sms_count' => $this->calculateSmsCount($message)
        ];
    }

    /**
     * Vérifier le statut d'un SMS
     */
    public function checkStatus($messageId)
    {
        try {
            $url = $this->baseUrl . 'status/' . $messageId;
            
            $response = Http::withOptions([
                    'verify' => true,
                    'timeout' => $this->timeout,
                ])
                ->withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'UNKNOWN',
                    'delivered' => ($data['status'] ?? '') === 'DELIVERED',
                    'raw_response' => $data
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de récupérer le statut',
                'http_status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut SMS: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un SMS à plusieurs destinataires
     */
    public function sendBulkSms($recipients, $message, $sender = null, $options = [])
    {
        // Vérifier le solde d'abord
        $balanceCheck = $this->hasSufficientBalance(count($recipients), true);
        
        if (!$balanceCheck['has_sufficient_balance']) {
            return [
                'success' => false,
                'error' => 'Solde insuffisant pour envoyer ' . count($recipients) . ' SMS',
                'current_balance' => $balanceCheck['current_balance'],
                'required_balance' => $balanceCheck['required_balance'],
                'shortage' => $balanceCheck['shortage']
            ];
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;
        $totalCost = 0;

        foreach ($recipients as $index => $recipient) {
            $result = $this->sendSms($recipient, $message, $sender, $options);
            
            $results[] = array_merge($result, [
                'recipient' => $recipient,
                'index' => $index + 1
            ]);
            
            if ($result['success']) {
                $successCount++;
                $totalCost += $result['sms_count'] ?? 1;
            } else {
                $errorCount++;
            }

            // Pause courte entre les envois
            usleep(200000);
        }

        return [
            'success' => $errorCount === 0,
            'total' => count($recipients),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_cost_sms' => $totalCost,
            'estimated_cost_fcfa' => $totalCost * ($this->config['sms_cost'] ?? 10),
            'results' => $results
        ];
    }

    /**
     * Formater un numéro de téléphone
     */
    public function formatPhoneNumber($phone, $countryCode = null)
    {
        $countryCode = $countryCode ?: $this->countryCode;
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = substr($cleanPhone, 1);
        }
        
        if (!str_starts_with($cleanPhone, $countryCode)) {
            $cleanPhone = $countryCode . $cleanPhone;
        }
        
        return $cleanPhone;
    }

    /**
     * Vérifier si une chaîne est du JSON valide
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Obtenir les statistiques d'utilisation
     */
    public function getUsageStatistics($days = 30)
    {
        return [
            'success' => true,
            'period_days' => $days,
            'message' => 'Les statistiques détaillées nécessitent un suivi en base de données',
            'suggestions' => [
                'Créez une table sms_logs pour tracer les envois',
                'Stockez message_id, recipient, cost, status, sent_at',
                'Agrégez les données pour les rapports'
            ]
        ];
    }
}
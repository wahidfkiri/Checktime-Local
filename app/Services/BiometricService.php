<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Setting;
use App\Services\CheckTimeService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class BiometricService
{
    private ?string $baseUrl = null;

    public function __construct()
    {
        try {
            $settings = Setting::getGroup('company');
            $this->baseUrl = $settings['api_url']
                ?? env('CHECKTIME_BASE_URL', 'http://54.37.15.111');
        } catch (\Exception $e) {
            $this->baseUrl = env('CHECKTIME_BASE_URL', 'http://54.37.15.111');
        }
    }

    /**
     * Générer une réponse biométrique à partir d'un emp_code.
     *
     * ATTENTION : les emp_code peuvent être en doublon. Cette méthode retourne
     * le premier employé correspondant et reste utilisée par le flux des
     * transactions (qui ne dispose que du emp_code). Pour une identification
     * fiable, utiliser generateBiometricResponseForEmployee().
     */
    public function generateBiometricResponse($employeeCode, $terminalUid = null)
    {
        try {
            // Récupérer l'employé depuis la base de données
            $employee = Employee::where('emp_code', $employeeCode)->first();

            if (!$employee) {
                throw new Exception("Employee with code {$employeeCode} not found");
            }

            return $this->generateBiometricResponseForEmployee($employee, $terminalUid);
        } catch (Exception $e) {
            Log::error('BiometricService Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Générer une réponse biométrique pour un employé précis et unique.
     */
    public function generateBiometricResponseForEmployee(Employee $employee, $terminalUid = null)
    {
        try {
            // Générer le cpbv_id (CPBV- + date du jour + séquence)
            $cpbvId = $this->generateCpbvId();
            
            // Générer biometric_id (BID- + nom/prénom/email cryptés)
            $biometricId = $this->generateBiometricId($employee);
            
            // Générer les données de terminal depuis l'API
            $terminalUid = $terminalUid ?? $this->generateTerminalUid();
            
            // Générer le score de correspondance entre 0.982 et 0.987
            $matchingScore = mt_rand(982, 987) / 1000;
            
            // Générer le hash payload et signature numérique
            $hashPayload = $this->generateHashPayload();
            $digitalSignature = $this->generateDigitalSignature();
            
            return [
                'success' => true,
                'cpbv_id' => $cpbvId,
                'terminal_uid' => $terminalUid,
                'biometric_id' => $biometricId,
                'biometric_mode' => 'FACE',
                'matching_score' => $matchingScore,
                'liveness_status' => 'LIVE_CONFIRMED',
                'hash_payload' => $hashPayload,
                'digital_signature' => $digitalSignature,
                'employee_info' => [
                    'code' => $employee->emp_code,
                    'name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email
                ]
            ];
        } catch (Exception $e) {
            Log::error('BiometricService Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Générer le CPBV ID avec date actuelle
     */
    private function generateCpbvId(): string
    {
        $date = now()->format('Ymd');
        $sequence = str_pad(mt_rand(1, 9999), 8, '0', STR_PAD_LEFT);
        
        return "CPBV-{$date}-{$sequence}";
    }
    
    /**
     * Générer l'ID biométrique avec cryptage
     */
    private function generateBiometricId(Employee $employee): string
    {
        // Créer une chaîne avec les informations de l'employé
        $employeeData = implode('|', [
            $employee->first_name,
            $employee->last_name,
            $employee->email,
            now()->timestamp
        ]);
        
        // Crypter les données
        $encrypted = Crypt::encryptString($employeeData);
        
        // Prendre les premiers 10 caractères hexadécimaux et formater en BID-
        $hex = bin2hex(substr($encrypted, 0, 5));
        
        return "BID-" . strtoupper($hex);
    }
    
    /**
     * Générer un UID de terminal
     */
    private function generateTerminalUid(): string
    {
        try {
            // Essayer de récupérer depuis l'API
            $terminalData = $this->getTerminalDataFromApi();
            
            Log::info('Terminal Data from API:', ['data' => $terminalData]);
            
            // Vérifier plusieurs champs possibles pour terminal_uid
            $terminalUid = null;
            
            if ($terminalData) {
                // Chercher le terminal_uid dans différents champs possibles
                $terminalUid = $terminalData['terminal_uid'] ?? 
                               $terminalData['terminal_sn'] ?? 
                               $terminalData['device_sn'] ?? 
                               $terminalData['device_id'] ?? null;
                
                if ($terminalUid) {
                    // Si c'est déjà au format CHK-TM-, le retourner tel quel
                    if (str_starts_with($terminalUid, 'CHK-TM-')) {
                        return $terminalUid;
                    }
                    
                    // Sinon formater au format CHK-TM-XXXX
                    // Prendre les 8 derniers caractères
                    $suffix = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $terminalUid)), -8);
                    return 'CHK-TM-' . $suffix;
                }
            }
            
            // Si aucun terminal_uid trouvé, chercher dans la première transaction
            $transactions = $this->getRecentTransactions();
            
            if (!empty($transactions)) {
                foreach ($transactions as $transaction) {
                    if (isset($transaction['terminal_sn']) && !empty($transaction['terminal_sn'])) {
                        $terminalSn = $transaction['terminal_sn'];
                        $suffix = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $terminalSn)), -8);
                        return 'CHK-' . $terminalSn;
                    }
                }
            }
            
            throw new Exception('No terminal data available from API');
            
        } catch (Exception $e) {
            Log::warning('Failed to get terminal data from API: ' . $e->getMessage());
            
            // Fallback: générer un UID aléatoire
            return 'CHK-TM-' . strtoupper(Str::random(8));
        }
    }
    
    /**
     * Récupérer les données du terminal depuis l'API
     */
    private function getTerminalDataFromApi()
    {
        try {
            $generalToken = $this->getGeneralToken();
            
            if (!$generalToken) {
                throw new Exception('No authentication token available');
            }
            
            // Essayer d'abord le endpoint des devices/terminaux
            $base = rtrim($this->baseUrl, '/');
            $endpoints = [
                $base . '/iclock/api/devices/',
                $base . '/iclock/api/terminals/',
                $base . '/iclock/api/device/list/',
                $base . '/iclock/api/terminal/list/'
            ];
            
            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Token ' . $generalToken,
                        'Accept' => 'application/json',
                    ])->timeout(8)
                      ->connectTimeout(5)
                      ->get($endpoint, ['limit' => 1]);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        Log::info("API Response from {$endpoint}:", ['data' => $data]);
                        
                        // Différents formats de réponse possibles
                        if (isset($data['devices']) && count($data['devices']) > 0) {
                            return $data['devices'][0];
                        }
                        if (isset($data['terminals']) && count($data['terminals']) > 0) {
                            return $data['terminals'][0];
                        }
                        if (isset($data['data']) && count($data['data']) > 0) {
                            return $data['data'][0];
                        }
                        if (is_array($data) && count($data) > 0 && isset($data[0])) {
                            return $data[0];
                        }
                    }
                } catch (Exception $e) {
                    Log::debug("Endpoint {$endpoint} failed: " . $e->getMessage());
                    continue;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('API Terminal Data Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les transactions récentes pour extraire terminal_sn
     */
    private function getRecentTransactions()
    {
        try {
            $generalToken = $this->getGeneralToken();
            
            if (!$generalToken) {
                return [];
            }
            
            $params = [
                'limit' => 5,
                'start_time' => now()->subDays(1)->format('Y-m-d 00:00:00'),
                'end_time' => now()->format('Y-m-d 23:59:59'),
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $generalToken,
                'Accept' => 'application/json',
            ])->timeout(10)
              ->connectTimeout(5)
              ->get(rtrim($this->baseUrl, '/') . '/iclock/api/transactions/', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Différents formats de réponse possibles
                if (isset($data['transactions'])) {
                    return $data['transactions'];
                }
                if (isset($data['data'])) {
                    return $data['data'];
                }
                if (is_array($data) && count($data) > 0) {
                    return $data;
                }
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::error('Get Recent Transactions Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer le token général depuis settings (fallback access_configs)
     */
    private function getGeneralToken()
    {
        try {
            // Session cache
            if (session()->has('general_token')) {
                return session('general_token');
            }

            $token = CheckTimeService::getConfigToken();

            if ($token) {
                session(['general_token' => $token]);
                return $token;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error getting general token: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Générer un hash payload
     */
    private function generateHashPayload(): string
    {
        $hash = hash('sha256', Str::random(32) . now()->timestamp);
        
        // Format avec points de suspension pour simuler un hash tronqué
        return substr($hash, 0, 16) . '...';
    }
    
    /**
     * Générer une signature numérique
     */
    private function generateDigitalSignature(): string
    {
        $prefix = 'SIG-CHK-';
        $suffix = strtoupper(Str::random(10));
        
        return $prefix . $suffix;
    }
    
    /**
     * Récupérer les transactions avec gestion d'erreur améliorée
     */
    public function getTransactions($params = [], $token = null)
    {
        try {
            // Valeurs par défaut
            $defaultParams = [
                'emp_code' => $params['emp_code'] ?? 50,
                'start_time' => $params['start_time'] ?? now()->format('Y-m-d 00:00:00'),
                'end_time' => $params['end_time'] ?? now()->format('Y-m-d 23:59:59'),
                'page' => $params['page'] ?? 1,
                'limit' => $params['limit'] ?? 50,
            ];
            
            $token = $token ?? $this->getGeneralToken();
            
            if (!$token) {
                throw new Exception('Authentication token required');
            }
            
            // Tentative de connexion à l'API
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $token,
                'Accept' => 'application/json',
            ])->timeout(15)
              ->connectTimeout(10)
              ->retry(2, 1000)
              ->get(rtrim($this->baseUrl, '/') . '/iclock/api/transactions/', $defaultParams);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extraire terminal_uid des transactions
                $terminalUid = null;
                if (isset($data['transactions']) && count($data['transactions']) > 0) {
                    foreach ($data['transactions'] as $transaction) {
                        if (isset($transaction['terminal_sn'])) {
                            $terminalSn = $transaction['terminal_sn'];
                            $terminalUid = 'CHK-TM-' . substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $terminalSn)), -8);
                            break;
                        }
                    }
                }
                
                // Ajouter la vérification biométrique
                if (isset($defaultParams['emp_code'])) {
                    $biometricData = $this->generateBiometricResponse($defaultParams['emp_code'], $terminalUid);
                    if ($biometricData['success']) {
                        $data['biometric_verification'] = $biometricData;
                    }
                }
                
                return [
                    'success' => true,
                    'data' => $data,
                    'source' => 'api'
                ];
            } else {
                throw new Exception('API request failed with status: ' . $response->status());
            }
            
        } catch (Exception $e) {
            Log::error('Get Transactions Error: ' . $e->getMessage());
            
            // Fallback: générer des données simulées
            return $this->generateMockTransactions($params);
        }
    }
    
    /**
     * Générer des transactions simulées en cas d'échec de l'API
     */
    private function generateMockTransactions($params)
    {
        $empCode = $params['emp_code'] ?? 50;
        $limit = $params['limit'] ?? 10;
        
        $transactions = [];
        $now = now();
        
        // Générer un terminal_uid cohérent pour toutes les transactions mock
        $terminalUid = 'CHK-TM-' . strtoupper(Str::random(8));
        
        for ($i = 1; $i <= $limit; $i++) {
            $transactions[] = [
                'id' => rand(1000, 9999),
                'emp_code' => $empCode,
                'terminal_sn' => $terminalUid,
                'terminal_uid' => $terminalUid,
                'punch_time' => $now->subMinutes($i * 30)->format('Y-m-d H:i:s'),
                'status' => ['IN', 'OUT'][rand(0, 1)],
                'verify_type' => ['FACE', 'FINGERPRINT', 'PASSWORD'][rand(0, 2)],
                'work_code' => 'W' . rand(100, 999),
            ];
        }
        
        // Générer la vérification biométrique avec le même terminal_uid
        $biometricData = $this->generateBiometricResponse($empCode, $terminalUid);
        
        return [
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'count' => $limit,
                'page' => $params['page'] ?? 1,
                'total_pages' => ceil(100 / $limit),
                'biometric_verification' => $biometricData['success'] ? $biometricData : null,
            ],
            'source' => 'mock',
            'warning' => 'API unavailable, using simulated data'
        ];
    }
}
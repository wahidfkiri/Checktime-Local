<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class CheckTimeService
{
    private ?string $baseUrl = null;
    private ?string $generalToken = null;

    /**
     * Constructor - Charge config depuis la table settings (fallback access_configs)
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Charge base_url et token depuis la table settings (group=company)
     * Fallback: access_configs table, puis hardcoded default
     */
    private function loadConfig(): void
    {
        try {
            $settings = Setting::getGroup('company');

            $this->baseUrl = $settings['api_url']
                ?? env('CHECKTIME_BASE_URL', 'http://54.37.15.111');

            $this->generalToken = $settings['api_token'] ?? null;

            if (!$this->generalToken) {
                $access_credentials = DB::table('access_configs')->first();
                if ($access_credentials && !empty($access_credentials->general_token)) {
                    $this->generalToken = $access_credentials->general_token;
                }
            }

            if (!$this->generalToken) {
                $this->generalToken = env('CHECKTIME_TOKEN');
            }

            if (!$this->generalToken) {
                \Log::warning('Aucun token trouvé dans settings ni access_configs.');
            }
        } catch (\Exception $e) {
            $this->baseUrl = env('CHECKTIME_BASE_URL', 'http://54.37.15.111');
            $this->generalToken = env('CHECKTIME_TOKEN');
            \Log::error('Erreur chargement config CheckTimeService: ' . $e->getMessage());
        }
    }

    /**
     * Test de la validité du token avec une requête GET
     */
    public function testTokenValid(string $token): bool
    {
        try {
            // Utilise une requête GET simple pour tester la validité du token
            $response = Http::withHeaders([
                "Authorization" => "Token " . $token,
                "Accept" => "application/json"
            ])->get($this->baseUrl . '/iclock/api/terminals/');

            
            
            // Vous pouvez ajuster l'endpoint selon votre API
            // Par exemple, utilisez un endpoint qui ne nécessite pas beaucoup de permissions
            // comme /api/user/me/ ou /api/auth/verify/

            if ($response->successful()) {
                return true;
            }
            
            // Si la réponse est 401 Unauthorized, le token est invalide
            if ($response->status() === 401) {
                return false;
            }
            
            // Pour d'autres statuts, on peut considérer que le token est valide
            // mais la ressource demandée n'existe pas ou autre erreur
            return $response->status() !== 403; // 403 Forbidden pourrait aussi indiquer un problème de token
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la validation du token: ' . $e->getMessage());
        }
    }

    /**
     * Test du token stocké
     */
    public function testStoredToken(): bool
    {
        if (!$this->generalToken) {
            throw new \Exception('Token non configuré.');
        }

        return $this->testTokenValid($this->generalToken);
    }

    /**
     * Get base URL
     */
    public function getBaseUrl(): string
    {
        if (!$this->baseUrl) {
            $this->loadConfig();
        }
        return $this->baseUrl;
    }

    /**
     * Get GENERAL TOKEN
     */
    public function getGeneralToken(): string
    {
        if (!$this->generalToken) {
            throw new \Exception('Token général non configuré. Veuillez configurer un token d\'accès.');
        }

        return $this->generalToken;
    }

    /**
     * Vérifie si un token est configuré
     */
    public function hasToken(): bool
    {
        return !empty($this->generalToken);
    }

    /**
     * Update token (dans settings + access_configs)
     */
    public function updateToken(string $token): void
    {
        try {
            $this->generalToken = $token;

            // Écrire dans settings
            Setting::updateOrCreate(
                ['key' => 'api_token'],
                ['value' => $token, 'group' => 'company']
            );

            // Écrire aussi dans access_configs (backward compat)
            $existingRecord = DB::table('access_configs')->first();

            if ($existingRecord) {
                DB::table('access_configs')
                    ->where('id', $existingRecord->id)
                    ->update([
                        'general_token' => $token,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('access_configs')->insert([
                    'general_token' => $token,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            \Log::info('Token mis à jour avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du token: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la mise à jour du token: ' . $e->getMessage());
        }
    }

    /**
     * GET request (uses GENERAL TOKEN)
     */
    public function get(string $endpoint, array $params = [], $token): array
    {
        // Vérifier que le token est configuré
        if (!$this->hasToken()) {
            throw new \Exception('Token non configuré. Veuillez configurer un token avant de faire des requêtes.');
        }

        $response = Http::withHeaders([
            "Authorization" => "Token " . $token,
            "Accept" => "application/json"
        ])
        ->timeout(60)
        ->retry(3, 1000)
        ->get($this->baseUrl . $endpoint, $params);

        if ($response->failed()) {
            throw new \Exception("GET request failed to {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    /**
     * POST request (uses GENERAL TOKEN)
     */
    public function post(string $endpoint, array $data = []): array
    {
        // Vérifier que le token est configuré
        if (!$this->hasToken()) {
            throw new \Exception('Token non configuré. Veuillez configurer un token avant de faire des requêtes.');
        }

        $response = Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->post($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new \Exception("POST request failed to {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    // [Les autres méthodes restent similaires...]

    /**
     * PATCH request (uses GENERAL TOKEN)
     */
    public function patch(string $endpoint, array $data = []): array
    {
        if (!$this->hasToken()) {
            throw new \Exception('Token non configuré. Veuillez configurer un token avant de faire des requêtes.');
        }

        $response = Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->patch($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new \Exception("PATCH request failed to {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    /**
     * PUT request (uses GENERAL TOKEN)
     */
    public function put(string $endpoint, array $data = []): array
    {
        if (!$this->hasToken()) {
            throw new \Exception('Token non configuré. Veuillez configurer un token avant de faire des requêtes.');
        }

        $response = Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->put($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new \Exception("PUT request failed to {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    /**
     * DELETE request (uses GENERAL TOKEN)
     */
    public function delete(string $endpoint): array
    {
        if (!$this->hasToken()) {
            throw new \Exception('Token non configuré. Veuillez configurer un token avant de faire des requêtes.');
        }

        $response = Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json"
        ])->delete($this->baseUrl . $endpoint);

        if ($response->failed()) {
            throw new \Exception("DELETE request failed to {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Clear token cache
     */
    public function clearToken(): void
    {
        $this->generalToken = null;
    }

    /**
     * Check if service is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->hasToken();
    }

    /**
     * Refresh config from database
     */
    public function refreshConfig(): void
    {
        $this->loadConfig();
    }

    /**
     * Refresh token alias
     */
    public function refreshToken(): void
    {
        $this->loadConfig();
    }

    // ─── Static helpers ─────────────────────────────────────────────

    /**
     * Lire l'URL de base depuis la table settings (fallback access_configs)
     */
    public static function getConfigBaseUrl(): string
    {
        try {
            $settings = Setting::getGroup('company');
            return $settings['api_url']
                ?? env('CHECKTIME_BASE_URL', 'http://54.37.15.111');
        } catch (\Exception $e) {
            return env('CHECKTIME_BASE_URL', 'http://54.37.15.111');
        }
    }

    /**
     * Lire le token depuis la table settings (fallback access_configs)
     */
    public static function getConfigToken(): ?string
    {
        try {
            $settings = Setting::getGroup('company');
            $token = $settings['api_token'] ?? null;

            if (!$token) {
                $config = DB::table('access_configs')->first();
                $token = $config->general_token ?? null;
            }

            if (!$token) {
                $token = env('CHECKTIME_TOKEN');
            }

            return $token;
        } catch (\Exception $e) {
            return env('CHECKTIME_TOKEN');
        }
    }

    /**
     * Initialiser la table access_configs si elle est vide
     */
    public function initializeTable(): bool
    {
        try {
            $count = DB::table('access_configs')->count();

            if ($count === 0) {
                DB::table('access_configs')->insert([
                    'general_token' => $this->generalToken,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Erreur initialisation access_configs: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir le statut de configuration
     */
    public function getConfigStatus(): array
    {
        try {
            $settings = Setting::getGroup('company');
        } catch (\Exception $e) {
            $settings = [];
        }

        return [
            'has_token' => $this->hasToken(),
            'settings_has_api_url' => !empty($settings['api_url']),
            'settings_has_api_token' => !empty($settings['api_token']),
            'access_configs_exists' => $this->checkTableExists(),
            'base_url' => $this->baseUrl ?? self::getConfigBaseUrl()
        ];
    }

    /**
     * Vérifie si la table access_configs existe
     */
    private function checkTableExists(): bool
    {
        try {
            DB::table('access_configs')->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CheckTimeService
{
    private string $baseUrl = "http://54.37.15.111";
    private ?string $generalToken = null;

    /**
     * Constructor - Charge le token depuis la base de données
     */
    public function __construct()
    {
        $this->loadToken();
    }

    /**
     * Charge le token depuis la base de données
     */
    private function loadToken(): void
    {
        try {
            $access_credentials = DB::table('access_configs')->first();
            
            if ($access_credentials && !empty($access_credentials->general_token)) {
                $this->generalToken = $access_credentials->general_token;
            } else {
                // Pas d'erreur immédiate, on permet à l'utilisateur de configurer le token plus tard
                $this->generalToken = null;
                \Log::warning('Aucun token trouvé dans la base de données. Le service nécessite une configuration.');
            }
        } catch (\Exception $e) {
            // En cas d'erreur de base de données, on log l'erreur mais on ne bloque pas l'instanciation
            $this->generalToken = null;
            \Log::error('Erreur lors du chargement du token: ' . $e->getMessage());
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
     * Update token
     */
    public function updateToken(string $token): void
    {
        try {
            $this->generalToken = $token;
            
            // Sauvegarder dans la base de données
            $existingRecord = DB::table('access_configs')->first();
            
            if ($existingRecord) {
                // Mettre à jour l'enregistrement existant
                DB::table('access_configs')
                    ->where('id', $existingRecord->id)
                    ->update([
                        'general_token' => $token,
                        'updated_at' => now()
                    ]);
            } else {
                // Créer un nouvel enregistrement
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
        ])->get($this->baseUrl . $endpoint, $params);

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
     * Refresh token from database
     */
    public function refreshToken(): void
    {
        $this->loadToken();
    }

    /**
     * Initialiser la table si elle est vide
     */
    public function initializeTable(): bool
    {
        try {
            // Vérifier si la table existe et est vide
            $count = DB::table('access_configs')->count();
            
            if ($count === 0) {
                // Créer un enregistrement vide
                DB::table('access_configs')->insert([
                    'general_token' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'initialisation de la table: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir le statut de configuration
     */
    public function getConfigStatus(): array
    {
        return [
            'has_token' => $this->hasToken(),
            'table_exists' => $this->checkTableExists(),
            'table_has_records' => DB::table('access_configs')->exists(),
            'base_url' => $this->baseUrl
        ];
    }

    /**
     * Vérifie si la table existe
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
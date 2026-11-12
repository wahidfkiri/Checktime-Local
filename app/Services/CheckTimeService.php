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
     * Charge base_url et token depuis la table settings (group=company).
     * Repli token : table access_configs (installations historiques).
     * Aucune lecture de .env : ces valeurs ne vivent plus dans l'environnement.
     */
    private function loadConfig(): void
    {
        $this->baseUrl = Setting::apiUrl();
        $this->generalToken = Setting::apiToken();

        if (!$this->generalToken) {
            \Log::warning('Aucun token trouvé dans settings ni access_configs.');
        }
    }

    /**
     * Récupère TOUS les employés de l'API biométrique, toutes pages confondues.
     *
     * L'API renvoie les employés par pages (10 par défaut : le paramètre `limit`
     * est ignoré) avec un champ `next` et un `count` total. On suit donc la
     * pagination annoncée par l'API elle-même (`next`, que ce soit en `page=` ou
     * en `offset=`) au lieu de supposer un format, on dédoublonne (une API qui
     * ignorerait `page` renverrait sans fin la même page) et on compare le
     * nombre récupéré au `count` annoncé.
     *
     * @return array{employees: array, count: ?int, complete: bool, pages: int}
     * @throws \Exception si la première page échoue
     */
    public static function fetchAllEmployees(string $baseUrl, string $token, int $pageSize = 100): array
    {
        $endpoint = rtrim($baseUrl, '/') . '/personnel/api/employees/';
        $params   = ['page' => 1, 'page_size' => $pageSize, 'limit' => $pageSize];

        $employees = [];
        $seen      = [];
        $count     = null;
        $pages     = 0;
        $failed    = false;

        for ($i = 0; $i < 2000; $i++) {
            $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $token,
                    'Accept'        => 'application/json',
                ])
                ->timeout(60)
                ->retry(3, 1000, throw: false)
                ->get($endpoint, $params);

            if ($response->failed()) {
                if ($pages === 0) {
                    throw new \Exception('GET /personnel/api/employees/ a échoué (HTTP ' . $response->status() . ') : ' . $response->body());
                }
                \Log::warning('Pagination employés interrompue (HTTP ' . $response->status() . ') après ' . $pages . ' page(s)');
                $failed = true;
                break;
            }

            $pages++;
            $json  = $response->json();
            $batch = $json['data'] ?? (is_array($json) && array_is_list($json) ? $json : []);

            if ($count === null && isset($json['count']) && is_numeric($json['count'])) {
                $count = (int) $json['count'];
            }

            $new = 0;
            foreach ($batch as $employee) {
                $key = $employee['id'] ?? $employee['emp_code'] ?? md5(json_encode($employee));
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key]  = true;
                $employees[] = $employee;
                $new++;
            }

            // Page vide ou déjà vue : fin (évite toute boucle infinie).
            if ($new === 0) {
                break;
            }

            if (!empty($json['next'])) {
                // On reprend les paramètres de la page suivante annoncés par l'API
                // (page=N ou offset=N&limit=M), sur l'URL configurée.
                parse_str((string) parse_url($json['next'], PHP_URL_QUERY), $nextParams);
                if (empty($nextParams)) {
                    break;
                }
                $params = $nextParams + ['page_size' => $pageSize];
            } elseif ($count !== null && count($employees) < $count) {
                // Pas de `next` mais il en manque : essayer la page suivante.
                $params['page'] = ($params['page'] ?? $pages) + 1;
            } else {
                break;
            }

            usleep(50000);
        }

        return [
            'employees' => $employees,
            'count'     => $count,
            'complete'  => !$failed && ($count === null || count($employees) >= $count),
            'pages'     => $pages,
        ];
    }

    /**
     * Test de la validité du token avec une requête GET
     */
    public function testTokenValid(string $token): bool
    {
        try {
            // Utilise une requête GET simple pour tester la validité du token.
            // Timeout explicite : sans lui, une requête HTTP peut rester
            // bloquée indéfiniment si l'appareil biométrique est injoignable.
            $response = Http::withHeaders([
                "Authorization" => "Token " . $token,
                "Accept" => "application/json"
            ])->timeout(15)->connectTimeout(10)->get($this->baseUrl . '/iclock/api/terminals/');

            
            
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
            throw new \Exception('Erreur lors de la validation de l\'accès: ' . $e->getMessage());
        }
    }

    /**
     * Test du token stocké
     */
    public function testStoredToken(): bool
    {
        if (!$this->generalToken) {
            throw new \Exception('Configuration d\'accès manquante.');
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
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner dans les paramètres.');
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
            throw new \Exception('Erreur lors de la mise à jour de la configuration d\'accès: ' . $e->getMessage());
        }
    }

    /**
     * GET request (uses GENERAL TOKEN)
     */
    public function get(string $endpoint, array $params = [], $token): array
    {
        // Vérifier que le token est configuré
        if (!$this->hasToken()) {
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner avant de faire des requêtes.');
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
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner avant de faire des requêtes.');
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
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner avant de faire des requêtes.');
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
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner avant de faire des requêtes.');
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
            throw new \Exception('Configuration d\'accès manquante. Veuillez la renseigner avant de faire des requêtes.');
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
     * Lire l'URL de base depuis la table settings (group=company, clé api_url)
     */
    public static function getConfigBaseUrl(): string
    {
        return Setting::apiUrl();
    }

    /**
     * Lire le token depuis la table settings (fallback access_configs)
     */
    public static function getConfigToken(): ?string
    {
        return Setting::apiToken();
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
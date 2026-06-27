<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\InstallationLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class InstallerController extends Controller
{
    /**
     * Show the installer wizard.
     */
    public function index()
    {
        if (InstallationLock::isInstalled()) {
            return redirect()->route('login');
        }

        return view('installer.index');
    }

    /**
     * Step 1: Save application information.
     */
    public function saveAppInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'timezone' => 'required|string|max:100',
            'locale' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'app_name' => $request->input('app_name'),
            'timezone' => $request->input('timezone'),
            'locale' => $request->input('locale'),
            'app_logo' => null,
        ];

        // Handle logo upload
        if ($request->hasFile('app_logo')) {
            $logo = $request->file('app_logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('uploads/logos'), $logoName);
            $data['app_logo'] = 'uploads/logos/' . $logoName;
        }

        // Store in session for later use
        $request->session()->put('installer.app_info', $data);

        return response()->json([
            'success' => true,
            'message' => 'Informations de l\'application sauvegardées.',
        ]);
    }

    /**
     * Step 2: Save administrator account.
     */
    public function saveAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $request->session()->put('installer.admin', [
            'full_name' => $request->input('full_name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Informations administrateur sauvegardées.',
        ]);
    }

    /**
     * Step 3: Save endpoint configuration.
     */
    public function saveEndpoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_url' => 'required|url|max:500',
            'api_token' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Test the API connection
        $testResult = $this->testApiConnection(
            $request->input('api_url'),
            $request->input('api_token')
        );

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de se connecter à l\'API: ' . $testResult['message'],
                'errors' => ['api_url' => ['Connexion échouée: ' . $testResult['message']]],
            ], 422);
        }

        $request->session()->put('installer.endpoint', [
            'api_url' => $request->input('api_url'),
            'api_token' => $request->input('api_token'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuration de l\'endpoint sauvegardée. Connexion API réussie.',
        ]);
    }

    /**
     * Step 4: Save SMTP configuration (optional).
     */
    public function saveSmtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl,null',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $request->session()->put('installer.smtp', [
            'mail_host' => $request->input('mail_host'),
            'mail_port' => $request->input('mail_port'),
            'mail_username' => $request->input('mail_username'),
            'mail_password' => $request->input('mail_password'),
            'mail_encryption' => $request->input('mail_encryption'),
            'mail_from_address' => $request->input('mail_from_address'),
            'mail_from_name' => $request->input('mail_from_name'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuration SMTP sauvegardée.',
        ]);
    }

    /**
     * Get installation summary (for step 5).
     */
    public function getSummary(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_info' => $request->session()->get('installer.app_info'),
                'admin' => collect($request->session()->get('installer.admin', []))
                    ->except('password')
                    ->merge(['password' => '••••••••'])
                    ->toArray(),
                'endpoint' => collect($request->session()->get('installer.endpoint', []))
                    ->except('api_token')
                    ->merge(['api_token' => '••••••••'])
                    ->toArray(),
                'smtp' => collect($request->session()->get('installer.smtp', []))
                    ->except('mail_password')
                    ->merge(['mail_password' => '••••••••'])
                    ->toArray(),
            ],
        ]);
    }

    /**
     * Test SMTP connection by sending a test email.
     */
    public function testSmtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $host = $request->input('mail_host');
            $port = $request->input('mail_port');
            $username = $request->input('mail_username');
            $password = $request->input('mail_password');
            $encryption = $request->input('mail_encryption');
            $fromAddress = $request->input('mail_from_address');
            $fromName = $request->input('mail_from_name', 'CheckTime');

            // Temporarily override mail config
            config([
                'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => (int) $port,
                    'username' => $username,
                    'password' => $password,
                    'encryption' => $encryption ?: null,
                    'timeout' => 10,
                ]),
                'mail.from' => [
                    'address' => $fromAddress,
                    'name' => $fromName,
                ],
            ]);

            // Purge the mailer to pick up the new config
            Mail::mailer('smtp');

            // Send test email
            Mail::raw(
                'Ceci est un email de test depuis CheckTime. Si vous recevez ce message, votre configuration SMTP est correcte.',
                function ($message) use ($fromAddress, $fromName) {
                    $message->to($fromAddress, $fromName)
                            ->subject('Test de configuration SMTP - CheckTime');
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à ' . $fromAddress . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Envoi échoué: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute the full installation process.
     */
    public function install(Request $request)
    {
        if (InstallationLock::isInstalled()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'application est déjà installée.',
            ], 403);
        }

        $appInfo = $request->session()->get('installer.app_info');
        $admin = $request->session()->get('installer.admin');
        $endpoint = $request->session()->get('installer.endpoint');
        $smtp = $request->session()->get('installer.smtp');

        // Validate all required data exists (SMTP is optional)
        if (!$appInfo || !$admin || !$endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Données d\'installation incomplètes. Veuillez recommencer.',
            ], 422);
        }

        try {
            // 1. Run migrations safely (handles pre-existing tables)
            $this->runMigrationsSafely();

            // 2. Create administrator account
            if (Schema::hasTable('users')) {
                $adminExists = User::where('email', $admin['email'])->exists();

                if (!$adminExists) {
                    $user = User::create([
                        'name' => $admin['full_name'],
                        'email' => $admin['email'],
                        'password' => Hash::make($admin['password']),
                    ]);

                    if (Schema::hasColumn('users', 'email_verified_at')) {
                        $user->email_verified_at = now();
                        $user->save();
                    }

                    // Assign admin role if roles table exists
                    if (Schema::hasTable('roles')) {
                        $adminRole = Role::where('name', 'admin')->first();
                        if ($adminRole) {
                            $user->assignRole('admin');
                        }
                    }
                }
            }

            // 3. Lock the application FIRST so even if something fails after,
            // the installer won't allow retry (prevents partial re-runs)
            InstallationLock::lock();

            // 4. Update .env file
            $this->updateEnvFile($appInfo, $endpoint, $smtp ?? []);

            // 5. Save settings to database (optional, gracefully skip if schema mismatch)
            try {
                $this->saveSettings($appInfo, $endpoint, $smtp ?? []);
            } catch (\Exception $e) {
                // Settings table schema may not match expected format, skip
            }

            // 6. Clear caches (skip config:cache to avoid runtime crashes)
            try { Artisan::call('config:clear'); } catch (\Exception $e) { Log::warning('Install: config:clear failed: ' . $e->getMessage()); }
            try { Artisan::call('cache:clear'); } catch (\Exception $e) { Log::warning('Install: cache:clear failed: ' . $e->getMessage()); }

            // 7. Flush entire session (clears stale encrypted cookies)
            $request->session()->flush();
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Installation terminée avec succès !',
                'redirect' => route('login'),
            ]);

        } catch (\Exception $e) {
            Log::error('Installation failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'installation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run migrations safely, handling pre-existing tables.
     */
    protected function runMigrationsSafely(): void
    {
        // Ensure the migrations tracking table exists
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $files = glob(database_path('migrations/*.php'));
        sort($files);
        $batch = (DB::table('migrations')->max('batch') ?? 0) + 1;

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (DB::table('migrations')->where('migration', $name)->exists()) {
                continue;
            }

            try {
                $migration = require $file;
                $migration->up();
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Base table or view already exists') &&
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }

            DB::table('migrations')->insert([
                'migration' => $name,
                'batch' => $batch,
            ]);
        }
    }

    /**
     * Test the API connection using a general token.
     */
    protected function testApiConnection(string $url, string $token): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => rtrim($url, '/') . '/',
                'timeout' => 10,
                'verify' => false,
            ]);

            $response = $client->get('iclock/api/terminals/', [
                'headers' => [
                    'Authorization' => 'Token ' . $token,
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return ['success' => true, 'message' => 'Connexion réussie.'];
            }

            return ['success' => false, 'message' => 'Réponse API invalide.'];
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return ['success' => false, 'message' => 'Impossible de se connecter au serveur.'];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 401 || $statusCode === 403) {
                return ['success' => false, 'message' => 'Token API invalide.'];
            }
            return ['success' => false, 'message' => 'Erreur client: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update the .env file with installation values.
     */
    protected function updateEnvFile(array $appInfo, array $endpoint, array $smtp): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $hasSmtp = !empty($smtp['mail_host']);

        $replacements = [
            'APP_NAME' => '"' . $appInfo['app_name'] . '"',
            'APP_TIMEZONE' => $appInfo['timezone'],
            'APP_LOCALE' => $appInfo['locale'],

            'CHECKTIME_BASE_URL' => $endpoint['api_url'],
            'CHECKTIME_TOKEN' => $endpoint['api_token'],

            'MAIL_MAILER' => $hasSmtp ? 'smtp' : 'log',
            'MAIL_HOST' => $smtp['mail_host'] ?? '',
            'MAIL_PORT' => $smtp['mail_port'] ?? '',
            'MAIL_USERNAME' => $smtp['mail_username'] ?? '',
            'MAIL_PASSWORD' => $smtp['mail_password'] ?? '',
            'MAIL_ENCRYPTION' => ($smtp['mail_encryption'] ?? '') ?: 'null',
            'MAIL_FROM_ADDRESS' => !empty($smtp['mail_from_address']) ? ('"' . $smtp['mail_from_address'] . '"') : '""',
            'MAIL_FROM_NAME' => !empty($smtp['mail_from_name']) ? ('"' . $smtp['mail_from_name'] . '"') : '"' . ($appInfo['app_name'] ?? 'CheckTime') . '"',
        ];

        foreach ($replacements as $key => $value) {
            $pattern = '/^' . $key . '=.*$/m';
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $key . '=' . $value, $envContent);
            } else {
                $envContent .= "\n" . $key . '=' . $value;
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Save settings to the database.
     */
    protected function saveSettings(array $appInfo, array $endpoint, array $smtp): void
    {
        // Check if settings table exists
        if (!Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            ['key' => 'app_name', 'value' => $appInfo['app_name']],
            ['key' => 'app_logo', 'value' => $appInfo['app_logo'] ?? ''],
            ['key' => 'app_timezone', 'value' => $appInfo['timezone']],
            ['key' => 'app_locale', 'value' => $appInfo['locale']],
            ['key' => 'api_url', 'value' => $endpoint['api_url']],
            ['key' => 'api_token', 'value' => $endpoint['api_token']],
            ['key' => 'mail_host', 'value' => $smtp['mail_host'] ?? ''],
            ['key' => 'mail_port', 'value' => $smtp['mail_port'] ?? ''],
            ['key' => 'mail_username', 'value' => $smtp['mail_username'] ?? ''],
            ['key' => 'mail_from_address', 'value' => $smtp['mail_from_address'] ?? ''],
            ['key' => 'mail_from_name', 'value' => $smtp['mail_from_name'] ?? ''],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
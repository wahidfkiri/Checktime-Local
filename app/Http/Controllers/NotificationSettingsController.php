<?php

namespace App\Http\Controllers;

use App\Models\ScheduledNotification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NotificationSettingsController extends Controller
{
    /**
     * Commandes autorisées à être exécutées/planifiées depuis l'interface.
     */
    private const ALLOWED_COMMANDS = [
        'attendance:send-weekly-reports',
        'attendance:send-weekly-rh-reports',
        'reports:send-monthly-rh',
    ];

    /**
     * Enregistrer la configuration SMTP dans la table settings.
     */
    public function smtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_host'         => 'required|string|max:255',
            'mail_port'         => 'required|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $fields = [
                'mail_host'         => $request->input('mail_host'),
                'mail_port'         => (string) $request->input('mail_port'),
                'mail_encryption'   => $request->input('mail_encryption', ''),
                'mail_username'     => $request->input('mail_username', ''),
                'mail_from_address' => $request->input('mail_from_address'),
                'mail_from_name'    => $request->input('mail_from_name', config('app.name', 'CheckTime')),
            ];

            // Le mot de passe n'est mis à jour que s'il est fourni (évite de l'effacer).
            if ($request->filled('mail_password')) {
                $fields['mail_password'] = $request->input('mail_password');
            }

            foreach ($fields as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => 'mail']
                );
            }

            // Appliquer immédiatement pour la requête courante.
            $this->applyMailConfig();

            Log::info('Configuration SMTP mise à jour depuis le profil.');

            return response()->json([
                'success' => true,
                'message' => 'Configuration SMTP enregistrée avec succès.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement SMTP: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tester la configuration SMTP en envoyant un email.
     */
    public function testSmtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_host'         => 'required|string',
            'mail_port'         => 'required|integer',
            'mail_encryption'   => 'nullable|in:tls,ssl',
            'mail_username'     => 'nullable|string',
            'mail_password'     => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'nullable|string',
            'test_email'        => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $fromAddress = $request->input('mail_from_address');
            $fromName    = $request->input('mail_from_name', config('app.name', 'CheckTime'));
            $testEmail   = $request->input('test_email');

            // Si le mot de passe n'est pas fourni, réutiliser celui déjà enregistré.
            $password = $request->filled('mail_password')
                ? $request->input('mail_password')
                : Setting::where('key', 'mail_password')->value('value');

            config([
                'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                    'transport'  => 'smtp',
                    'host'       => $request->input('mail_host'),
                    'port'       => (int) $request->input('mail_port'),
                    'username'   => $request->input('mail_username'),
                    'password'   => $password,
                    'encryption' => $request->input('mail_encryption') ?: null,
                    'timeout'    => 15,
                ]),
                'mail.default' => 'smtp',
                'mail.from'    => ['address' => $fromAddress, 'name' => $fromName],
            ]);

            Mail::mailer('smtp');

            Mail::raw(
                "Ceci est un email de test envoyé depuis les paramètres de notification de CheckTime.\n"
                . "Si vous recevez ce message, votre configuration SMTP est correcte.",
                function ($message) use ($testEmail, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName)
                            ->to($testEmail)
                            ->subject('Test SMTP - CheckTime');
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à ' . $testEmail . '.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur test SMTP: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Envoi échoué: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Enregistrer la clé API SMS dans la table settings.
     */
    public function sms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sms_api_key' => 'required|string|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            Setting::updateOrCreate(
                ['key' => 'sms_api_key'],
                ['value' => $request->input('sms_api_key'), 'group' => 'sms']
            );

            config(['sms.fastway.api_key' => $request->input('sms_api_key')]);

            Log::info('Clé API SMS mise à jour depuis le profil.');

            return response()->json([
                'success' => true,
                'message' => 'Clé API SMS enregistrée avec succès.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement clé API SMS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tester la clé API SMS (vérification de connectivité / solde).
     */
    public function testSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sms_api_key' => 'required|string|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // Applique la clé postée puis reconstruit le service pour la prendre en compte.
            config(['sms.fastway.api_key' => $request->input('sms_api_key')]);

            $service = new \App\Services\SmsService();
            $balance = $service->getBalance(true);

            if (!empty($balance['success'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion API SMS réussie. Solde : ' . ($balance['balance_formatted'] ?? $balance['balance'] ?? 'N/A'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Échec du test SMS : ' . ($balance['error'] ?? 'réponse invalide de l\'API.'),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur test clé API SMS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur test SMS: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mettre à jour la configuration des tâches planifiées.
     */
    public function updateJobs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jobs'                    => 'required|array',
            'jobs.*.id'               => 'required|integer|exists:scheduled_notifications,id',
            'jobs.*.is_active'        => 'required|boolean',
            'jobs.*.frequency'        => 'required|in:daily,weekly,monthly,custom,once',
            'jobs.*.time'             => 'nullable|date_format:H:i',
            'jobs.*.day_of_week'      => 'nullable|integer|between:1,7',
            'jobs.*.day_of_month'     => 'nullable|integer|between:1,31',
            'jobs.*.cron_expression'  => 'nullable|string|max:100',
            'jobs.*.run_at'           => 'nullable|date',
            'jobs.*.recipients'       => 'nullable|array',
            'jobs.*.recipients.*'     => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            foreach ($request->input('jobs', []) as $data) {
                $job = ScheduledNotification::find($data['id']);
                if (!$job) {
                    continue;
                }

                $job->is_active       = (bool) $data['is_active'];
                $job->frequency       = $data['frequency'];
                $job->time            = $data['time'] ?? '09:00';
                $job->day_of_week     = $data['frequency'] === 'weekly' ? ($data['day_of_week'] ?? 1) : null;
                $job->day_of_month    = $data['frequency'] === 'monthly' ? ($data['day_of_month'] ?? 1) : null;
                $job->cron_expression = $data['frequency'] === 'custom' ? ($data['cron_expression'] ?? null) : null;

                if ($data['frequency'] === 'once') {
                    $job->run_at = !empty($data['run_at']) ? \Carbon\Carbon::parse($data['run_at']) : null;
                    // Ré-armer la tâche pour qu'elle se déclenche à la nouvelle date.
                    $job->last_run_at = null;
                    $job->last_status = null;
                } else {
                    $job->run_at = null;
                }

                if ($job->supports_recipients) {
                    $recipients = array_values(array_filter(array_map('trim', $data['recipients'] ?? [])));
                    $job->recipients = !empty($recipients) ? $recipients : null;
                }

                $job->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Tâches planifiées enregistrées avec succès.',
                'jobs'    => ScheduledNotification::all(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour tâches planifiées: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exécuter une tâche immédiatement (test manuel).
     */
    public function runJob(Request $request)
    {
        $command = $request->input('command');

        if (!in_array($command, self::ALLOWED_COMMANDS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non autorisée.',
            ], 403);
        }

        try {
            @set_time_limit(0);

            Artisan::call($command);
            $output = trim(Artisan::output());

            ScheduledNotification::where('command', $command)->update([
                'last_run_at' => now(),
                'last_status' => 'Exécutée manuellement',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande exécutée: ' . $command,
                'output'  => $output !== '' ? $output : '(aucune sortie)',
            ]);
        } catch (\Throwable $e) {
            Log::error("Erreur exécution commande {$command}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur exécution: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Injecter la configuration SMTP de la base dans la config runtime.
     */
    private function applyMailConfig(): void
    {
        $mail = Setting::mailConfig();

        if (empty($mail['mail_host'])) {
            return;
        }

        config([
            'mail.default'                     => 'smtp',
            'mail.mailers.smtp.transport'      => 'smtp',
            'mail.mailers.smtp.host'           => $mail['mail_host'],
            'mail.mailers.smtp.port'           => (int) ($mail['mail_port'] ?: 587),
            'mail.mailers.smtp.username'       => $mail['mail_username'] ?: null,
            'mail.mailers.smtp.password'       => $mail['mail_password'] ?: null,
            'mail.mailers.smtp.encryption'     => $mail['mail_encryption'] ?: null,
            'mail.from.address'                => $mail['mail_from_address'] ?: null,
            'mail.from.name'                   => $mail['mail_from_name'] ?: config('app.name', 'CheckTime'),
        ]);

        Mail::mailer('smtp');
    }
}

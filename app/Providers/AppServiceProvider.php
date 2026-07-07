<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        try {
            if (Schema::hasTable('settings')) {
                $logoPath = \App\Models\Setting::where('key', 'app_logo')->value('value');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not load app_logo setting: ' . $e->getMessage());
        }

        $defaultLogo = asset('logo.jpg');
        $appLogo = !empty($logoPath) ? asset($logoPath) : $defaultLogo;

        view()->composer('*', function ($view) use ($appLogo) {
            $view->with('appLogo', $appLogo);
        });

        // Applique la configuration SMTP stockée en base (groupe "mail")
        // pour le web ET les commandes Artisan (cron), si elle est renseignée.
        $this->applyDatabaseMailConfig();

        // Applique la clé API SMS stockée en base, si elle est renseignée.
        $this->applyDatabaseSmsConfig();
    }

    /**
     * Injecte la clé API SMS de la base dans la config au démarrage.
     */
    protected function applyDatabaseSmsConfig(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $apiKey = \App\Models\Setting::where('key', 'sms_api_key')->value('value');

            if (!empty($apiKey)) {
                config(['sms.fastway.api_key' => $apiKey]);
            }
        } catch (\Throwable $e) {
            Log::warning('Impossible d\'appliquer la clé API SMS depuis la base: ' . $e->getMessage());
        }
    }

    /**
     * Injecte la configuration SMTP de la base dans la config mail au démarrage.
     */
    protected function applyDatabaseMailConfig(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $mail = \App\Models\Setting::where('group', 'mail')->pluck('value', 'key')->toArray();

            if (empty($mail['mail_host'])) {
                return;
            }

            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.transport'  => 'smtp',
                'mail.mailers.smtp.host'       => $mail['mail_host'],
                'mail.mailers.smtp.port'       => (int) ($mail['mail_port'] ?? 587),
                'mail.mailers.smtp.username'   => $mail['mail_username'] ?? null,
                'mail.mailers.smtp.password'   => $mail['mail_password'] ?? null,
                'mail.mailers.smtp.encryption' => ($mail['mail_encryption'] ?? '') ?: null,
                'mail.from.address'            => $mail['mail_from_address'] ?? config('mail.from.address'),
                'mail.from.name'               => $mail['mail_from_name'] ?? config('mail.from.name'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Impossible d\'appliquer la config SMTP depuis la base: ' . $e->getMessage());
        }
    }
}

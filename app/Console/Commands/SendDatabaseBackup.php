<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use App\Models\ScheduledNotification;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Vendor\BackupData\Models\DataBackup;
use Vendor\BackupData\Services\DatabaseBackupService;

class SendDatabaseBackup extends Command
{
    protected $signature = 'backup:send';

    protected $description = 'Générer une sauvegarde de la base de données et l\'envoyer par email';

    /**
     * Taille maximale (octets) au-delà de laquelle l'archive n'est pas jointe
     * à l'email — la plupart des serveurs SMTP refusent les grosses pièces
     * jointes. Le backup reste généré et consultable dans Paramètres >
     * Sauvegarde des données ; seul l'envoi par email est adapté.
     */
    private const MAX_ATTACHMENT_BYTES = 20 * 1024 * 1024; // 20 Mo

    private function isSmtpConfigured(): bool
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $mailer = config('mail.default');

        return $mailer === 'smtp' && !empty($host) && !empty($port);
    }

    public function handle()
    {
        if (!$this->isSmtpConfigured()) {
            $this->warn('⚠️  SMTP non configuré. La sauvegarde ne peut pas être envoyée par email.');
            $this->info('💡  Configurez SMTP dans Paramètres.');
            Log::warning('Sauvegarde annulée: SMTP non configuré');
            return Command::SUCCESS;
        }

        $settings = Setting::first();
        $recipients = $this->resolveRecipients($settings);

        if (empty($recipients)) {
            $this->warn('⚠️  Aucun destinataire valide (configurez-les dans Paramètres > Planification automatique des rapports).');
            return 0;
        }

        $this->info('🔄  Génération de la sauvegarde...');

        try {
            @set_time_limit(0);

            $service = new DatabaseBackupService();
            $meta = $service->create();

            $backup = DataBackup::create(array_merge($meta, [
                'status'          => 'completed',
                'created_by'      => null,
                'created_by_name' => 'Planification automatique',
            ]));

            $this->info("✅  Sauvegarde générée: {$meta['filename']} ({$meta['tables_count']} tables, {$meta['rows_count']} lignes, "
                . number_format($meta['size_bytes'] / 1024 / 1024, 2) . ' Mo)');
        } catch (\Throwable $e) {
            $this->error('❌  Erreur génération sauvegarde: ' . $e->getMessage());
            Log::error('Erreur génération sauvegarde (cron): ' . $e->getMessage());

            DataBackup::create([
                'filename'        => 'backup_' . now()->format('Y-m-d_H-i-s') . '.zip',
                'path'            => '',
                'size_bytes'      => 0,
                'tables_count'    => 0,
                'rows_count'      => 0,
                'status'          => 'failed',
                'error'           => $e->getMessage(),
                'created_by'      => null,
                'created_by_name' => 'Planification automatique',
            ]);

            return 1;
        }

        $rhEmail = implode(', ', $recipients);
        $this->info("📤  Envoi à {$rhEmail}...");

        try {
            $attachTooBig = $meta['size_bytes'] > self::MAX_ATTACHMENT_BYTES;

            $mail = new DatabaseBackupMail($backup, $attachTooBig);

            if (!$attachTooBig) {
                $absolutePath = Storage::disk('local')->path($meta['path']);
                $mail->attach($absolutePath, [
                    'as'   => $meta['filename'],
                    'mime' => 'application/zip',
                ]);
            } else {
                $this->warn('⚠️  Archive trop volumineuse (> 20 Mo) : envoyée sans pièce jointe, disponible dans Paramètres > Sauvegarde des données.');
            }

            Mail::to($recipients)->send($mail);

            ScheduledNotification::where('command', 'backup:send')->update([
                'last_run_at' => now(),
                'last_status' => 'Envoyée à ' . $rhEmail,
            ]);

            $this->info("✅  Sauvegarde envoyée à {$rhEmail}");
            Log::info('Sauvegarde envoyée par email à ' . $rhEmail);
        } catch (\Throwable $e) {
            $this->error('❌  Erreur envoi email: ' . $e->getMessage());
            Log::error('Erreur envoi sauvegarde par email: ' . $e->getMessage());

            ScheduledNotification::where('command', 'backup:send')->update([
                'last_run_at' => now(),
                'last_status' => 'Erreur envoi: ' . $e->getMessage(),
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * Résout la liste des destinataires : tâche planifiée (Paramètres) puis
     * repli sur l'email RH des paramètres généraux.
     */
    private function resolveRecipients($settings): array
    {
        $job = ScheduledNotification::where('command', 'backup:send')->first();
        $recipients = $job && is_array($job->recipients) ? $job->recipients : [];

        if (empty($recipients) && !empty($settings->email)) {
            $recipients = [$settings->email];
        }

        return array_values(array_filter($recipients, function ($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        }));
    }
}

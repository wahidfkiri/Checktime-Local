<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\Setting;
use App\Support\EmailTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Vendor\BackupData\Models\DataBackup;

/**
 * Éditeur visuel (Vvveb, vendorisé dans public/vendor/vvveb) du contenu des
 * emails envoyés par les 4 tâches de /settings qui envoient réellement un
 * email (la synchronisation horaire des pointages n'en envoie aucun, elle
 * n'apparaît pas ici).
 *
 * Fonctionnement : le HTML courant (personnalisé s'il existe, sinon un
 * aperçu généré à partir de données d'exemple) est écrit dans un fichier
 * public réel sous public/email-templates/ — Vvveb ne sait éditer qu'un
 * vrai fichier chargé dans un iframe, pas une chaîne en mémoire. À
 * l'enregistrement, ce même fichier est réécrit ET le HTML est stocké dans
 * la table email_templates, qui est la source utilisée par les Mailables
 * (voir EmailTemplateRenderer) au moment de l'envoi réel.
 */
class EmailTemplateController extends Controller
{
    private const TEMPLATES = [
        'attendance:send-weekly-reports' => [
            'label' => 'Rapport hebdomadaire de présence (employés)',
            'view'  => 'emails.weekly-attendance-employee-pdf',
        ],
        'attendance:send-weekly-rh-reports' => [
            'label' => 'Rapport hebdomadaire RH',
            'view'  => 'emails.weekly-rh-attendance-report',
        ],
        'reports:send-monthly-rh' => [
            'label' => 'Rapport mensuel RH (PDF)',
            'view'  => 'emails.monthly-rh-report-email',
        ],
        'backup:send' => [
            'label' => 'Sauvegarde de la base de données',
            'view'  => 'emails.database-backup',
        ],
    ];

    public function index()
    {
        $customized = EmailTemplate::whereNotNull('html')
            ->where('html', '!=', '')
            ->pluck('updated_at', 'command');

        $templates = [];
        foreach (self::TEMPLATES as $command => $meta) {
            $templates[] = [
                'command'    => $command,
                'label'      => $meta['label'],
                'customized' => $customized->has($command),
                'updated_at' => $customized->get($command),
            ];
        }

        return view('settings.email-templates-index', compact('templates'));
    }

    public static function isEditable(string $command): bool
    {
        return array_key_exists($command, self::TEMPLATES);
    }

    private function meta(string $command): array
    {
        abort_unless(self::isEditable($command), 404);

        return self::TEMPLATES[$command];
    }

    private function slug(string $command): string
    {
        return Str::slug(str_replace(':', '-', $command));
    }

    private function workingFileRelativePath(string $command): string
    {
        return 'email-templates/' . $this->slug($command) . '.html';
    }

    private function workingFileAbsolutePath(string $command): string
    {
        return public_path($this->workingFileRelativePath($command));
    }

    private function mediaDirRelativePath(string $command): string
    {
        return 'email-templates/media/' . $this->slug($command);
    }

    private function mediaDirAbsolutePath(string $command): string
    {
        return public_path($this->mediaDirRelativePath($command));
    }

    public function edit(string $command)
    {
        $meta = $this->meta($command);

        $template = EmailTemplate::where('command', $command)->first();
        $html = ($template && trim((string) $template->html) !== '')
            ? $template->html
            : EmailTemplateRenderer::seed($meta['view'], $this->sampleData($command));

        $dir = dirname($this->workingFileAbsolutePath($command));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->workingFileAbsolutePath($command), $html);

        $slug = $this->slug($command);

        return view('settings.email-template-editor', [
            'label'         => $meta['label'],
            'pageName'      => $slug,
            'fileUrl'       => asset($this->workingFileRelativePath($command)),
            'saveUrl'       => route('settings.email-templates.save', $command),
            'scanUrl'       => route('settings.email-templates.scan-media', $command),
            'uploadUrl'     => route('settings.email-templates.upload-media', $command),
            'mediaBaseUrl'  => asset($this->mediaDirRelativePath($command)) . '/',
            'hasCustom'     => (bool) ($template && trim((string) $template->html) !== ''),
            'resetUrl'      => route('settings.email-templates.reset', $command),
            'backUrl'       => route('settings.email-templates.index'),
        ]);
    }

    public function save(Request $request, string $command)
    {
        $this->meta($command);

        $html = (string) $request->input('html', '');

        if (trim($html) === '') {
            return response("Html content is empty!", 500);
        }

        if (strlen($html) > 2 * 1024 * 1024) {
            return response("Fichier trop volumineux (max 2 Mo).", 500);
        }

        $dir = dirname($this->workingFileAbsolutePath($command));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->workingFileAbsolutePath($command), $html);

        EmailTemplate::updateOrCreate(['command' => $command], ['html' => $html]);

        Log::info("Template email personnalisé enregistré pour {$command}");

        return response("File saved '" . $this->slug($command) . ".html'");
    }

    public function reset(string $command)
    {
        $this->meta($command);

        EmailTemplate::where('command', $command)->delete();

        return response()->json(['success' => true, 'message' => 'Template réinitialisé au modèle par défaut.']);
    }

    /**
     * Liste les médias déjà déposés pour ce template (format attendu par le
     * gestionnaire de médias de Vvveb — voir libs/media/media.js scan()).
     */
    public function scanMedia(string $command)
    {
        $this->meta($command);

        $dir = $this->mediaDirAbsolutePath($command);
        $items = [];

        if (is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if ($f === '.' || $f === '..' || !is_file($dir . DIRECTORY_SEPARATOR . $f)) {
                    continue;
                }
                $items[] = [
                    'name' => $f,
                    'type' => 'file',
                    'path' => '/' . $f,
                    'size' => filesize($dir . DIRECTORY_SEPARATOR . $f),
                ];
            }
        }

        return response()->json([
            'name'  => '',
            'type'  => 'folder',
            'path'  => '',
            'items' => $items,
        ]);
    }

    /**
     * Reçoit une image déposée depuis l'éditeur (logo, etc.) — même contrat
     * que upload.php de Vvveb (champ "onlyFilename" => renvoie juste le nom
     * de fichier en texte brut).
     */
    public function uploadMedia(Request $request, string $command)
    {
        $this->meta($command);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,ico|max:5120',
        ]);

        $dir = $this->mediaDirAbsolutePath($command);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $request->file('file');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $base . '.' . $extension;

        $i = 1;
        while (file_exists($dir . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $base . '-' . $i . '.' . $extension;
            $i++;
        }

        $file->move($dir, $filename);

        if ($request->boolean('onlyFilename')) {
            return response($filename);
        }

        return response('/' . $filename);
    }

    /**
     * Données d'exemple utilisées uniquement pour afficher un aperçu fidèle
     * dans l'éditeur (première ouverture, sans template personnalisé
     * enregistré) — jamais utilisées pour un envoi réel.
     */
    private function sampleData(string $command): array
    {
        $client = Setting::company();

        switch ($command) {
            case 'attendance:send-weekly-reports':
                return [
                    'employeeName' => 'Jean Dupont',
                    'clientName'   => $client->name,
                    'startDate'    => now()->startOfWeek()->format('d/m/Y'),
                    'endDate'      => now()->startOfWeek()->addDays(4)->format('d/m/Y'),
                    'stats'        => [
                        'present' => 4, 'absent' => 1, 'presence_rate' => 80,
                        'late' => 1, 'early_leave' => 0, 'half_day' => 0,
                        'mission' => 0, 'leave' => 0, 'ponctualite_rate' => 75,
                    ],
                    'observations' => 'Retard 15 min le 12/01',
                ];

            case 'attendance:send-weekly-rh-reports':
                return [
                    'clientName'       => $client->name,
                    'startDate'        => now()->startOfWeek()->format('d/m/Y'),
                    'endDate'          => now()->startOfWeek()->addDays(4)->format('d/m/Y'),
                    'totalEmployees'   => Employee::count() ?: 25,
                    'avgPresenceRate'  => 88.5,
                    'totalDepartments' => 4,
                ];

            case 'reports:send-monthly-rh':
                return [
                    'data' => [
                        'month_name'    => now()->locale('fr')->monthName,
                        'year'          => now()->year,
                        'client'        => $client,
                        'global_stats'  => [
                            'total_employees'        => Employee::count() ?: 25,
                            'avg_presence_rate'       => 88.5,
                            'avg_ponctualite_rate'    => 91.2,
                            'total_presence_absent'   => 12,
                        ],
                        'pdf_filename'  => 'rapport_mensuel_rh.pdf',
                        'period_days'   => 22,
                        'start_date'    => now()->startOfMonth()->format('d/m/Y'),
                        'end_date'      => now()->endOfMonth()->format('d/m/Y'),
                        'generated_at'  => now(),
                    ],
                ];

            case 'backup:send':
                $backup = new DataBackup();
                $backup->filename = 'backup_2026_01_01.zip';
                $backup->tables_count = 42;
                $backup->rows_count = 125000;
                $backup->size_human = '18.4 Mo';

                return [
                    'backup'            => $backup,
                    'attachmentOmitted' => false,
                ];

            default:
                return [];
        }
    }
}

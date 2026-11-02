<?php

namespace App\Console\Commands;

use App\Services\CheckTimeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Diagnostic en lecture seule : interroge l'API biométrique avec l'URL et la clé
 * enregistrées dans les paramètres, et montre quels champs date (date de
 * création, embauche, mise à jour…) sont réellement renvoyés pour les employés.
 */
class InspectApiEmployees extends Command
{
    protected $signature = 'employees:api-inspect
                            {emp_code? : Code employé à afficher en détail (sinon : résumé des champs)}
                            {--pages=3 : Nombre maximum de pages à lire}';

    protected $description = "Affiche les champs (dont les dates de création/embauche) renvoyés par l'API biométrique pour les employés";

    public function handle()
    {
        $baseUrl = rtrim(CheckTimeService::getConfigBaseUrl(), '/');
        $token   = CheckTimeService::getConfigToken();

        if (!$token) {
            $this->error("Aucune clé d'accès configurée (Paramètres).");
            return 1;
        }

        $this->info("API : {$baseUrl}/personnel/api/employees/");

        $wanted   = $this->argument('emp_code');
        $employees = [];

        for ($page = 1; $page <= max(1, (int) $this->option('pages')); $page++) {
            $response = Http::withHeaders(['Authorization' => 'Token ' . $token, 'Accept' => 'application/json'])
                ->timeout(60)
                ->get($baseUrl . '/personnel/api/employees/', array_filter([
                    'page'     => $page,
                    'limit'    => 300,
                    'emp_code' => $wanted,
                ]));

            if ($response->failed()) {
                $this->error("Page {$page} : HTTP " . $response->status());
                break;
            }

            $json  = $response->json();
            $batch = $json['data'] ?? (is_array($json) && array_is_list($json) ? $json : []);
            if (empty($batch)) {
                break;
            }
            $employees = array_merge($employees, $batch);

            if ($wanted || empty($json['next'] ?? null)) {
                break;
            }
        }

        if (empty($employees)) {
            $this->warn('Aucun employé renvoyé par l\'API.');
            return 1;
        }

        if ($wanted) {
            $employee = collect($employees)->first(fn ($e) => (string) ($e['emp_code'] ?? '') === (string) $wanted) ?? $employees[0];
            $this->info("Employé {$wanted} — tous les champs renvoyés :");
            foreach ($employee as $key => $value) {
                $this->line(sprintf('  %-22s %s', $key, $this->short($value)));
            }
            $this->line('');
            $dates = $this->dateFields($employee);
            $this->info($dates
                ? 'Champs de type date : ' . implode(', ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($dates), $dates))
                : "Aucun champ de type date pour cet employé.");
            return 0;
        }

        $this->info(count($employees) . ' employé(s) lus. Champs date/heure trouvés :');
        $stats = [];
        foreach ($employees as $e) {
            foreach ($e as $key => $value) {
                if ($this->looksLikeDateKey($key)) {
                    $stats[$key]['total'] = ($stats[$key]['total'] ?? 0) + 1;
                    if ($value !== null && $value !== '') {
                        $stats[$key]['filled'] = ($stats[$key]['filled'] ?? 0) + 1;
                        $stats[$key]['sample'] ??= $this->short($value);
                    }
                }
            }
        }

        if (!$stats) {
            $this->warn("L'API ne renvoie aucun champ qui ressemble à une date (create/hire/update/time/date).");
        }
        foreach ($stats as $key => $s) {
            $this->line(sprintf('  %-20s renseigné pour %d/%d   exemple: %s', $key, $s['filled'] ?? 0, $s['total'], $s['sample'] ?? '-'));
        }
        $this->line('');
        $this->line('Tous les champs de la 1re fiche : ' . implode(', ', array_keys($employees[0])));

        return 0;
    }

    private function looksLikeDateKey(string $key): bool
    {
        return (bool) preg_match('/(date|time|create|hire|join|entry|start|update|birth)/i', $key);
    }

    private function dateFields(array $employee): array
    {
        return array_filter(
            array_filter($employee, fn ($v, $k) => $this->looksLikeDateKey($k), ARRAY_FILTER_USE_BOTH),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    private function short($value): string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return mb_strimwidth((string) ($value === null ? 'null' : $value), 0, 110, '…');
    }
}

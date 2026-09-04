<?php

namespace Vendor\Journalisation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vendor\Journalisation\Support\ActivityLogger;

/**
 * Journalise les actions applicatives qui ne passent pas par un modèle Eloquent :
 * synchronisations, imports, exports, sauvegardes, téléchargements.
 *
 * Le repérage est générique (nom de route + en-tête Content-Disposition), ce qui
 * couvre tous les modules de packages/vendor sans toucher aux contrôleurs.
 *
 * Une requête ne produit jamais plus d'une entrée : la correspondance par nom de
 * route est prioritaire, le repli « pièce jointe » ne sert que si aucun motif ne
 * correspond.
 */
class LogRequestActivity
{
    /**
     * Motifs recherchés dans le nom de route => [action, libellé].
     *
     * L'ordre compte : le premier motif trouvé gagne (d'où les noms complets
     * du module backup_data avant les motifs génériques export/download).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const ROUTE_ACTIONS = [
        // Sauvegardes : noms complets d'abord, la route de création s'appelle
        // « backup-data.export » et serait sinon prise pour un simple export.
        'backup-data.export'      => ['backup', 'Création d’une sauvegarde de la base'],
        'backup-data.download'    => ['export', 'Téléchargement d’une sauvegarde'],

        // Exports avant synchros : « export-pdf-sync » est un export, pas une synchro.
        'export'                  => ['export', 'Export'],
        'download'                => ['export', 'Téléchargement'],
        'reports.custom.generate' => ['export', 'Génération d’un rapport personnalisé'],

        'force-sync'              => ['sync',   'Synchronisation forcée'],
        'sync'                    => ['sync',   'Synchronisation'],
        'reset'                   => ['sync',   'Réinitialisation puis synchronisation'],
        'import'                  => ['import', 'Import de données'],
        'justify'                 => ['update', 'Justification d’un retard'],
    ];

    /**
     * Actions dont l'exécution modifie massivement des modèles observés :
     * on coupe les observers le temps de la requête pour n'avoir qu'une entrée.
     *
     * @var array<int, string>
     */
    private const BULK_ACTIONS = ['sync', 'import', 'backup'];

    public function handle(Request $request, Closure $next)
    {
        $match = $this->matchRoute($request);

        if ($match && in_array($match[0], self::BULK_ACTIONS, true)) {
            ActivityLogger::suppressModelLogs();
        }

        try {
            /** @var Response $response */
            $response = $next($request);
        } finally {
            ActivityLogger::suppressModelLogs(false);
        }

        try {
            if (auth()->check()) {
                // Un export en GET n'est journalisé que s'il renvoie réellement un
                // fichier : sinon la route ne fait qu'afficher la page d'export.
                if ($match && $request->isMethod('GET') && !$this->isFileResponse($response)) {
                    $match = null;
                }

                $match ? $this->logRouteAction($match, $response) : $this->logDownload($response);
            }
        } catch (\Throwable $e) {
            // Ne jamais bloquer la réponse pour un log.
        }

        return $response;
    }

    /**
     * Cherche le motif correspondant au nom de route de la requête.
     *
     * @return array{0: string, 1: string}|null  [action, libellé]
     */
    private function matchRoute(Request $request): ?array
    {
        $name = optional($request->route())->getName();

        if (!$name) {
            return null;
        }

        foreach (self::ROUTE_ACTIONS as $pattern => $action) {
            if (!str_contains($name, $pattern)) {
                continue;
            }

            // Les synchronisations / imports / actions sont toujours des POST :
            // en GET il s'agit d'une page ou d'un sondage d'état (sync-status),
            // qui n'a pas à polluer le journal. Les exports restent loggés en GET.
            if ($request->isMethod('GET') && !in_array($action[0], ['export'], true)) {
                return null;
            }

            return $action;
        }

        return null;
    }

    /**
     * Entrée liée à une route identifiée (synchro, export, sauvegarde…).
     *
     * @param array{0: string, 1: string} $match
     */
    private function logRouteAction(array $match, Response $response): void
    {
        [$action, $label] = $match;

        $status = $response->getStatusCode();
        $failed = $status >= 400;

        $properties = array_filter([
            'status'   => $status,
            'filename' => $this->filenameFrom($response),
            'resultat' => $this->summaryFrom($response),
        ], fn ($value) => $value !== null && $value !== []);

        ActivityLogger::log(
            $action,
            ($failed ? 'Échec — ' : '') . $label,
            null,
            $properties
        );
    }

    /**
     * Repli : toute réponse renvoyant un fichier en pièce jointe est un export.
     */
    private function logDownload(Response $response): void
    {
        $filename = $this->filenameFrom($response);

        if ($filename === null && !$this->isAttachment($response)) {
            return;
        }

        ActivityLogger::log(
            'export',
            'Export / téléchargement' . ($filename ? ' : ' . $filename : ''),
            null,
            array_filter(['filename' => $filename])
        );
    }

    /**
     * La réponse transporte-t-elle un fichier (pièce jointe ou contenu non HTML) ?
     */
    private function isFileResponse(Response $response): bool
    {
        if ($this->isAttachment($response)) {
            return true;
        }

        $type = (string) $response->headers->get('Content-Type');

        return $type !== '' && !str_contains($type, 'text/html');
    }

    private function isAttachment(Response $response): bool
    {
        $disposition = $response->headers->get('Content-Disposition');

        return $disposition !== null && stripos($disposition, 'attachment') !== false;
    }

    private function filenameFrom(Response $response): ?string
    {
        $disposition = $response->headers->get('Content-Disposition');

        if ($disposition && preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $m)) {
            return trim(urldecode($m[1]));
        }

        return null;
    }

    /**
     * Récupère un résumé lisible depuis une réponse JSON (message, compteurs…).
     *
     * @return array<string, mixed>|null
     */
    private function summaryFrom(Response $response): ?array
    {
        $content = $response->getContent();

        if (!is_string($content) || $content === '' || !str_starts_with(ltrim($content), '{')) {
            return null;
        }

        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            return null;
        }

        $summary = array_filter([
            'message' => is_string($payload['message'] ?? null) ? $payload['message'] : null,
            'stats'   => is_array($payload['stats'] ?? null) ? $payload['stats'] : null,
        ]);

        return $summary ?: null;
    }
}

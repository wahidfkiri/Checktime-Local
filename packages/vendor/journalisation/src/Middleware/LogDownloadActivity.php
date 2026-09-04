<?php

namespace Vendor\Journalisation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vendor\Journalisation\Support\ActivityLogger;

/**
 * Journalise tout téléchargement de fichier (export PDF/Excel/ZIP, etc.).
 *
 * Détection générique : la réponse porte un en-tête
 * "Content-Disposition: attachment". Couvre ainsi tous les exports de
 * l'application sans modifier chaque contrôleur.
 */
class LogDownloadActivity
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        try {
            if (!auth()->check()) {
                return $response;
            }

            $disposition = $response->headers->get('Content-Disposition');
            if ($disposition && stripos($disposition, 'attachment') !== false) {
                $filename = $this->extractFilename($disposition);

                ActivityLogger::log(
                    'export',
                    'Export / téléchargement' . ($filename ? ' : ' . $filename : ''),
                    null,
                    array_filter(['filename' => $filename])
                );
            }
        } catch (\Throwable $e) {
            // Ne jamais bloquer la réponse pour un log.
        }

        return $response;
    }

    private function extractFilename(string $disposition): ?string
    {
        if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $m)) {
            return trim(urldecode($m[1]));
        }
        return null;
    }
}

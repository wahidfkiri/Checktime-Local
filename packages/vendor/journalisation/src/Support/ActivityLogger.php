<?php

namespace Vendor\Journalisation\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Vendor\Journalisation\Models\ActivityLog;

/**
 * Point d'entrée unique pour enregistrer une activité utilisateur.
 *
 * La journalisation ne doit JAMAIS casser l'application : toute erreur est
 * silencieusement ignorée (l'audit est secondaire par rapport à l'action).
 */
class ActivityLogger
{
    /** Évite de re-tester l'existence de la table à chaque appel. */
    private static ?bool $tableExists = null;

    /**
     * Quand true, les observers de modèles ne journalisent plus.
     *
     * Utilisé pendant les synchronisations / imports : ces opérations créent ou
     * modifient des centaines de lignes, on veut une seule entrée « Synchronisation »
     * plutôt qu'une entrée par employé.
     */
    private static bool $modelLogsSuppressed = false;

    /**
     * Active / désactive la journalisation automatique des modèles.
     */
    public static function suppressModelLogs(bool $suppress = true): void
    {
        self::$modelLogsSuppressed = $suppress;
    }

    public static function modelLogsSuppressed(): bool
    {
        return self::$modelLogsSuppressed;
    }

    /**
     * @param  string       $action       login|logout|create|update|delete|export|…
     * @param  string|null  $description  Texte lisible.
     * @param  Model|null   $subject      Modèle concerné (optionnel).
     * @param  array        $properties   Détails additionnels (colonnes modifiées…).
     * @param  mixed        $user         Utilisateur explicite (sinon auth()).
     */
    public static function log(
        string $action,
        ?string $description = null,
        $subject = null,
        array $properties = [],
        $user = null
    ): void {
        try {
            if (!self::tableExists()) {
                return;
            }

            $user = $user ?: Auth::user();
            $request = request();

            ActivityLog::create([
                'user_id'      => $user->id ?? null,
                'user_name'    => self::userName($user),
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'method'       => $request?->method(),
                'url'          => $request ? $request->fullUrl() : null,
                'route'        => optional($request?->route())->getName(),
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request?->userAgent(),
                'properties'   => !empty($properties) ? $properties : null,
            ]);
        } catch (\Throwable $e) {
            // Silencieux : on ne bloque jamais l'application pour un log.
        }
    }

    /**
     * Récupère un nom lisible pour l'utilisateur (le champ name est chiffré).
     */
    private static function userName($user): ?string
    {
        if (!$user) {
            return null;
        }
        try {
            return $user->name ?? ($user->email ?? null);
        } catch (\Throwable $e) {
            return $user->email ?? null;
        }
    }

    private static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('activity_logs');
            } catch (\Throwable $e) {
                self::$tableExists = false;
            }
        }
        return self::$tableExists;
    }
}

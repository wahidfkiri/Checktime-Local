<?php

namespace Vendor\Journalisation\Observers;

use Illuminate\Database\Eloquent\Model;
use Vendor\Journalisation\Support\ActivityLogger;

/**
 * Observer générique : journalise create / update / delete des modèles suivis.
 */
class ModelActivityObserver
{
    /** Colonnes ignorées pour décider si une modification vaut la peine d'être journalisée. */
    private const IGNORED_ON_UPDATE = [
        'updated_at', 'created_at', 'remember_token',
        'last_login_at', 'last_seen_at', 'last_activity',
    ];

    /** Colonnes sensibles dont on ne stocke jamais la valeur. */
    private const SENSITIVE = ['password', 'remember_token', 'name', 'api_token', 'value'];

    public function created(Model $model): void
    {
        if ($this->shouldSkip()) {
            return;
        }
        ActivityLogger::log(
            'create',
            'Création ' . $this->label($model),
            $model,
            ['attributes' => $this->safeKeys($model->getAttributes())]
        );
    }

    public function updated(Model $model): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        $changed = array_keys($model->getChanges());
        $meaningful = array_diff($changed, self::IGNORED_ON_UPDATE);

        // Rien d'intéressant n'a changé (ex. remember_token au login) → on ignore.
        if (empty($meaningful)) {
            return;
        }

        ActivityLogger::log(
            'update',
            'Modification ' . $this->label($model),
            $model,
            ['changed' => array_values($meaningful)]
        );
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldSkip()) {
            return;
        }
        ActivityLogger::log(
            'delete',
            'Suppression ' . $this->label($model),
            $model
        );
    }

    private function label(Model $model): string
    {
        return class_basename($model) . ' #' . $model->getKey();
    }

    /**
     * Ne pas journaliser depuis la console (migrations, seeders, tinker, jobs),
     * ni pendant une synchronisation / un import (une seule entrée globale suffit,
     * cf. LogRequestActivity).
     */
    private function shouldSkip(): bool
    {
        return app()->runningInConsole() || ActivityLogger::modelLogsSuppressed();
    }

    /**
     * Ne conserve que les noms de colonnes, en excluant les colonnes sensibles.
     *
     * @return array<int, string>
     */
    private function safeKeys(array $attributes): array
    {
        return array_values(array_diff(array_keys($attributes), self::SENSITIVE));
    }
}

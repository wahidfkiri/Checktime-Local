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

        $properties = ['changed' => array_values($meaningful)];

        // Valeurs avant / après : c'est le détail qui rend le journal exploitable
        // lors d'un audit (« qui a désactivé cet employé, et depuis quelle valeur »).
        $values = $this->changeValues($model, $meaningful);

        if ($values) {
            $properties['changes'] = $values;
        }

        ActivityLogger::log(
            'update',
            'Modification ' . $this->label($model),
            $model,
            $properties
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

    /**
     * Libellé métier du modèle, ex. « Employé #42 » plutôt que « Employee #42 ».
     */
    private function label(Model $model): string
    {
        $short = class_basename($model);

        return (\Vendor\Journalisation\Models\ActivityLog::SUBJECT_LABELS[$short] ?? $short)
            . ' #' . $model->getKey();
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
     * Valeurs avant / après pour les colonnes modifiées non sensibles.
     *
     * Les valeurs brutes sont utilisées (getRawOriginal / getChanges) afin de ne
     * jamais déclencher un cast ou un accesseur — certains champs sont chiffrés
     * et lever une exception ici casserait l'enregistrement de l'activité.
     *
     * @param  array<int, string> $fields
     * @return array<string, array{avant: string, apres: string}>
     */
    private function changeValues(Model $model, array $fields): array
    {
        $changes = $model->getChanges();
        $values  = [];

        foreach ($fields as $field) {
            if (in_array($field, self::SENSITIVE, true)) {
                continue;
            }

            $values[$field] = [
                'avant' => $this->readable($model->getRawOriginal($field)),
                'apres' => $this->readable($changes[$field] ?? null),
            ];
        }

        return $values;
    }

    /**
     * Représentation courte et lisible d'une valeur de colonne.
     */
    private function readable($value): string
    {
        if ($value === null || $value === '') {
            return '(vide)';
        }

        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }

        // Une colonne datée non encore persistée porte un objet Carbon : on le
        // formate comme la valeur brute d'origine, sinon les deux côtés du
        // « avant → après » ne sont pas comparables à l'œil.
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (!is_scalar($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $value = (string) $value;

        // Le journal n'est pas une copie de la base : on borne la taille stockée.
        return mb_strlen($value) > 120 ? mb_substr($value, 0, 120) . '…' : $value;
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

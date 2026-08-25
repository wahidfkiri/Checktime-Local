<?php

namespace App\Models;

use App\Reports\PresencePonctualiteColumns;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    protected $fillable = [
        'report_key', 'name', 'description', 'columns', 'options', 'is_default', 'created_by',
    ];

    protected $casts = [
        'columns' => 'array',
        'options' => 'array',
        'is_default' => 'boolean',
    ];

    public function scopeForReport($query, string $reportKey = PresencePonctualiteColumns::REPORT_KEY)
    {
        return $query->where('report_key', $reportKey);
    }

    /**
     * Colonnes du modèle, filtrées/réordonnées selon le catalogue actuel
     * (au cas où une colonne aurait été retirée du catalogue depuis).
     */
    public function resolvedColumns(): array
    {
        $columns = PresencePonctualiteColumns::sanitize($this->columns ?? []);
        return !empty($columns) ? $columns : PresencePonctualiteColumns::defaultKeys();
    }

    public function resolvedOptions(): array
    {
        return PresencePonctualiteColumns::normalizeOptions($this->options);
    }

    /**
     * Fait de ce modèle le modèle par défaut pour son rapport (et retire
     * ce statut à tous les autres modèles du même rapport).
     */
    public function markAsDefault(): void
    {
        static::forReport($this->report_key)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    /**
     * Garantit qu'un modèle par défaut existe pour ce rapport ; le crée
     * (reproduisant l'ancien tableau figé) si aucun n'existe encore.
     */
    public static function ensureDefaultFor(string $reportKey = PresencePonctualiteColumns::REPORT_KEY): self
    {
        $existing = static::forReport($reportKey)->orderByDesc('is_default')->orderBy('id')->first();
        if ($existing) {
            return $existing;
        }

        return static::create([
            'report_key' => $reportKey,
            'name' => 'Modèle standard',
            'description' => 'Modèle par défaut reproduisant le tableau historique.',
            'columns' => PresencePonctualiteColumns::defaultKeys(),
            'options' => PresencePonctualiteColumns::normalizeOptions(null),
            'is_default' => true,
        ]);
    }

    /**
     * Résout le modèle à utiliser pour un export : le modèle demandé s'il
     * existe pour ce rapport, sinon le modèle par défaut.
     */
    public static function resolveFor($templateId, string $reportKey = PresencePonctualiteColumns::REPORT_KEY): self
    {
        if ($templateId) {
            $template = static::forReport($reportKey)->find($templateId);
            if ($template) {
                return $template;
            }
        }

        return static::ensureDefaultFor($reportKey);
    }
}

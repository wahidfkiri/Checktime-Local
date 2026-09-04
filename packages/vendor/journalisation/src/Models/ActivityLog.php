<?php

namespace Vendor\Journalisation\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'method',
        'url',
        'route',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Libellés lisibles des actions.
     */
    public const ACTION_LABELS = [
        'login'        => 'Connexion',
        'logout'       => 'Déconnexion',
        'login_failed' => 'Échec de connexion',
        'create'       => 'Création',
        'update'       => 'Modification',
        'delete'       => 'Suppression',
        'export'       => 'Export / Téléchargement',
    ];

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Couleur de badge Bootstrap selon l'action.
     */
    public function getActionColorAttribute(): string
    {
        return [
            'login'        => 'success',
            'logout'       => 'secondary',
            'login_failed' => 'danger',
            'create'       => 'primary',
            'update'       => 'warning',
            'delete'       => 'danger',
            'export'       => 'info',
        ][$this->action] ?? 'dark';
    }

    /**
     * Nom court du modèle concerné (sans namespace).
     */
    public function getSubjectShortAttribute(): ?string
    {
        return $this->subject_type ? class_basename($this->subject_type) : null;
    }
}

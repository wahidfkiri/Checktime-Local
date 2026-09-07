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
        'sync'         => 'Synchronisation',
        'import'       => 'Import',
        'backup'       => 'Sauvegarde',
    ];

    /**
     * Clés de `properties` traitées explicitement par getDetailItemsAttribute().
     * Toute autre clé est affichée telle quelle, pour ne rien perdre.
     */
    private const HANDLED_PROPERTY_KEYS = [
        'changed', 'changes', 'attributes', 'elements', 'filename', 'status', 'resultat',
    ];

    /**
     * Nom métier des modèles journalisés (class_basename => libellé français).
     * Aligne l'affichage sur le vocabulaire de l'application plutôt que sur les
     * noms de classes PHP, illisibles pour un auditeur non technique.
     */
    public const SUBJECT_LABELS = [
        'Employee'              => 'Employé',
        'Department'            => 'Département',
        'Leave'                 => 'Congé',
        'LeaveType'             => 'Type de congé',
        'Mission'               => 'Mission',
        'EmployeePermission'    => 'Autorisation de sortie',
        'EmployeeSchedule'      => 'Planning employé',
        'Holiday'               => 'Jour férié',
        'Device'                => 'Appareil biométrique',
        'WorkHourType'          => "Type d'heures",
        'ScheduleRotation'      => 'Rotation de planning',
        'Zone'                  => 'Zone',
        'User'                  => 'Utilisateur',
        'Setting'               => 'Paramètre',
        'ReportTemplate'        => 'Modèle de rapport',
        'ReportSetting'         => 'Réglage de rapport',
        'AccessConfig'          => "Configuration d'accès",
        'Client'                => 'Client',
        'ClientUser'            => 'Utilisateur client',
        'DailyPlanning'         => 'Planning journalier',
        'ScheduledNotification' => 'Notification planifiée',
        'Signataire'            => 'Signataire',
        'SignatairePoste'       => 'Poste de signataire',
        'Role'                  => 'Rôle',
        'Permission'            => 'Permission',
        'DataBackup'            => 'Sauvegarde de données',
    ];

    /**
     * Libellés français des colonnes citées dans `properties`.
     * Les colonnes absentes de cette table sont simplement « humanisées »
     * (underscores remplacés, première lettre en majuscule).
     */
    public const FIELD_LABELS = [
        'emp_code'      => 'Matricule',
        'first_name'    => 'Prénom',
        'last_name'     => 'Nom',
        'email'         => 'E-mail',
        'phone'         => 'Téléphone',
        'department_id' => 'Département',
        'position'      => 'Poste',
        'hire_date'     => "Date d'embauche",
        'is_active'     => 'Actif',
        'active'        => 'Actif',
        'status'        => 'Statut',
        'start_date'    => 'Date de début',
        'end_date'      => 'Date de fin',
        'start_time'    => 'Heure de début',
        'end_time'      => 'Heure de fin',
        'time'          => 'Heure',
        'day_of_week'   => 'Jour de la semaine',
        'date'          => 'Date',
        'reason'        => 'Motif',
        'comment'       => 'Commentaire',
        'key'           => 'Clé',
        'value'         => 'Valeur',
        'group'         => 'Groupe',
        'last_sync'     => 'Dernière synchronisation',
        'ip_address'    => 'Adresse IP',
        'serial_number' => 'Numéro de série',
        'zone_id'       => 'Zone',
        'role'          => 'Rôle',
        'roles'         => 'Rôles',
        'permissions'   => 'Permissions',
        'created'       => 'Créés',
        'updated'       => 'Mis à jour',
        'deleted'       => 'Supprimés',
        'skipped'       => 'Ignorés',
        'errors'        => 'Erreurs',
        'total'         => 'Total',
        'created_at'    => 'Date de création',
        'updated_at'    => 'Date de modification',
        'id'            => 'Identifiant',
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
            'sync'         => 'primary',
            'import'       => 'primary',
            'backup'       => 'dark',
        ][$this->action] ?? 'dark';
    }

    /**
     * Icône Bootstrap Icons associée à l'action.
     */
    public function getActionIconAttribute(): string
    {
        return [
            'login'        => 'bi-box-arrow-in-right',
            'logout'       => 'bi-box-arrow-left',
            'login_failed' => 'bi-shield-exclamation',
            'create'       => 'bi-plus-circle',
            'update'       => 'bi-pencil-square',
            'delete'       => 'bi-trash',
            'export'       => 'bi-download',
            'sync'         => 'bi-arrow-repeat',
            'import'       => 'bi-upload',
            'backup'       => 'bi-hdd',
        ][$this->action] ?? 'bi-dot';
    }

    /**
     * Nom court du modèle concerné (sans namespace).
     */
    public function getSubjectShortAttribute(): ?string
    {
        return $this->subject_type ? class_basename($this->subject_type) : null;
    }

    /**
     * Libellé métier de l'élément concerné, ex. « Employé #42 ».
     */
    public function getSubjectLabelAttribute(): ?string
    {
        if (!$this->subject_short) {
            return null;
        }

        $label = self::SUBJECT_LABELS[$this->subject_short] ?? $this->subject_short;

        return $this->subject_id ? $label . ' #' . $this->subject_id : $label;
    }

    /**
     * Chemin de l'URL appelée, sans le domaine (plus lisible en tableau).
     */
    public function getUrlPathAttribute(): ?string
    {
        if (!$this->url) {
            return null;
        }

        $path  = parse_url($this->url, PHP_URL_PATH) ?: $this->url;
        $query = parse_url($this->url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    }

    /**
     * Navigateur et système déduits du User-Agent, ex. « Chrome sur Windows ».
     * L'ordre de test compte : Edge et Opera annoncent aussi « Chrome », et
     * Chrome annonce « Safari ».
     */
    public function getDeviceLabelAttribute(): ?string
    {
        $agent = (string) $this->user_agent;

        if ($agent === '') {
            return null;
        }

        $browser = 'Navigateur inconnu';
        foreach ([
            'Edg'     => 'Edge',
            'OPR'     => 'Opera',
            'Chrome'  => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari'  => 'Safari',
            'curl'    => 'cURL',
        ] as $needle => $name) {
            if (str_contains($agent, $needle)) {
                $browser = $name;
                break;
            }
        }

        $platform = null;
        foreach ([
            'Windows'  => 'Windows',
            'Android'  => 'Android',
            'iPhone'   => 'iOS',
            'iPad'     => 'iPadOS',
            'Mac OS X' => 'macOS',
            'Linux'    => 'Linux',
        ] as $needle => $name) {
            if (str_contains($agent, $needle)) {
                $platform = $name;
                break;
            }
        }

        return $platform ? $browser . ' sur ' . $platform : $browser;
    }

    /**
     * Détails exploitables extraits de `properties`, sous forme
     * [libellé => valeur | liste de valeurs], prêts à l'affichage.
     *
     * @return array<string, string|array<int, string>>
     */
    public function getDetailItemsAttribute(): array
    {
        $props = is_array($this->properties) ? $this->properties : [];
        $items = [];

        // `changes` (valeurs avant / après) prime sur `changed` (noms seuls) ;
        // les entrées antérieures à cet enrichissement n'ont que `changed`.
        if (!empty($props['changes']) && is_array($props['changes'])) {
            $lines = [];

            foreach ($props['changes'] as $field => $change) {
                $before = is_array($change) ? ($change['avant'] ?? '') : '';
                $after  = is_array($change) ? ($change['apres'] ?? '') : (string) $change;

                $lines[] = self::fieldLabel((string) $field) . ' : ' . $before . ' → ' . $after;
            }

            $items['Champs modifiés'] = $lines;
        } elseif (!empty($props['changed']) && is_array($props['changed'])) {
            $items['Champs modifiés'] = self::humanFields($props['changed']);
        }

        if (!empty($props['attributes']) && is_array($props['attributes'])) {
            $items['Champs renseignés'] = self::humanFields($props['attributes']);
        }

        if (!empty($props['elements']) && is_array($props['elements'])) {
            $items['Éléments concernés'] = array_map('strval', $props['elements']);
        }

        if (!empty($props['filename'])) {
            $items['Fichier'] = (string) $props['filename'];
        }

        if (isset($props['status'])) {
            $items['Code HTTP'] = (string) $props['status'];
        }

        $resultat = $props['resultat'] ?? null;

        if (is_array($resultat)) {
            if (!empty($resultat['message'])) {
                $items['Résultat'] = (string) $resultat['message'];
            }

            if (!empty($resultat['stats']) && is_array($resultat['stats'])) {
                foreach ($resultat['stats'] as $key => $value) {
                    if (is_scalar($value)) {
                        $items[self::fieldLabel((string) $key)] = (string) $value;
                    }
                }
            }
        }

        // Clés inconnues : affichées telles quelles plutôt que silencieusement perdues.
        foreach ($props as $key => $value) {
            if (in_array($key, self::HANDLED_PROPERTY_KEYS, true)) {
                continue;
            }

            $items[self::fieldLabel((string) $key)] = is_scalar($value)
                ? (string) $value
                : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $items;
    }

    /**
     * Résumé d'une ligne pour la colonne « Détails » du tableau et les exports.
     */
    public function getDetailsSummaryAttribute(): ?string
    {
        $parts = [];

        foreach ($this->detail_items as $label => $value) {
            $parts[] = $label . ' : ' . (is_array($value) ? implode(', ', $value) : $value);
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * `properties` mis en forme pour l'affichage brut (audit technique).
     */
    public function getPropertiesJsonAttribute(): ?string
    {
        if (empty($this->properties)) {
            return null;
        }

        return (string) json_encode(
            $this->properties,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Traduit une liste de noms de colonnes en libellés lisibles.
     *
     * @param  array<int, mixed> $fields
     * @return array<int, string>
     */
    public static function humanFields(array $fields): array
    {
        return array_values(array_map(
            fn ($field) => self::fieldLabel((string) $field),
            $fields
        ));
    }

    /**
     * Libellé lisible d'une colonne (table de correspondance, sinon humanisation).
     */
    public static function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}

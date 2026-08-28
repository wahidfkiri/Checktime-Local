<?php

namespace App\Reports;

/**
 * Catalogue des colonnes disponibles pour le rapport « Présence & Ponctualité ».
 *
 * Source de vérité unique utilisée à la fois par la page de gestion des
 * modèles d'édition (colonnes à cocher), la validation, et le rendu PDF —
 * pour que ces trois usages ne puissent jamais diverger.
 */
class PresencePonctualiteColumns
{
    const REPORT_KEY = 'presence-ponctualite';

    /**
     * Colonnes regroupées par section. L'ordre des groupes puis des colonnes
     * à l'intérieur d'un groupe fixe l'ordre d'affichage définitif (catalogue
     * order), y compris pour les colonnes cochées par l'utilisateur.
     */
    public static function groups(): array
    {
        return [
            'identite' => [
                'label' => 'Identité',
                'grouped' => false,
                'columns' => [
                    'order_number' => ['label' => "N° d'ordre", 'align' => 'center', 'width' => '5%'],
                    'employee_code' => ['label' => 'Code employé', 'align' => 'left', 'width' => '8%'],
                    'employee_name' => ['label' => 'Nom et Prénoms', 'align' => 'left', 'width' => '18%'],
                    'department_name' => ['label' => 'Département', 'align' => 'left', 'width' => '12%'],
                ],
            ],
            'presence' => [
                'label' => 'PRÉSENCE AU POSTE',
                'grouped' => true,
                'columns' => [
                    'present' => ['label' => 'Présence', 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'absent' => ['label' => 'Absence', 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'presence_rate' => ['label' => 'Taux de présence', 'align' => 'center', 'width' => '8%', 'type' => 'percent', 'total' => 'avg'],
                    'present_days_display' => ['label' => 'Détail', 'align' => 'center', 'width' => '8%'],
                ],
            ],
            'ponctualite' => [
                'label' => 'PONCTUALITÉ',
                'grouped' => true,
                'columns' => [
                    'on_time' => ['label' => "A l'heure", 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'late' => ['label' => 'Retard', 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'early_leave' => ['label' => 'Départ anticipé', 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'half_day' => ['label' => 'Demi-journée', 'align' => 'center', 'width' => '6%', 'total' => 'sum'],
                    'ponctualite_rate' => ['label' => 'Taux de ponctualité', 'align' => 'center', 'width' => '8%', 'type' => 'percent', 'total' => 'avg'],
                ],
            ],
            'observations' => [
                'label' => 'Observations',
                'grouped' => false,
                'columns' => [
                    'observation' => ['label' => 'Observation', 'align' => 'left', 'width' => '20%'],
                ],
            ],
        ];
    }

    /**
     * Colonnes cochées par défaut pour le modèle « standard » — reproduit
     * exactement le tableau historique (avant l'introduction des modèles).
     */
    public static function defaultKeys(): array
    {
        return [
            'order_number', 'employee_name',
            'present', 'absent', 'presence_rate', 'present_days_display',
            'on_time', 'late', 'ponctualite_rate',
            'observation',
        ];
    }

    /**
     * Catalogue à plat : clé de colonne => définition (+ groupe d'origine).
     */
    public static function all(): array
    {
        $flat = [];
        foreach (static::groups() as $groupKey => $group) {
            foreach ($group['columns'] as $colKey => $col) {
                $flat[$colKey] = $col + [
                    'group' => $groupKey,
                    'group_label' => $group['label'],
                    'grouped' => $group['grouped'],
                ];
            }
        }
        return $flat;
    }

    /**
     * Filtre une liste de clés aux colonnes connues, sans doublons, et les
     * remet dans l'ordre du catalogue (peu importe l'ordre reçu).
     */
    public static function sanitize(array $keys): array
    {
        $known = array_keys(static::all());
        $wanted = array_values(array_unique(array_filter($keys, fn ($k) => in_array($k, $known, true))));
        return array_values(array_filter($known, fn ($k) => in_array($k, $wanted, true)));
    }

    /**
     * Valeur d'affichage d'une colonne pour une ligne du rapport
     * (structure produite par CustomReportController::getPresencePonctualiteData).
     */
    public static function value(string $key, array $row, int $index)
    {
        switch ($key) {
            case 'order_number':
                return $index + 1;
            case 'employee_code':
                return $row['employee_code'] ?? '';
            case 'employee_name':
                return $row['employee_name'] ?? '';
            case 'department_name':
                return $row['department_name'] ?? '';
            case 'present':
                return $row['presence_data']['present'] ?? 0;
            case 'absent':
                return $row['presence_data']['absent'] ?? 0;
            case 'presence_rate':
                return $row['presence_data']['rate'] ?? 0;
            case 'present_days_display':
                return $row['presence_data']['present_days_display'] ?? '0/0';
            case 'on_time':
                return $row['ponctualite_data']['on_time'] ?? 0;
            case 'late':
                return $row['ponctualite_data']['late'] ?? 0;
            case 'early_leave':
                return $row['ponctualite_data']['early_leave'] ?? 0;
            case 'half_day':
                return $row['ponctualite_data']['half_day'] ?? 0;
            case 'ponctualite_rate':
                return $row['ponctualite_data']['rate'] ?? 0;
            case 'observation':
                return $row['observation'] ?? 'Aucune observation';
            default:
                return '';
        }
    }

    /**
     * Totaux/moyennes pour les colonnes du catalogue qui en définissent un
     * (metadata "total" => 'sum' ou 'avg').
     */
    public static function totals(array $keys, array $rows): array
    {
        $catalogue = static::all();
        $totals = [];

        foreach ($keys as $key) {
            $mode = $catalogue[$key]['total'] ?? null;
            if (!$mode) {
                continue;
            }

            $values = array_map(fn ($row, $i) => (float) static::value($key, $row, $i), $rows, array_keys($rows));

            if ($mode === 'sum') {
                $totals[$key] = array_sum($values);
            } elseif ($mode === 'avg') {
                $totals[$key] = count($values) > 0 ? round(array_sum($values) / count($values), 1) : 0;
            }
        }

        return $totals;
    }

    /**
     * Complète les options d'un modèle avec leurs valeurs par défaut.
     */
    public static function normalizeOptions(?array $options): array
    {
        $options = $options ?? [];

        return [
            'orientation' => in_array($options['orientation'] ?? null, ['portrait', 'landscape'], true)
                ? $options['orientation']
                : 'landscape',
            'layout' => in_array($options['layout'] ?? null, ['single', 'per_section'], true)
                ? $options['layout']
                : 'single',
            'edition' => in_array($options['edition'] ?? null, ['standard', 'department'], true)
                ? $options['edition']
                : 'standard',
            'show_totals' => array_key_exists('show_totals', $options) ? (bool) $options['show_totals'] : true,
            'show_signatures' => array_key_exists('show_signatures', $options) ? (bool) $options['show_signatures'] : true,
            // Par défaut, le rapport ne porte que sur les jours ouvrés
            // (lundi-vendredi) — voir la légende du PDF. Cette option permet
            // d'inclure aussi les samedis/dimanches dans le calcul et
            // l'affichage, pour les organisations qui travaillent le week-end.
            'show_weekends' => array_key_exists('show_weekends', $options) ? (bool) $options['show_weekends'] : false,
        ];
    }
}

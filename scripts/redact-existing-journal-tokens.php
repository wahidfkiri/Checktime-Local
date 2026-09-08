<?php

/**
 * Nettoie les entrées déjà enregistrées dans activity_logs qui mentionnent
 * "token" (description ou properties), en appliquant la même substitution
 * que le filet de sécurité de LogRequestActivity::redact() — pour du
 * contenu déjà en base, généré avant ce correctif.
 *
 * Usage (depuis la racine du projet, sur le serveur) :
 *   php artisan tinker --execute="require 'scripts/redact-existing-journal-tokens.php';"
 *
 * Affiche ce qui serait changé sans exécuter de requête d'écriture (DRY_RUN)
 * sauf si la variable d'environnement DRY_RUN=0 est positionnée :
 *   DRY_RUN=0 php artisan tinker --execute="require 'scripts/redact-existing-journal-tokens.php';"
 */

function redact_journal_text(string $text): string
{
    $text = preg_replace('/token\s+d\'accès\s+non\s+configur[ée]e?/iu', "configuration d'accès manquante", $text) ?? $text;
    $text = preg_replace('/token\s+d\'accès/iu', "configuration d'accès", $text) ?? $text;
    $text = preg_replace('/\btoken\b/iu', 'accès', $text) ?? $text;

    return $text !== '' ? mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1) : $text;
}

function redact_journal_value($value)
{
    if (is_string($value)) {
        return redact_journal_text($value);
    }
    if (is_array($value)) {
        return array_map('redact_journal_value', $value);
    }
    return $value;
}

$dryRun = (getenv('DRY_RUN') ?: '1') !== '0';

$rows = \Vendor\Journalisation\Models\ActivityLog::where('description', 'like', '%oken%')
    ->orWhere('properties', 'like', '%oken%')
    ->get();

echo 'Lignes concernées : ' . $rows->count() . PHP_EOL;

foreach ($rows as $row) {
    $newDescription = $row->description ? redact_journal_text($row->description) : $row->description;
    $newProperties  = is_array($row->properties) ? redact_journal_value($row->properties) : $row->properties;

    echo "#{$row->id}" . PHP_EOL;
    echo '  avant: ' . $row->description . PHP_EOL;
    echo '  après: ' . $newDescription . PHP_EOL;

    if (!$dryRun) {
        $row->description = $newDescription;
        $row->properties  = $newProperties;
        $row->save();
    }
}

echo $dryRun
    ? "\nAperçu seulement (DRY_RUN). Relancez avec DRY_RUN=0 pour appliquer."
    : "\nAppliqué.";
echo PHP_EOL;

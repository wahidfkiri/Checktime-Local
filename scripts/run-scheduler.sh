#!/bin/bash
#
# Lance le planificateur de tâches Laravel (rapports par email planifiés,
# synchronisation des appareils, etc.).
#
# Ce script ne fait qu'appeler `php artisan schedule:run` : c'est Laravel
# lui-même (voir app/Console/Kernel.php) qui décide, à chaque appel, quelles
# tâches sont réellement dues, en lisant la table `scheduled_notifications`
# (configurable depuis Paramètres > Planification automatique des rapports).
# Ce script doit donc être appelé UNE FOIS PAR MINUTE, jamais plus souvent.
#
# Installation (cron classique, à ajouter avec `crontab -e`) :
#   * * * * * /chemin/vers/CheckTime-Custom/scripts/run-scheduler.sh >> /dev/null 2>&1
#
# Installation (panneau d'hébergement mutualisé type cPanel — section
# "Cron Jobs") : indiquer comme commande, avec la même fréquence "chaque
# minute" :
#   bash /chemin/vers/CheckTime-Custom/scripts/run-scheduler.sh
#
# Le binaire PHP utilisé peut être surchargé via la variable d'environnement
# PHP_BIN si `php` n'est pas dans le PATH du cron (fréquent en hébergement
# mutualisé, où le binaire s'appelle parfois php8.2, php-cli, etc.) :
#   PHP_BIN=/usr/bin/php8.2 /chemin/vers/CheckTime-Custom/scripts/run-scheduler.sh

set -euo pipefail

# Racine du projet = dossier parent de ce script, quel que soit le
# répertoire courant depuis lequel cron l'appelle.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
LOG_FILE="${LOG_FILE:-$PROJECT_DIR/storage/logs/scheduler.log}"

mkdir -p "$(dirname "$LOG_FILE")"

cd "$PROJECT_DIR"

{
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] schedule:run"
    "$PHP_BIN" artisan schedule:run --no-interaction
} >> "$LOG_FILE" 2>&1

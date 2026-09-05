<?php

namespace App\Support;

/** Résout le secret SMTP sans jamais le renvoyer à l'interface. */
class MailPassword
{
    public static function passwordFilePath(): string
    {
        return (string) config('mail.mailers.smtp.password_file', base_path('MAIL.txt'));
    }

    public static function resolve(?string $configuredPassword): ?string
    {
        // Le chemin peut être adapté en production avec MAIL_PASSWORD_FILE.
        // Par défaut, le fichier demandé est placé à la racine du projet. Le
        // fichier est prioritaire : il permet de remplacer sans ambiguïté un
        // ancien mot de passe encore présent dans la table settings.
        $path = self::passwordFilePath();
        if (is_file($path) && is_readable($path)) {
            $password = file_get_contents($path);
            if ($password !== false && rtrim($password, "\r\n") !== '') {
                return rtrim($password, "\r\n");
            }
        }

        return $configuredPassword !== null && $configuredPassword !== ''
            ? $configuredPassword
            : null;
    }

    /** Écrit le secret sans nécessiter l'accès en écriture au dossier applicatif. */
    public static function store(string $password): void
    {
        $path = self::passwordFilePath();
        $directory = dirname($path);

        if (!is_dir($directory)) {
            throw new \RuntimeException("Le dossier du fichier MAIL.txt est introuvable : {$directory}");
        }

        // MAIL.txt existe normalement déjà. Cette écriture directe évite de
        // donner à PHP le droit de modifier le répertoire de l'application.
        if (is_file($path) && !is_writable($path)) {
            throw new \RuntimeException("Le fichier MAIL.txt n'est pas accessible en écriture par PHP : {$path}");
        }

        if (!is_file($path) && !is_writable($directory)) {
            throw new \RuntimeException("Impossible de créer MAIL.txt : le dossier n'est pas accessible en écriture par PHP : {$directory}");
        }

        if (file_put_contents($path, $password . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d\'écrire le fichier de mot de passe SMTP.');
        }

        @chmod($path, 0600);
    }
}

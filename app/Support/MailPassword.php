<?php

namespace App\Support;

/** Résout le secret SMTP sans jamais le renvoyer à l'interface. */
class MailPassword
{
    public static function resolve(?string $configuredPassword): ?string
    {
        if ($configuredPassword !== null && $configuredPassword !== '') {
            return $configuredPassword;
        }

        // Le chemin peut être adapté en production avec MAIL_PASSWORD_FILE.
        // Par défaut, le fichier demandé est placé à la racine du projet.
        $path = config('mail.mailers.smtp.password_file', base_path('MAIL.txt'));
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $password = file_get_contents($path);

        return $password === false ? null : rtrim($password, "\r\n");
    }
}

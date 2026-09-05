<?php

namespace App\Support;

/** Résout le secret SMTP sans jamais le renvoyer à l'interface. */
class MailPassword
{
    public static function resolve(?string $configuredPassword): ?string
    {
        // Le chemin peut être adapté en production avec MAIL_PASSWORD_FILE.
        // Par défaut, le fichier demandé est placé à la racine du projet. Le
        // fichier est prioritaire : il permet de remplacer sans ambiguïté un
        // ancien mot de passe encore présent dans la table settings.
        $path = config('mail.mailers.smtp.password_file', base_path('MAIL.txt'));
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
}

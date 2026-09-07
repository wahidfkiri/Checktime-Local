<?php
// app/Models/Setting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'email',
        'email_is_active',
        'email_employees_is_active',
        'sms_is_active',
        'sms_credit',
    ];

    protected $casts = [
        'email_is_active' => 'boolean',
        'sms_is_active' => 'boolean',
        'email_employees_is_active' => 'boolean',
        'sms_credit' => 'integer',
    ];

    public static function set(string $key, $value, string $group = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * URL de base de l'API biométrique (table settings, group "company").
     *
     * Source unique de vérité : la clé `api_url` enregistrée par l'installeur
     * ou l'écran des paramètres. Aucune lecture de .env ici — les identifiants
     * de l'appareil ne doivent plus transiter par le fichier d'environnement.
     * Repli final : la valeur par défaut de config/checktime.php.
     */
    public static function apiUrl(): string
    {
        try {
            $value = self::where('group', 'company')->where('key', 'api_url')->value('value');
        } catch (\Exception $e) {
            $value = null;
        }

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : (string) config('checktime.base_url');
    }

    /**
     * Token d'accès à l'API biométrique (table settings, group "company").
     *
     * Repli sur la table access_configs pour les installations antérieures
     * à la migration vers la table settings. Retourne null si rien n'est
     * configuré : l'appelant doit gérer ce cas explicitement.
     */
    public static function apiToken(): ?string
    {
        try {
            $value = self::where('group', 'company')->where('key', 'api_token')->value('value');

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            $config = \Illuminate\Support\Facades\DB::table('access_configs')->first();
            $legacy = $config->general_token ?? null;

            return (is_string($legacy) && trim($legacy) !== '') ? trim($legacy) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Tolérance de retard (en minutes) configurée dans les paramètres.
     *
     * Un employé arrivé dans cette marge après l'heure de début planifiée
     * n'est PAS marqué en retard. Valeur mise en cache le temps de la requête
     * car appelée dans les boucles de génération des rapports.
     */
    public static function lateToleranceMinutes(): int
    {
        static $cached = null;

        if ($cached === null) {
            $value = self::where('key', 'late_tolerance_minutes')->value('value');
            $cached = max(0, (int) $value);
        }

        return $cached;
    }

    /**
     * Récupère la configuration SMTP stockée en base (groupe "mail"),
     * avec repli sur la config applicative (.env) pour préremplir le formulaire.
     */
    public static function mailConfig(): array
    {
        $db = self::where('group', 'mail')->pluck('value', 'key')->toArray();

        return [
            'mail_host'         => $db['mail_host']         ?? config('mail.mailers.smtp.host', ''),
            'mail_port'         => $db['mail_port']         ?? config('mail.mailers.smtp.port', 587),
            'mail_encryption'   => $db['mail_encryption']   ?? config('mail.mailers.smtp.encryption', ''),
            'mail_username'     => $db['mail_username']     ?? config('mail.mailers.smtp.username', ''),
            'mail_password'     => $db['mail_password']     ?? config('mail.mailers.smtp.password', ''),
            'mail_from_address' => $db['mail_from_address'] ?? config('mail.from.address', ''),
            'mail_from_name'    => $db['mail_from_name']    ?? config('mail.from.name', config('app.name', 'CheckTime')),
        ];
    }

    public static function company(): \stdClass
    {
        $settings = self::where('group', 'company')->pluck('value', 'key')->toArray();

        $user = new \stdClass();
        $user->name = $settings['app_name'] ?? 'CheckTime';
        $user->email = $settings['mail_from_address'] ?? '';

        $company = new \stdClass();
        $company->id = 1;
        $company->name = $settings['app_name'] ?? 'CheckTime';
        $company->raison_sociale = $settings['app_name'] ?? '';
        $company->company_name = $settings['app_name'] ?? '';
        $company->logo = $settings['app_logo'] ?? '';
        $company->address = $settings['app_address'] ?? '';
        $company->adresse = $settings['app_address'] ?? '';
        $company->phone = $settings['app_phone'] ?? '';
        $company->telephone = $settings['app_phone'] ?? '';
        $company->email = $settings['mail_from_address'] ?? '';
        $company->user = $user;
        $company->all = $settings;

        return $company;
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if ($column === 'sms_credit') {
            $newValue = max(0, $this->sms_credit - $amount);
            $this->sms_credit = $newValue;
            $this->save();
            return $this;
        }
        
        // For other columns, use parent method
        return parent::decrement($column, $amount, $extra);
    }
}
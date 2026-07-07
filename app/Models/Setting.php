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
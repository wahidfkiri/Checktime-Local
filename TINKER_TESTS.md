# Scripts Tinker — tests des rapports et des tâches planifiées

Ouvrir Tinker sur le serveur (`HOME=/tmp` évite l'erreur `psysh` de l'utilisateur `www-data`) :

```bash
cd /var/www/Checktime-Local
sudo -u www-data env HOME=/tmp php artisan tinker
```

Chaque commande de rapport accepte `--to=` pour envoyer à une adresse de test
**à la place** des destinataires configurés (et ignorer le drapeau d'activation
des emails). Après chaque appel, `echo Artisan::output();` affiche le résumé :
« Emails envoyés : 1 » si l'envoi a eu lieu.

---

## 1. Rapport hebdomadaire de présence (employés)

Commande : `attendance:send-weekly-reports`

Options : `--employee=<id>` (un seul employé), `--to=<emails>`, `--start-date=`, `--end-date=`.

```php
// Un employé précis, envoyé à une adresse de test
Artisan::call('attendance:send-weekly-reports', ['--employee' => 12, '--to' => 'wahidfkiri5@gmail.com']); echo Artisan::output();

// Même chose sur une période précise (Y-m-d)
Artisan::call('attendance:send-weekly-reports', ['--employee' => 12, '--to' => 'wahidfkiri5@gmail.com', '--start-date' => '2026-09-21', '--end-date' => '2026-09-25']); echo Artisan::output();

// L'employé reçoit son propre rapport sur l'email enregistré dans sa fiche
Artisan::call('attendance:send-weekly-reports', ['--employee' => 12]); echo Artisan::output();

// Tous les employés ayant un email (envoi réel, comme le cron)
Artisan::call('attendance:send-weekly-reports'); echo Artisan::output();
```

Trouver l'id d'un employé :

```php
App\Models\Employee::where('emp_code', '1010')->value('id');
App\Models\Employee::where('first_name', 'like', '%Jean%')->get(['id', 'emp_code', 'first_name', 'last_name', 'email']);
```

## 2. Rapport hebdomadaire RH

Commande : `attendance:send-weekly-rh-reports` — options : `--to=`, `--start-date=`, `--end-date=`, `--date=` (date de référence).

```php
Artisan::call('attendance:send-weekly-rh-reports', ['--to' => 'wahidfkiri5@gmail.com']); echo Artisan::output();

// Période précise
Artisan::call('attendance:send-weekly-rh-reports', ['--to' => 'wahidfkiri5@gmail.com', '--start-date' => '2026-09-21', '--end-date' => '2026-09-25']); echo Artisan::output();

// Plusieurs destinataires de test (séparés par des virgules)
Artisan::call('attendance:send-weekly-rh-reports', ['--to' => 'a@exemple.com,b@exemple.com']); echo Artisan::output();
```

## 3. Rapport mensuel RH

Commande : `reports:send-monthly-rh` — options : `--to=`, `--start-date=`, `--end-date=`, `--date=`.

```php
Artisan::call('reports:send-monthly-rh', ['--to' => 'wahidfkiri5@gmail.com']); echo Artisan::output();

// Un mois précis (n'importe quelle date de ce mois)
Artisan::call('reports:send-monthly-rh', ['--to' => 'wahidfkiri5@gmail.com', '--date' => '2026-08-15']); echo Artisan::output();
```

## 4. Sauvegarde de la base de données

Commande : `backup:send` — option : `--to=`. Génère une vraie sauvegarde à chaque appel.

```php
Artisan::call('backup:send', ['--to' => 'wahidfkiri5@gmail.com']); echo Artisan::output();
```

## 5. Synchronisation des pointages

Commande : `attendance:sync` — options : `--date=`, `--days-back=`, `--force`.

```php
Artisan::call('attendance:sync'); echo Artisan::output();
Artisan::call('attendance:sync', ['--days-back' => 3]); echo Artisan::output();
Artisan::call('attendance:sync', ['--date' => '2026-09-30']); echo Artisan::output();
```

---

## 6. Tâches planifiées (table `scheduled_notifications`)

```php
// État de toutes les tâches
foreach (App\Models\ScheduledNotification::all() as $j) { echo $j->command.' | actif='.(int)$j->is_active.' | '.$j->frequency.' | cron='.$j->cronExpression().' | dernier='.$j->last_run_at.PHP_EOL; }

// Activer / désactiver une tâche
App\Models\ScheduledNotification::where('command', 'attendance:send-weekly-reports')->update(['is_active' => true]);
App\Models\ScheduledNotification::where('command', 'attendance:send-weekly-reports')->update(['is_active' => false]);
```

Hors Tinker — vérifier que le scheduler voit la tâche et le cron système :

```bash
sudo -u www-data php artisan schedule:list
sudo crontab -l -u www-data
```

## 7. Templates d'emails personnalisés (éditeur Vvveb)

```php
// Quels emails sont personnalisés ?
App\Models\EmailTemplate::whereNotNull('html')->where('html', '!=', '')->get(['command', 'updated_at']);

// Revenir au modèle par défaut pour un rapport (équivaut au bouton « Réinitialiser »)
App\Models\EmailTemplate::where('command', 'attendance:send-weekly-reports')->delete();
```

Commandes : `attendance:send-weekly-reports`, `attendance:send-weekly-rh-reports`,
`reports:send-monthly-rh`, `backup:send`.

## 8. Privilèges utilisateurs (Spatie)

```php
// Donner l'accès à l'éditeur du contenu des emails à un utilisateur
$u = App\Models\User::where('email', 'utilisateur@exemple.com')->first();
$u->givePermissionTo('menu.email-templates');

// Vérifier / retirer
$u->can('menu.email-templates');
$u->revokePermissionTo('menu.email-templates');
```

## 9. Dépannage rapide

```php
// Drapeaux d'activation des emails (ignorés quand --to est utilisé)
$s = App\Models\Setting::first(); [(bool) $s->email_is_active, (bool) $s->email_employees_is_active, $s->email];

// SMTP appliqué à cet instant
config('mail.default'); config('mail.mailers.smtp.host'); config('mail.mailers.smtp.port');
```

```bash
# Derniers envois et erreurs dans le journal applicatif
grep -E "Message accepté|Erreur envoi|Rapports|SMTP" /var/www/Checktime-Local/storage/logs/laravel.log | tail -30
```

## 10. Employés actifs / inactifs

Un employé inactif (`employees.status = 'inactive'`) est masqué de tous les rapports,
statistiques, plannings, listes de sélection et de la synchronisation des pointages.
Le statut se change avec l'interrupteur de la colonne « Statut » de `/employees`
(décision locale : la synchronisation avec l'appareil ne l'écrase plus).

```php
// Combien d'actifs / inactifs
App\Models\Employee::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

// Désactiver / réactiver un employé par son code
App\Models\Employee::where('emp_code', '1010')->update(['status' => 'inactive']);
App\Models\Employee::where('emp_code', '1010')->update(['status' => 'active']);

// Tout remettre actif
App\Models\Employee::where('status', '!=', 'active')->update(['status' => 'active']);

// Vérifier qu'un employé inactif est bien exclu du rapport 1 (doit afficher « Aucun employé correspondant »)
Artisan::call('attendance:send-weekly-reports', ['--employee' => 12, '--to' => 'wahidfkiri5@gmail.com']); echo Artisan::output();
```

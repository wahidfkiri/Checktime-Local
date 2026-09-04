<?php

namespace Vendor\Journalisation;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Vendor\Journalisation\Middleware\LogRequestActivity;
use Vendor\Journalisation\Observers\ModelActivityObserver;
use Vendor\Journalisation\Support\ActivityLogger;

class JournalisationServiceProvider extends ServiceProvider
{
    /**
     * Modèles métier dont les créations / modifications / suppressions
     * sont journalisées automatiquement.
     */
    private const OBSERVED_MODELS = [
        \App\Models\Employee::class,
        \App\Models\Department::class,
        \App\Models\Leave::class,
        \App\Models\LeaveType::class,
        \App\Models\Mission::class,
        \App\Models\EmployeePermission::class,
        \App\Models\EmployeeSchedule::class,
        \App\Models\Holiday::class,
        \App\Models\Device::class,
        \App\Models\WorkHourType::class,
        \App\Models\ScheduleRotation::class,
        \App\Models\Zone::class,
        \App\Models\User::class,
        \App\Models\Setting::class,
        \App\Models\ReportTemplate::class,
        \App\Models\AccessConfig::class,
        \App\Models\Client::class,
        \App\Models\ClientUser::class,
        \App\Models\DailyPlanning::class,
        \App\Models\ReportSetting::class,
        \App\Models\ScheduledNotification::class,
        \App\Models\Signataire::class,
        \App\Models\SignatairePoste::class,
        \Spatie\Permission\Models\Role::class,
        \Spatie\Permission\Models\Permission::class,
        \Vendor\BackupData\Models\DataBackup::class,
    ];

    /*
     * Volontairement NON observés : AttendanceTransaction, DailyAttendance,
     * RealTimeLog, EmailLog, SmsLog. Ces tables sont alimentées en masse par les
     * synchronisations (des milliers de lignes par exécution) ; les journaliser
     * ligne à ligne rendrait le journal inexploitable. L'action globale est
     * tracée une seule fois par LogRequestActivity.
     */

    public function boot(Router $router)
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Views', 'journalisation');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->registerAuthEventListeners();
        $this->registerPermissionEventListeners();
        $this->registerModelObservers();
        $this->registerDownloadMiddleware();
    }

    public function register()
    {
        //
    }

    /**
     * Connexion / déconnexion / échec de connexion.
     */
    private function registerAuthEventListeners(): void
    {
        Event::listen(Login::class, function (Login $event) {
            ActivityLogger::log('login', 'Connexion réussie', null, [], $event->user);
        });

        Event::listen(Logout::class, function (Logout $event) {
            ActivityLogger::log('logout', 'Déconnexion', null, [], $event->user);
        });

        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? ($event->credentials['name'] ?? 'inconnu');
            ActivityLogger::log('login_failed', 'Échec de connexion (' . $email . ')');
        });
    }

    /**
     * Changements de rôles et de permissions (événements Spatie).
     *
     * Ces modifications passent par des tables pivot : aucun événement Eloquent
     * n'est émis sur le modèle User, d'où ces écouteurs dédiés. Nécessite
     * 'events_enabled' => true dans config/permission.php.
     */
    private function registerPermissionEventListeners(): void
    {
        $listeners = [
            RoleAttached::class       => ['Attribution de rôle(s)',   'rolesOrIds',       'role'],
            RoleDetached::class       => ['Retrait de rôle(s)',       'rolesOrIds',       'role'],
            PermissionAttached::class => ['Attribution de permission(s)', 'permissionsOrIds', 'permission'],
            PermissionDetached::class => ['Retrait de permission(s)',     'permissionsOrIds', 'permission'],
        ];

        foreach ($listeners as $event => [$label, $property, $type]) {
            Event::listen($event, function ($e) use ($label, $property, $type) {
                $items = $this->readableNames($e->{$property}, $type);

                // Un sync() qui ne retire rien émet quand même l'événement : on ignore.
                if (empty($items)) {
                    return;
                }

                ActivityLogger::log(
                    'update',
                    $label . ' — ' . class_basename($e->model) . ' #' . $e->model->getKey(),
                    $e->model,
                    ['elements' => $items]
                );
            });
        }
    }

    /**
     * Normalise la charge d'un événement Spatie (modèles, ids ou collection)
     * en une liste de noms lisibles.
     *
     * @param  mixed  $rolesOrPermissions
     * @param  string $type  'role' ou 'permission' (pour résoudre les identifiants)
     * @return array<int, string>
     */
    private function readableNames($rolesOrPermissions, string $type): array
    {
        if ($rolesOrPermissions instanceof \Illuminate\Support\Collection) {
            $rolesOrPermissions = $rolesOrPermissions->all();
        }

        $items = is_array($rolesOrPermissions) ? $rolesOrPermissions : [$rolesOrPermissions];
        $names = [];
        $ids   = [];

        foreach ($items as $item) {
            if (is_object($item)) {
                $names[] = $item->name ?? (string) $item->getKey();
            } elseif ($item !== null && $item !== '') {
                // Spatie transmet souvent des identifiants : on les résout en noms.
                $ids[] = $item;
            }
        }

        if ($ids) {
            try {
                $class = config('permission.models.' . $type);
                $names = array_merge($names, $class::whereIn('id', $ids)->pluck('name')->all());
            } catch (\Throwable $e) {
                $names = array_merge($names, array_map('strval', $ids));
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Observers CRUD sur les modèles suivis.
     */
    private function registerModelObservers(): void
    {
        foreach (self::OBSERVED_MODELS as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::observe(ModelActivityObserver::class);
            }
        }
    }

    /**
     * Ajoute au groupe "web" le middleware qui trace synchros, imports,
     * exports et téléchargements.
     */
    private function registerDownloadMiddleware(): void
    {
        try {
            $this->app->make(Router::class)
                ->pushMiddlewareToGroup('web', LogRequestActivity::class);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}

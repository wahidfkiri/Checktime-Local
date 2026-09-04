<?php

namespace Vendor\Journalisation;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Vendor\Journalisation\Middleware\LogDownloadActivity;
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
    ];

    public function boot(Router $router)
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Views', 'journalisation');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->registerAuthEventListeners();
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
     * Ajoute le middleware de capture des téléchargements au groupe "web".
     */
    private function registerDownloadMiddleware(): void
    {
        try {
            $this->app->make(Router::class)
                ->pushMiddlewareToGroup('web', LogDownloadActivity::class);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}

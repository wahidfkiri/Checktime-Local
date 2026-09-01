<?php

namespace Vendor\BackupData;

use Illuminate\Support\ServiceProvider;

class BackupDataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Routes du module
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // Vues (namespace "backup_data")
        $this->loadViewsFrom(__DIR__ . '/Views', 'backup_data');

        // Migration de l'historique des exports
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    public function register()
    {
        //
    }
}

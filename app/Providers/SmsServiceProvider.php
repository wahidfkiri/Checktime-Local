<?php

namespace App\Providers;

use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('sms.service', function ($app) {
            return new SmsService();
        });

        // Publier la configuration
        $this->publishes([
            __DIR__.'/../../config/sms.php' => config_path('sms.php'),
        ], 'sms-config');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Charger la configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/sms.php', 'sms'
        );
    }
}
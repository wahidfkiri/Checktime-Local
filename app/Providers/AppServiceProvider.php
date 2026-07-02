<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        try {
            if (Schema::hasTable('settings')) {
                $logoPath = \App\Models\Setting::where('key', 'app_logo')->value('value');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not load app_logo setting: ' . $e->getMessage());
        }

        $defaultLogo = asset('logo.jpg');
        $appLogo = !empty($logoPath) ? asset($logoPath) : $defaultLogo;

        view()->composer('*', function ($view) use ($appLogo) {
            $view->with('appLogo', $appLogo);
        });
    }
}

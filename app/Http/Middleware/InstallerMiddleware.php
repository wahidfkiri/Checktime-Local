<?php

namespace App\Http\Middleware;

use App\Services\InstallationLock;
use Closure;
use Illuminate\Http\Request;

class InstallerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * If the application is already installed, redirect to login.
     * If not installed, allow access to installer routes.
     */
    public function handle(Request $request, Closure $next)
    {
        if (InstallationLock::isInstalled()) {
            return redirect()->route('login')
                ->with('info', 'L\'application est déjà installée.');
        }

        return $next($request);
    }
}
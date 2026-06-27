<?php

namespace App\Http\Middleware;

use App\Services\InstallationLock;
use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    /**
     * Handle an incoming request.
     *
     * If the application is not installed, redirect to the installer.
     * This middleware protects all application routes from being accessed
     * before the installation process is complete.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!InstallationLock::isInstalled()) {
            return redirect()->route('installer.index');
        }

        return $next($request);
    }
}
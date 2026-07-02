<?php

namespace App\Http\Middleware;

use App\Services\InstallationLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!InstallationLock::isInstalled()) {
            return redirect()->route('installer.index');
        }

        return $next($request);
    }
}

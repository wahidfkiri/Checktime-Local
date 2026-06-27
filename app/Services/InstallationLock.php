<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class InstallationLock
{
    /**
     * Path to the lock file.
     */
    protected static string $lockPath = 'storage/app/.installed';

    /**
     * Check if the application is already installed.
     */
    public static function isInstalled(): bool
    {
        return File::exists(base_path(static::$lockPath));
    }

    /**
     * Lock the application (mark as installed).
     */
    public static function lock(): bool
    {
        $path = base_path(static::$lockPath);

        if (!File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }

        return File::put($path, json_encode([
            'installed_at' => now()->toDateTimeString(),
            'version' => config('app.version', '1.0.0'),
        ])) !== false;
    }

    /**
     * Remove the lock (for re-installation if needed).
     */
    public static function unlock(): bool
    {
        $path = base_path(static::$lockPath);

        if (File::exists($path)) {
            return File::delete($path);
        }

        return true;
    }
}
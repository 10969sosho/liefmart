<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Helpers\PathHelper;

class PathServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register PathHelper as singleton
        $this->app->singleton(PathHelper::class, function ($app) {
            return new PathHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure directories exist on boot
        $this->ensureDirectoriesExist();
        
        // Clean up old temp files on boot (10% chance)
        if (rand(1, 10) === 1) {
            PathHelper::cleanupTempFiles();
        }
    }
    
    /**
     * Ensure all necessary directories exist
     */
    private function ensureDirectoriesExist()
    {
        $directories = [
            PathHelper::getStorageAppPath(),
            PathHelper::getStoragePublicPath(),
            PathHelper::getTempPath(),
            PathHelper::getExportPath('excel'),
            PathHelper::getExportPath('pdf'),
        ];
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                PathHelper::ensureDirectoryExists($directory);
            }
        }
    }
}

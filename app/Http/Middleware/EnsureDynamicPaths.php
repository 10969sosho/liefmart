<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\PathHelper;

class EnsureDynamicPaths
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure all necessary directories exist
        $this->ensureDirectoriesExist();
        
        // Clean up old temp files periodically (1% chance)
        if (rand(1, 100) === 1) {
            PathHelper::cleanupTempFiles();
        }
        
        return $next($request);
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

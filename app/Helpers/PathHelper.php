<?php

namespace App\Helpers;

class PathHelper
{
    /**
     * Get dynamic public path
     * 
     * @return string
     */
    public static function getPublicPath()
    {
        return public_path();
    }

    /**
     * Get dynamic storage path
     * 
     * @return string
     */
    public static function getStoragePath()
    {
        return storage_path();
    }

    /**
     * Get dynamic storage app path
     * 
     * @return string
     */
    public static function getStorageAppPath()
    {
        return storage_path('app');
    }

    /**
     * Get dynamic storage public path
     * 
     * @return string
     */
    public static function getStoragePublicPath()
    {
        return storage_path('app/public');
    }

    /**
     * Get dynamic base path
     * 
     * @return string
     */
    public static function getBasePath()
    {
        return base_path();
    }

    /**
     * Ensure directory exists and is writable
     * 
     * @param string $path
     * @return bool
     */
    public static function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        
        return is_writable($path);
    }

    /**
     * Get safe filename for export
     * 
     * @param string $filename
     * @return string
     */
    public static function getSafeFilename($filename)
    {
        // Remove any characters that might cause issues
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Ensure it has proper extension
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.xlsx';
        }
        
        return $filename;
    }

    /**
     * Get dynamic export path
     * 
     * @param string $type
     * @return string
     */
    public static function getExportPath($type = 'excel')
    {
        $basePath = self::getStorageAppPath();
        $exportPath = $basePath . '/exports/' . $type;
        
        self::ensureDirectoryExists($exportPath);
        
        return $exportPath;
    }

    /**
     * Get dynamic temp path
     * 
     * @return string
     */
    public static function getTempPath()
    {
        $tempPath = self::getStorageAppPath() . '/temp';
        
        self::ensureDirectoryExists($tempPath);
        
        return $tempPath;
    }

    /**
     * Clean up old files in temp directory
     * 
     * @param int $maxAgeHours
     * @return int Number of files cleaned
     */
    public static function cleanupTempFiles($maxAgeHours = 24)
    {
        $tempPath = self::getTempPath();
        $cutoffTime = time() - ($maxAgeHours * 3600);
        $cleaned = 0;
        
        if (is_dir($tempPath)) {
            $files = glob($tempPath . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}

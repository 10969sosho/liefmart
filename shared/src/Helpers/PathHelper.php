<?php

namespace Shared\Helpers;

class PathHelper
{
    public static function getPublicPath()
    {
        return public_path();
    }

    public static function getStoragePath()
    {
        return storage_path();
    }

    public static function getStorageAppPath()
    {
        return storage_path('app');
    }

    public static function getStoragePublicPath()
    {
        return storage_path('app/public');
    }

    public static function getBasePath()
    {
        return base_path();
    }

    public static function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        
        return is_writable($path);
    }

    public static function getSafeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.xlsx';
        }
        
        return $filename;
    }

    public static function getExportPath($type = 'excel')
    {
        $basePath = self::getStorageAppPath();
        $exportPath = $basePath . '/exports/' . $type;
        
        self::ensureDirectoryExists($exportPath);
        
        return $exportPath;
    }

    public static function getTempPath()
    {
        $tempPath = self::getStorageAppPath() . '/temp';
        
        self::ensureDirectoryExists($tempPath);
        
        return $tempPath;
    }

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

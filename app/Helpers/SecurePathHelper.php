<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SecurePathHelper
{
    /**
     * Base path of the Laravel application
     */
    private static ?string $basePath = null;

    /**
     * Maximum allowed parent directory levels from Laravel base
     */
    private const MAX_PARENT_LEVELS = 1;

    /**
     * Initialize the base path with security validation
     */
    private static function initializeBasePath(): void
    {
        if (self::$basePath === null) {
            $basePath = base_path();
            
            // Validate that we're not too far from Laravel root
            $realBasePath = realpath($basePath);
            if ($realBasePath === false) {
                throw new \Exception('Invalid base path detected');
            }
            
            self::$basePath = $realBasePath;
        }
    }

    /**
     * Get the public path with security validation
     * Only allows going up 1 level from Laravel base
     */
    public static function getSecurePublicPath(): string
    {
        self::initializeBasePath();
        
        $publicPath = public_path();
        $realPublicPath = realpath($publicPath);
        
        if ($realPublicPath === false) {
            throw new \Exception('Public path not accessible');
        }
        
        // Check if public path is within allowed tolerance
        if (!self::isPathWithinTolerance($realPublicPath)) {
            throw new \Exception('Public path exceeds security tolerance (max 1 level up from Laravel base)');
        }
        
        return $realPublicPath;
    }

    /**
     * Get the storage path with security validation
     */
    public static function getSecureStoragePath(): string
    {
        self::initializeBasePath();
        
        $storagePath = storage_path();
        $realStoragePath = realpath($storagePath);
        
        if ($realStoragePath === false) {
            throw new \Exception('Storage path not accessible');
        }
        
        // Check if storage path is within allowed tolerance
        if (!self::isPathWithinTolerance($realStoragePath)) {
            throw new \Exception('Storage path exceeds security tolerance (max 1 level up from Laravel base)');
        }
        
        return $realStoragePath;
    }

    /**
     * Get the storage/app path with security validation
     */
    public static function getSecureStorageAppPath(): string
    {
        $storagePath = self::getSecureStoragePath();
        $appPath = $storagePath . DIRECTORY_SEPARATOR . 'app';
        
        if (!File::isDirectory($appPath)) {
            File::makeDirectory($appPath, 0755, true, true);
        }
        
        return $appPath;
    }

    /**
     * Get the storage/app/public path with security validation
     */
    public static function getSecureStoragePublicPath(): string
    {
        $storageAppPath = self::getSecureStorageAppPath();
        $publicPath = $storageAppPath . DIRECTORY_SEPARATOR . 'public';
        
        if (!File::isDirectory($publicPath)) {
            File::makeDirectory($publicPath, 0755, true, true);
        }
        
        return $publicPath;
    }

    /**
     * Get the temporary directory path with security validation
     */
    public static function getSecureTempPath(): string
    {
        $storageAppPath = self::getSecureStorageAppPath();
        $tempPath = $storageAppPath . DIRECTORY_SEPARATOR . 'temp';
        
        if (!File::isDirectory($tempPath)) {
            File::makeDirectory($tempPath, 0755, true, true);
        }
        
        return $tempPath;
    }

    /**
     * Get the export directory path for Excel files with security validation
     */
    public static function getSecureExportExcelPath(): string
    {
        $storageAppPath = self::getSecureStorageAppPath();
        $exportPath = $storageAppPath . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . 'excel';
        
        if (!File::isDirectory($exportPath)) {
            File::makeDirectory($exportPath, 0755, true, true);
        }
        
        return $exportPath;
    }

    /**
     * Get the export directory path for PDF files with security validation
     */
    public static function getSecureExportPdfPath(): string
    {
        $storageAppPath = self::getSecureStorageAppPath();
        $exportPath = $storageAppPath . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . 'pdf';
        
        if (!File::isDirectory($exportPath)) {
            File::makeDirectory($exportPath, 0755, true, true);
        }
        
        return $exportPath;
    }

    /**
     * Check if a path is within the allowed tolerance (max 1 level up from Laravel base)
     */
    private static function isPathWithinTolerance(string $path): bool
    {
        $basePath = self::$basePath;
        $basePathParts = explode(DIRECTORY_SEPARATOR, $basePath);
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        
        // Find common prefix
        $commonPrefix = [];
        $minLength = min(count($basePathParts), count($pathParts));
        
        for ($i = 0; $i < $minLength; $i++) {
            if ($basePathParts[$i] === $pathParts[$i]) {
                $commonPrefix[] = $basePathParts[$i];
            } else {
                break;
            }
        }
        
        // Calculate how many levels up the path goes from Laravel base
        $baseLevels = count($basePathParts);
        $pathLevels = count($pathParts);
        $commonLevels = count($commonPrefix);
        
        // If path is shorter than base, it means it's going up
        $upLevels = $baseLevels - $commonLevels;
        
        // Allow only 1 level up from Laravel base
        return $upLevels <= self::MAX_PARENT_LEVELS;
    }

    /**
     * Generate a safe filename by removing potentially problematic characters
     */
    public static function getSafeFilename(string $filename): string
    {
        // Remove characters that are not alphanumeric, dashes, underscores, or dots
        $filename = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $filename);
        // Replace multiple dots with a single dot
        $filename = preg_replace('/\.\.+/', '.', $filename);
        // Trim leading/trailing dots, dashes, underscores
        $filename = trim($filename, '.-_');
        // Ensure it's not empty, fallback to a default if it is
        if (empty($filename)) {
            $filename = 'file_' . uniqid();
        }
        return $filename;
    }

    /**
     * Clean up old temporary files with security validation
     */
    public static function cleanupSecureTempFiles(int $hours = 24): void
    {
        try {
            $tempPath = self::getSecureTempPath();
            $files = File::files($tempPath);

            foreach ($files as $file) {
                if (File::lastModified($file) < now()->subHours($hours)->timestamp) {
                    File::delete($file);
                    Log::info("Deleted old temporary file: {$file->getPathname()}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to cleanup temp files: " . $e->getMessage());
        }
    }

    /**
     * Validate that all paths are secure and within tolerance
     */
    public static function validateSecurity(): array
    {
        $results = [];
        
        try {
            $results['public_path'] = [
                'path' => self::getSecurePublicPath(),
                'secure' => true,
                'message' => 'Public path is secure'
            ];
        } catch (\Exception $e) {
            $results['public_path'] = [
                'path' => null,
                'secure' => false,
                'message' => $e->getMessage()
            ];
        }
        
        try {
            $results['storage_path'] = [
                'path' => self::getSecureStoragePath(),
                'secure' => true,
                'message' => 'Storage path is secure'
            ];
        } catch (\Exception $e) {
            $results['storage_path'] = [
                'path' => null,
                'secure' => false,
                'message' => $e->getMessage()
            ];
        }
        
        return $results;
    }

    /**
     * Get security status and recommendations
     */
    public static function getSecurityStatus(): array
    {
        $validation = self::validateSecurity();
        $allSecure = true;
        
        foreach ($validation as $result) {
            if (!$result['secure']) {
                $allSecure = false;
                break;
            }
        }
        
        return [
            'overall_secure' => $allSecure,
            'max_parent_levels' => self::MAX_PARENT_LEVELS,
            'base_path' => self::$basePath ?? base_path(),
            'validation' => $validation,
            'recommendations' => $allSecure ? [
                'All paths are secure and within tolerance',
                'System is safe from cross-domain access',
                'Maximum 1 level up from Laravel base is allowed'
            ] : [
                'Some paths exceed security tolerance',
                'Check file permissions and directory structure',
                'Ensure Laravel base path is correct'
            ]
        ];
    }
}

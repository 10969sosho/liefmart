<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\PathHelper;

class EnsureStorageLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:ensure-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure storage link exists and is properly configured';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ensuring storage link configuration...');
        
        // Get dynamic paths
        $publicPath = PathHelper::getPublicPath();
        $storagePublicPath = PathHelper::getStoragePublicPath();
        $linkPath = $publicPath . '/storage';
        
        $this->info("Public path: {$publicPath}");
        $this->info("Storage public path: {$storagePublicPath}");
        $this->info("Link path: {$linkPath}");
        
        // Check if storage/app/public directory exists
        if (!is_dir($storagePublicPath)) {
            $this->info('Creating storage/app/public directory...');
            if (PathHelper::ensureDirectoryExists($storagePublicPath)) {
                $this->info('✓ Storage public directory created successfully');
            } else {
                $this->error('✗ Failed to create storage public directory');
                return 1;
            }
        } else {
            $this->info('✓ Storage public directory exists');
        }
        
        // Check if link exists
        if (is_link($linkPath)) {
            $this->info('✓ Storage link already exists');
            
            // Check if link is correct
            $linkTarget = readlink($linkPath);
            if ($linkTarget === $storagePublicPath) {
                $this->info('✓ Storage link is correctly configured');
            } else {
                $this->warn("Storage link points to: {$linkTarget}");
                $this->warn("Expected: {$storagePublicPath}");
                $this->info('Removing incorrect link...');
                unlink($linkPath);
            }
        } elseif (file_exists($linkPath)) {
            $this->warn('Storage link path exists but is not a link');
            $this->info('Removing existing file/directory...');
            if (is_dir($linkPath)) {
                rmdir($linkPath);
            } else {
                unlink($linkPath);
            }
        }
        
        // Create link if it doesn't exist
        if (!is_link($linkPath)) {
            $this->info('Creating storage link...');
            if (symlink($storagePublicPath, $linkPath)) {
                $this->info('✓ Storage link created successfully');
            } else {
                $this->error('✗ Failed to create storage link');
                $this->error('You may need to run: php artisan storage:link');
                return 1;
            }
        }
        
        // Ensure temp directory exists
        $tempPath = PathHelper::getTempPath();
        $this->info("✓ Temp directory ensured: {$tempPath}");
        
        // Clean up old temp files
        $cleaned = PathHelper::cleanupTempFiles();
        if ($cleaned > 0) {
            $this->info("✓ Cleaned up {$cleaned} old temp files");
        }
        
        $this->info('Storage link configuration completed successfully!');
        return 0;
    }
}

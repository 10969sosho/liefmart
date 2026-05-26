<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Exception;

class ExportDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {--filename= : Custom filename for the export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all database tables and data to SQL file in root directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Starting database export...');

            // Get database configuration
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port') ?? '3306';

            // Validate database configuration
            if (empty($database) || empty($username)) {
                $this->error('Database configuration is incomplete. Please check your .env file.');
                return 1;
            }

            // Generate filename
            $filename = $this->option('filename');
            if (!$filename) {
                $filename = 'database_export_' . date('Y-m-d_H-i-s') . '.sql';
            }
            
            // Ensure filename ends with .sql
            if (!str_ends_with($filename, '.sql')) {
                $filename .= '.sql';
            }

            // Export path is in root directory
            $exportPath = base_path($filename);

            $this->info("Database: {$database}");
            $this->info("Host: {$host}:{$port}");
            $this->info("Export file: {$filename}");
            $this->info("");

            // Build mysqldump command
            // Options:
            // --quick: Don't buffer result, dump directly
            // --lock-tables=false: Don't lock tables (allows concurrent reads)
            // --single-transaction: Creates a consistent snapshot (for InnoDB)
            // --routines: Include stored procedures and functions
            // --triggers: Include triggers
            // --events: Include events
            // --add-drop-table: Add DROP TABLE statement before each CREATE TABLE
            // --complete-insert: Use complete INSERT statements
            // --set-gtid-purged=OFF: Disable GTID warnings
            // Redirect stderr to /dev/null to suppress warnings, stdout to file
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s -p%s --quick --lock-tables=false --single-transaction --routines --triggers --events --add-drop-table --complete-insert --set-gtid-purged=OFF %s > %s 2>/dev/null',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($exportPath)
            );

            // Execute mysqldump command
            $this->info('Exporting database...');
            exec($command, $output, $returnCode);
            
            // If command failed, try to get error from stderr
            if ($returnCode !== 0) {
                // Try again with stderr captured
                $errorCommand = sprintf(
                    'mysqldump -h %s -P %s -u %s -p%s --quick --lock-tables=false --single-transaction --routines --triggers --events --add-drop-table --complete-insert --set-gtid-purged=OFF %s > %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($exportPath)
                );
                $errorOutput = shell_exec($errorCommand);
                
                // Check if file was created despite error code
                if (!File::exists($exportPath)) {
                    $this->error('Export failed with return code: ' . $returnCode);
                    if ($errorOutput) {
                        $this->error('Error: ' . $errorOutput);
                    }
                    return 1;
                }
            }
            
            // Clean up file: remove any non-SQL lines at the beginning
            $this->cleanSqlFile($exportPath);

            // Check if file was created and has content
            if (!File::exists($exportPath)) {
                $this->error('Export failed: File was not created.');
                return 1;
            }

            $fileSize = File::size($exportPath);
            if ($fileSize == 0) {
                $this->error('Export failed: File is empty.');
                File::delete($exportPath);
                return 1;
            }

            // Format file size for display
            $fileSizeFormatted = $this->formatBytes($fileSize);

            $this->info('');
            $this->info('✓ Database export completed successfully!');
            $this->info("File: {$filename}");
            $this->info("Size: {$fileSizeFormatted}");
            $this->info("Location: " . base_path());
            $this->info('');
            $this->info('You can now import this file to MySQL using:');
            $this->info("mysql -u username -p database_name < {$filename}");

            return 0;
        } catch (Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clean SQL file by removing non-SQL lines at the beginning
     *
     * @param string $filePath
     * @return void
     */
    private function cleanSqlFile($filePath)
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        // Find the first line that looks like SQL (starts with --, /*, or SET, or CREATE, etc.)
        $startIndex = 0;
        $sqlStartPatterns = ['--', '/*', 'SET', 'CREATE', 'DROP', 'LOCK', 'INSERT', '/*!'];
        
        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);
            
            // Skip empty lines
            if (empty($trimmedLine)) {
                continue;
            }
            
            // Check if this looks like SQL
            foreach ($sqlStartPatterns as $pattern) {
                if (str_starts_with($trimmedLine, $pattern)) {
                    $startIndex = $index;
                    break 2;
                }
            }
            
            // If we see "Enter password:" or "Warning:" or similar, skip it
            if (stripos($trimmedLine, 'Enter password:') !== false || 
                stripos($trimmedLine, 'Warning:') !== false ||
                stripos($trimmedLine, 'mysqldump:') !== false) {
                continue;
            }
        }
        
        // If we found non-SQL content at the beginning, remove it
        if ($startIndex > 0) {
            $cleanedLines = array_slice($lines, $startIndex);
            File::put($filePath, implode("\n", $cleanedLines));
            $this->info('Cleaned non-SQL content from file.');
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}


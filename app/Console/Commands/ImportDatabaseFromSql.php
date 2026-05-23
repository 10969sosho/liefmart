<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Exception;

class ImportDatabaseFromSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-sql {sqlFile} {--force : Force import without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables and import database from SQL file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sqlFile = $this->argument('sqlFile');
        $force = $this->option('force');

        // Check if file exists
        if (!File::exists($sqlFile)) {
            $this->error("File tidak ditemukan: {$sqlFile}");
            return 1;
        }

        $this->info("File SQL: {$sqlFile}");
        $this->info("Database: " . config('database.connections.mysql.database'));

        if (!$force) {
            if (!$this->confirm('Apakah Anda yakin ingin menghapus SEMUA tabel dan mengimpor database dari file SQL ini?', false)) {
                $this->info('Operasi dibatalkan.');
                return 0;
            }
        }

        try {
            // Step 1: Drop all tables
            $this->info('Menghapus semua tabel...');
            $this->wipeAllTables();
            $this->info('✓ Semua tabel berhasil dihapus.');

            // Step 2: Import SQL file
            $this->info('Mengimpor file SQL...');
            $this->importSqlFile($sqlFile);
            $this->info('✓ File SQL berhasil diimpor.');

            $this->info('');
            $this->info('✓ Database berhasil diimpor!');

            return 0;
        } catch (Exception $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Drop all tables from database
     */
    private function wipeAllTables()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Get all table names
        $tables = DB::select('SHOW TABLES');

        if (empty($tables)) {
            $this->info('Tidak ada tabel yang perlu dihapus.');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return;
        }

        // Drop all tables
        foreach ($tables as $table) {
            $values = array_values((array) $table);
            $tableName = $values[0] ?? null;
            if (!$tableName) {
                continue;
            }
            DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            $this->line("  - Dihapus: {$tableName}");
        }

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Import SQL file using mysql command
     */
    private function importSqlFile($sqlFilePath)
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        // Create temporary config file for mysql command (more secure)
        $configFile = sys_get_temp_dir() . '/mysql_config_' . uniqid() . '.cnf';
        $configContent = "[client]\n";
        $configContent .= "host={$host}\n";
        $configContent .= "port={$port}\n";
        $configContent .= "user={$username}\n";
        $configContent .= "password={$password}\n";
        
        File::put($configFile, $configContent);
        chmod($configFile, 0600); // Read/write for owner only

        try {
            // Build mysql command using config file
            $command = sprintf(
                'mysql --defaults-file=%s --force --verbose %s < %s 2>&1',
                escapeshellarg($configFile),
                escapeshellarg($database),
                escapeshellarg($sqlFilePath)
            );

            // Execute command
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // Clean up config file
            @unlink($configFile);

            if ($returnVar !== 0) {
                $errorOutput = implode("\n", $output);
                // Check for critical errors (ignore warnings and duplicate key errors)
                if (preg_match('/ERROR\s+(?!1062|1049|1050)/', $errorOutput)) {
                    // ERROR 1062 = Duplicate entry (can be ignored)
                    // ERROR 1049 = Unknown database (should not happen)
                    // ERROR 1050 = Table already exists (can be ignored in some cases)
                    throw new Exception('Gagal mengimpor SQL file: ' . $errorOutput);
                }
            }

            // Show summary
            if (!empty($output)) {
                $errorLines = array_filter($output, function($line) {
                    return preg_match('/ERROR\s+(?!1062|1050)/', $line);
                });
                if (empty($errorLines)) {
                    $this->info('  ✓ Import berhasil (beberapa warning diabaikan)');
                } else {
                    $this->warn('  ⚠ Beberapa error ditemukan:');
                    foreach ($errorLines as $line) {
                        $this->line('    ' . $line);
                    }
                }
            } else {
                $this->info('  ✓ Import berhasil');
            }
            
        } catch (Exception $e) {
            // Clean up config file in case of error
            @unlink($configFile);
            throw $e;
        }
    }
}

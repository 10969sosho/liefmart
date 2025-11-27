<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RefreshAnalyticMaterializedTable extends Command
{
    protected $signature = 'analytic:refresh-materialized-table 
                            {--days=90 : Number of days to refresh (default: 90)}
                            {--force : Force refresh even if recently updated}';
    
    protected $description = 'Refresh the materialized table for analytic master product';

    public function handle()
    {
        $this->info('Starting materialized table refresh...');
        
        $retentionDays = (int) $this->option('days');
        $force = $this->option('force');
        
        try {
            // Check if table exists
            $tableExists = DB::selectOne("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'mv_analytic_master_product'
            ");
            
            if ($tableExists->count == 0) {
                $this->error('Materialized table does not exist. Please run create_materialized_table.sql first.');
                return 1;
            }
            
            // Read refresh SQL
            $refreshSqlFile = database_path('sql/refresh_materialized_table.sql');
            if (!File::exists($refreshSqlFile)) {
                $this->error('Refresh SQL file not found: ' . $refreshSqlFile);
                return 1;
            }
            
            $refreshSql = File::get($refreshSqlFile);
            
            // Replace retention days variable
            $refreshSql = str_replace('SET @retention_days = 90;', "SET @retention_days = {$retentionDays};", $refreshSql);
            
            $this->info("Refreshing data for last {$retentionDays} days...");
            
            // Execute refresh SQL
            DB::beginTransaction();
            try {
                // Split SQL by semicolon and execute each statement
                $statements = array_filter(
                    array_map('trim', explode(';', $refreshSql)),
                    function($stmt) {
                        return !empty($stmt) && !preg_match('/^--/', $stmt);
                    }
                );
                
                foreach ($statements as $statement) {
                    if (!empty(trim($statement))) {
                        DB::statement($statement);
                    }
                }
                
                DB::commit();
                
                // Get row count
                $rowCount = DB::selectOne("SELECT COUNT(*) as count FROM mv_analytic_master_product");
                $this->info("✓ Materialized table refreshed successfully!");
                $this->info("  Total rows in table: " . number_format($rowCount->count));
                
                Log::info('Materialized table refreshed', [
                    'retention_days' => $retentionDays,
                    'row_count' => $rowCount->count
                ]);
                
                return 0;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error('Error refreshing materialized table: ' . $e->getMessage());
            Log::error('Materialized table refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}


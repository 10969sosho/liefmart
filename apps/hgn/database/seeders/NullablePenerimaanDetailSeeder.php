<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class NullablePenerimaanDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            // Get the SQL file content
            $sql = File::get(database_path('raw_sql_migration_nullable.sql'));
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            // Execute each statement
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    DB::statement($statement);
                    $this->command->info("Executed: " . substr($statement, 0, 80) . "...");
                }
            }
            
            $this->command->info('Penerimaan detail ID has been made nullable!');
        } catch (\Exception $e) {
            $this->command->error('Error executing migration: ' . $e->getMessage());
        }
    }
} 
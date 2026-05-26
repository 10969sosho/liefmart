<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportMasterBarangInternalSeeder;

class ImportMasterBarangInternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:master-barang-internal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Master Barang Internal from CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Master Barang Internal import...');
        
        $csvFile = storage_path('app/imports/MASTER - BARANG INTERNAL (3).csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }
        
        // Run the seeder
        $seeder = new ImportMasterBarangInternalSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Master Barang Internal import completed!');
        
        return 0;
    }
}

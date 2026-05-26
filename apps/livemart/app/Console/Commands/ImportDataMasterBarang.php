<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportDataMasterBarangSeeder;

class ImportDataMasterBarang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:data-master-barang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Data Master Barang from CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Data Master Barang import...');
        
        $csvFile = storage_path('app/imports/DATA MASTER BARANG.csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }
        
        // Run the seeder
        $seeder = new ImportDataMasterBarangSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Data Master Barang import completed!');
        
        return 0;
    }
}

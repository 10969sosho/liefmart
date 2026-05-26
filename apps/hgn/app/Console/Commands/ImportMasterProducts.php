<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ProductSeeder;

class ImportMasterProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import master products from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting master products import...');
        
        // Check if the storage/app/imports directory exists, if not create it
        if (!file_exists(storage_path('app/imports'))) {
            mkdir(storage_path('app/imports'), 0755, true);
            $this->info('Created directory: ' . storage_path('app/imports'));
        }
        
        $csvFile = storage_path('app/imports/MASTER - BARANG INTERNAL.csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            $this->info('Please place your CSV file at the specified location with name: MASTER - BARANG INTERNAL.csv');
            return 1;
        }
        
        // Run the seeder
        $seeder = new ProductSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Product import completed!');
        
        return 0;
    }
} 
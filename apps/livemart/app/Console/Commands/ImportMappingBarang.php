<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportMappingBarangSeeder;

class ImportMappingBarang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:mapping-barang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Mapping Barang for Shopee and Tiktok (Lamourad & Liefmarket)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Mapping Barang import...');
        
        // Run the seeder
        $seeder = new ImportMappingBarangSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Mapping Barang import completed!');
        
        return 0;
    }
}

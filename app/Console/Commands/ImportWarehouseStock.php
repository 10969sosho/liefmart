<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportWarehouseStockSeeder;

class ImportWarehouseStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:warehouse-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Warehouse Stock from Penerimaan Unlocated and CSV for ED';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Warehouse Stock import...');
        
        // Run the seeder
        $seeder = new ImportWarehouseStockSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Warehouse Stock import completed!');
        
        return 0;
    }
}

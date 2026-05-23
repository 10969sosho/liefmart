<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportPenerimaanPKPSeeder;

class ImportPenerimaanPKP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:penerimaan-pkp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Penerimaan from MASTER - PKP.csv with Tax Category PKP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Penerimaan PKP import...');
        
        $csvFile = storage_path('app/imports/barangdatang/MASTER - PKP.csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }
        
        // Run the seeder
        $seeder = new ImportPenerimaanPKPSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Penerimaan PKP import completed!');
        
        return 0;
    }
}

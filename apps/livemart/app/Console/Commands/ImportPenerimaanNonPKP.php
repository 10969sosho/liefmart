<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ImportPenerimaanNonPKPSeeder;

class ImportPenerimaanNonPKP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:penerimaan-non-pkp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Penerimaan from MASTER - NON PKP.csv with Tax Category NON PKP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Penerimaan Non-PKP (NON PKP) import...');
        
        $csvFile = storage_path('app/imports/barangdatang/MASTER - NON PKP.csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }
        
        // Run the seeder
        $seeder = new ImportPenerimaanNonPKPSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Penerimaan Non-PKP (NON PKP) import completed!');
        
        return 0;
    }
}

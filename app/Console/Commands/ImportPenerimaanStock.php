<?php

namespace App\Console\Commands;

use Database\Seeders\ImportPenerimaanStockSeeder;
use Illuminate\Console\Command;

class ImportPenerimaanStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:penerimaan-stock
                            {file? : Path CSV file (optional). If provided, will import exactly this file}
                            {--tax= : Tax category name (e.g. HGN, LM). Required when file is provided}
                            {--po= : Nomor PO for the new penerimaan. Required when file is provided}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Penerimaan and automatically create Warehouse Stock with ED from CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Penerimaan & Stock import...');

        // Run the seeder
        $seeder = new ImportPenerimaanStockSeeder;
        $seeder->setCommand($this);
        $seeder->setImportOptions([
            'file' => $this->argument('file'),
            'tax' => $this->option('tax'),
            'po' => $this->option('po'),
        ]);
        $seeder->run();

        $this->info('Penerimaan & Stock import completed!');

        return 0;
    }
}

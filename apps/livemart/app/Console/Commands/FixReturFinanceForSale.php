<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OfflineSale;
use App\Models\ReturOfflineSale;
use App\Services\ReturFinanceService;

class FixReturFinanceForSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retur:fix-finance 
                            {--surat-jalan= : Surat jalan number of the offline sale}
                            {--all : Fix all offline sales that have completed returns}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess retur finance for offline sales that have completed returns but invoices were created after the retur';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ReturFinanceService $financeService)
    {
        $suratJalan = $this->option('surat-jalan');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        if (!$suratJalan && !$all) {
            $this->error('Please provide --surat-jalan or --all option');
            return Command::FAILURE;
        }

        if ($suratJalan) {
            $offlineSale = OfflineSale::withoutGlobalScopes()
                ->where('surat_jalan_number', $suratJalan)
                ->first();

            if (!$offlineSale) {
                $this->error("OfflineSale with surat_jalan_number '{$suratJalan}' not found");
                return Command::FAILURE;
            }

            $this->info("Found OfflineSale ID: {$offlineSale->id}, SJ: {$offlineSale->surat_jalan_number}");

            $returs = ReturOfflineSale::where('offline_sale_id', $offlineSale->id)
                ->where('status', 'selesai')
                ->get();

            if ($returs->isEmpty()) {
                $this->warn("No completed returns found for this sale");
                return Command::SUCCESS;
            }

            $this->info("Found {$returs->count()} completed return(s)");

            $this->processReturs($returs, $financeService, $dryRun);

            return Command::SUCCESS;
        }

        if ($all) {
            $this->info("Processing all offline sales with completed returns...");

            $offlineSales = OfflineSale::withoutGlobalScopes()
                ->whereHas('returOfflineSales', function($q) {
                    $q->where('status', 'selesai');
                })
                ->get();

            $this->info("Found {$offlineSales->count()} offline sale(s) with completed returns");

            $progressBar = $this->output->createProgressBar($offlineSales->count());
            $progressBar->start();

            foreach ($offlineSales as $offlineSale) {
                $returs = ReturOfflineSale::where('offline_sale_id', $offlineSale->id)
                    ->where('status', 'selesai')
                    ->get();

                $this->processReturs($returs, $financeService, $dryRun);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
            $this->info('All done!');
        }

        return Command::SUCCESS;
    }

    /**
     * Process returs and update finance
     */
    private function processReturs($returs, ReturFinanceService $financeService, bool $dryRun)
    {
        foreach ($returs as $retur) {
            $retur->load(['details.offlineSaleItem']);

            $invoices = $retur->offlineSale->getInvoices();

            if ($invoices->isEmpty()) {
                $this->warn("  Retur {$retur->kode_retur}: No invoices found to update");
                continue;
            }

            $this->info("  Retur {$retur->kode_retur}: Found {$invoices->count()} invoice(s)");

            if ($dryRun) {
                foreach ($invoices as $invoice) {
                    $this->line("    Would update invoice {$invoice->invoice_number} (current status: {$invoice->status})");
                }
            } else {
                $financeService->handleOfflineReturFinance($retur);
                $this->info("  Retur {$retur->kode_retur}: Finance reprocessed successfully");

                $retur->refresh();
                $invoices = $retur->offlineSale->getInvoices();
                foreach ($invoices as $invoice) {
                    $this->line("    Invoice {$invoice->invoice_number}: status now '{$invoice->status}'");
                }
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\FinanceOffline;

class FixFinanceOfflineNominal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:fix-nominal 
                            {--invoice-number= : Fix specific invoice by invoice number}
                            {--dry-run : Show what would be updated without making changes}
                            {--threshold=0.01 : Minimum difference to update (default: 0.01)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix finance_offlines nominal values by recalculating from offline_sales total_amount';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $invoiceNumber = $this->option('invoice-number');
        $threshold = (float) $this->option('threshold');
        
        $this->info('🔄 Fixing finance_offlines nominal values...');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Build query
        $query = FinanceOffline::with([
            'barangKeluarItems',
            'barangKeluarItems.offlineSaleItem',
            'barangKeluarItems.offlineSaleItem.offlineSale',
            'barangKeluarItems.offlineSaleItem.offlineSale.items'
        ]);

        if ($invoiceNumber) {
            $query->where('invoice_number', $invoiceNumber);
            $this->info("📋 Fixing invoice: {$invoiceNumber}");
        } else {
            $this->info("📋 Fixing all invoices...");
        }

        $invoices = $query->get();
        
        if ($invoices->isEmpty()) {
            $this->warn('⚠️  No invoices found');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$invoices->count()} invoice(s) to check");
        $this->newLine();

        $updatedCount = 0;
        $skippedCount = 0;
        $totalDifference = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($invoices->count());
        $progressBar->start();

        foreach ($invoices as $invoice) {
            try {
                $oldNominal = $invoice->nominal;
                $newNominal = $invoice->recalculateNominal();
                $difference = abs($oldNominal - $newNominal);

                if ($difference >= $threshold) {
                    if (!$isDryRun) {
                        $invoice->nominal = $newNominal;
                        $invoice->save();
                    }
                    
                    $updatedCount++;
                    $totalDifference += $difference;
                    
                    $this->newLine();
                    $this->line("✅ Invoice {$invoice->invoice_number}:");
                    $this->line("   Old: Rp " . number_format($oldNominal, 0, ',', '.'));
                    $this->line("   New: Rp " . number_format($newNominal, 0, ',', '.'));
                    $this->line("   Diff: Rp " . number_format($difference, 0, ',', '.'));
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'invoice' => $invoice->invoice_number,
                    'error' => $e->getMessage()
                ];
                $this->newLine();
                $this->error("❌ Error processing invoice {$invoice->invoice_number}: {$e->getMessage()}");
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Fix completed!");
        $this->info("📊 Summary:");
        $this->info("   - Updated: {$updatedCount} invoice(s)");
        $this->info("   - Skipped: {$skippedCount} invoice(s)");
        $this->info("   - Total difference: Rp " . number_format($totalDifference, 0, ',', '.'));
        
        if (!empty($errors)) {
            $this->warn("⚠️  Errors:");
            foreach ($errors as $error) {
                $this->warn("   - Invoice {$error['invoice']}: {$error['error']}");
            }
        }
        
        if ($isDryRun) {
            $this->warn("🔍 This was a dry run. Use without --dry-run to apply changes.");
        }

        return Command::SUCCESS;
    }
}


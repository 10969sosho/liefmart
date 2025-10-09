<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\FinanceOffline;
use App\Models\OfflineSale;

class UpdateFinanceOfflineDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:update-dates {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update tanggal_invoice in finance_offlines to match sale_date from offline_sales';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔄 Updating finance_offlines tanggal_invoice to match offline_sales sale_date...');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get all finance_offlines that are related to offline_sales
        $financeOfflines = FinanceOffline::whereHas('barangKeluarItems', function($query) {
            $query->whereNotNull('offline_sale_item_id');
        })->with(['barangKeluarItems.offlineSaleItem.offlineSale'])->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($financeOfflines as $finance) {
            // Get the related offline sale
            $offlineSale = null;
            foreach ($finance->barangKeluarItems as $barangKeluar) {
                if ($barangKeluar->offlineSaleItem && $barangKeluar->offlineSaleItem->offlineSale) {
                    $offlineSale = $barangKeluar->offlineSaleItem->offlineSale;
                    break;
                }
            }

            if ($offlineSale) {
                $oldDate = $finance->tanggal_invoice;
                $newDate = $offlineSale->sale_date;

                if ($oldDate != $newDate) {
                    if (!$isDryRun) {
                        $finance->update(['tanggal_invoice' => $newDate]);
                    }
                    
                    $this->line("📅 Finance ID {$finance->id}: {$oldDate} → {$newDate}");
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            } else {
                $this->warn("⚠️  Finance ID {$finance->id}: No related offline sale found");
                $skippedCount++;
            }
        }

        $this->info("✅ Update completed!");
        $this->info("📊 Summary:");
        $this->info("   - Updated: {$updatedCount} records");
        $this->info("   - Skipped: {$skippedCount} records");
        
        if ($isDryRun) {
            $this->warn("🔍 This was a dry run. Use without --dry-run to apply changes.");
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use Illuminate\Support\Facades\DB;

class FixSplitOrders extends Command
{
    protected $signature = 'tiktok:fix-split';
    protected $description = 'Merge incorrectly split invoices for specific orders';

    public function handle()
    {
        // The 3 problematic orders identified
        $orders = [
            '580540637438510352',
            '580647941514953775',
            '581271772368307437'
        ];

        $this->info("Starting fix for " . count($orders) . " orders...");

        foreach ($orders as $noOrder) {
            $this->info("\nProcessing Order: {$noOrder}");

            $transactions = TiktokFinancialTransaction::where('no_order', $noOrder)
                ->orderBy('id') // Keep the first created one as base
                ->get();

            if ($transactions->count() < 2) {
                $this->warn("  Skipping: Found " . $transactions->count() . " transaction(s). Nothing to merge.");
                continue;
            }

            // Start DB Transaction for safety
            DB::beginTransaction();
            try {
                $baseTrx = $transactions->first();
                $toMerge = $transactions->slice(1);

                $originalTotal = $transactions->sum('nominal_fix');
                $this->info("  Original Total Nominal Fix: " . number_format($originalTotal, 2));

                foreach ($toMerge as $trx) {
                    $this->line("  Merging Invoice: {$trx->no_invoice} into {$baseTrx->no_invoice}");

                    // Sum Nominals
                    $baseTrx->nominal_harga += $trx->nominal_harga;
                    $baseTrx->qty += $trx->qty;
                    $baseTrx->nominal_fix += $trx->nominal_fix;
                    $baseTrx->saldo_masuk += $trx->saldo_masuk;
                    $baseTrx->outstanding += $trx->outstanding;
                    $baseTrx->adjustment += $trx->adjustment;

                    // Sum Discounts
                    for ($i = 1; $i <= 12; $i++) {
                        $col = "nominal_diskon{$i}";
                        $baseTrx->$col += $trx->$col;
                    }

                    // Delete the merged transaction
                    $trx->delete();
                }

                // Recalculate or Update Percentages? 
                // For now, we trust the Nominals as requested by user ("Total Biaya").
                // If needed, we could recalculate percentages based on new nominals, 
                // but usually nominals are the source of truth for finance.
                
                $baseTrx->save();

                $newTotal = $baseTrx->nominal_fix;
                $this->info("  New Total Nominal Fix: " . number_format($newTotal, 2));

                if (abs($originalTotal - $newTotal) > 0.01) {
                    throw new \Exception("Total mismatch! Original: $originalTotal, New: $newTotal");
                }

                DB::commit();
                $this->info("  [SUCCESS] Merged successfully.");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  [FAILED] " . $e->getMessage());
            }
        }
        
        return 0;
    }
}

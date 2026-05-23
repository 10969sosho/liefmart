<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WarehouseStock;
use App\Models\ReturPenjualan;
use App\Models\ReturOfflineSale;
use App\Models\PenerimaanDetail;
use App\Models\BarangKeluar;
use App\Models\ReturPembelianDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FixMissingStock extends Command
{
    protected $signature = 'fix:missing-stock {--product_id= : Specific product ID} {--dry-run : Run without making changes}';
    protected $description = 'Fix missing WarehouseStock records';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $productId = $this->option('product_id');
        
        $this->info("Starting Missing Stock Fix..." . ($dryRun ? " [DRY RUN]" : ""));

        if ($productId) {
            $this->fixReturMissing($productId, $dryRun);
        } else {
            // Process all products? Maybe too heavy. Let's stick to user request logic if possible.
            // But for now, let's just support specific product or all returs.
            $this->fixReturMissing(null, $dryRun);
        }

        $this->info("Fix complete.");
    }

    private function fixReturMissing($targetProductId, $dryRun)
    {
        $this->info("\nChecking Retur Penjualan for missing stocks...");
        
        $query = ReturPenjualan::where('status', 'selesai')->with('details');
        
        if ($targetProductId) {
             $query->whereHas('details', function($q) use ($targetProductId) {
                 $q->where('product_id', $targetProductId);
             });
        }
        
        $returs = $query->get();
        
        foreach ($returs as $retur) {
            $detailsByProduct = $retur->details->groupBy('product_id');
            
            foreach ($detailsByProduct as $productId => $details) {
                if ($targetProductId && $productId != $targetProductId) continue;
                
                $sourceQty = $details->sum('qty');
                
                $stocks = WarehouseStock::where('source_type', 'retur_penjualan')
                    ->where('source_id', $retur->id)
                    ->where('product_id', $productId)
                    ->get();
                
                // If stocks exist, we assume FixStockBalance handles/handled it (unless they are 0 and need more?)
                // But my previous script handled existing stocks.
                // Here we specifically want to catch cases where NO STOCK exists or stock is insufficient and we want to ensure it matches.
                
                $currentStockQty = $stocks->sum('qty');
                
                if ($stocks->isEmpty()) {
                    // TOTALLY MISSING
                    $soldQty = 0; // No stock means no sold linked to it usually? 
                    // But check if any orphan BK exists (unlikely linked to non-existent WS)
                    
                    $targetStockQty = $sourceQty; // Assume none sold if stock record missing
                    
                    $this->line("  [MISSING RECORD] Retur #{$retur->id} Prod {$productId}: Should have {$targetStockQty}. Found 0 records.");
                    
                    if (!$dryRun && $targetStockQty > 0) {
                        $this->createStock('retur_penjualan', $retur->id, $productId, $targetStockQty, $retur->lokasi_id ?? 1); // Default location?
                    }
                } else {
                    // Stock exists but maybe 0?
                    // Re-check logic from FixStockBalance just in case
                    $stockIds = $stocks->pluck('id');
                    $soldQty = BarangKeluar::whereIn('warehouse_stock_id', $stockIds)->sum('qty');
                    $targetStockQty = max(0, $sourceQty - $soldQty);
                    
                    if ($currentStockQty < $targetStockQty) {
                         $diff = $targetStockQty - $currentStockQty;
                         $this->line("  [SHORTAGE] Retur #{$retur->id} Prod {$productId}: Target {$targetStockQty} > Current {$currentStockQty}. Diff {$diff}");
                         
                         if (!$dryRun) {
                             $stock = $stocks->first();
                             $stock->qty += $diff;
                             $stock->save();
                             $this->line("    -> Added {$diff} to WS #{$stock->id}");
                         }
                    }
                }
            }
        }
    }

    private function createStock($sourceType, $sourceId, $productId, $qty, $locationId)
    {
        // Need to know condition (Good/Bad). 
        // Retur details have 'kondisi'.
        // If we have mixed conditions, we should create separate stocks.
        
        if ($sourceType == 'retur_penjualan') {
            $details = ReturPenjualan::find($sourceId)->details->where('product_id', $productId);
            
            // Group by condition
            $goodQty = $details->where('kondisi', '!=', 'RUSAK')->sum('qty');
            $badQty = $details->where('kondisi', '==', 'RUSAK')->sum('qty');
            
            if ($goodQty > 0) {
                $ws = new WarehouseStock();
                $ws->product_id = $productId;
                $ws->source_type = $sourceType;
                $ws->source_id = $sourceId;
                $ws->qty = $goodQty;
                $ws->lokasi_id = $retur->lokasi_id ?? 1;
                $ws->source_date = $retur->created_at ?? now();
                $ws->is_damaged = 0;
                $ws->save();
                $this->line("    -> Created Stock (Good): {$goodQty}");
            }
            
            if ($badQty > 0) {
                $ws = new WarehouseStock();
                $ws->product_id = $productId;
                $ws->source_type = $sourceType;
                $ws->source_id = $sourceId;
                $ws->qty = $badQty;
                $ws->lokasi_id = $retur->lokasi_id ?? 1;
                $ws->source_date = $retur->created_at ?? now();
                $ws->is_damaged = 1;
                $ws->save();
                $this->line("    -> Created Stock (Damaged): {$badQty}");
            }
        }
    }
}

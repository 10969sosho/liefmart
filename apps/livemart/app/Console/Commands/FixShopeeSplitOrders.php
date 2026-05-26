<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeFinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FixShopeeSplitOrders extends Command
{
    protected $signature = 'shopee:fix-split';
    protected $description = 'Scan and merge incorrectly split Shopee orders (Single Tax ID but Multiple Invoices)';

    public function handle()
    {
        $this->info("Scanning Shopee Orders for Incorrect Splits...");

        // 1. Find Shopee orders with > 1 transaction
        // We group by no_order and count
        $duplicates = ShopeeFinancialTransaction::select('no_order', DB::raw('count(*) as count'))
            ->groupBy('no_order')
            ->having('count', '>', 1)
            ->get();

        $this->info("Found " . $duplicates->count() . " Shopee orders with multiple invoices.");
        
        $incorrectSplits = [];

        foreach ($duplicates as $dup) {
            $noOrder = $dup->no_order;
            
            // Get the order details to check items and tax IDs
            $order = Order::with(['orderItems.barangKeluar.warehouseStock', 'orderItems.warehouseStock'])
                ->where('order_number', $noOrder)
                ->first();

            if (!$order) {
                // $this->warn("Order $noOrder not found in orders table. Skipping.");
                continue;
            }

            $allTaxIds = [];

            foreach ($order->orderItems as $item) {
                // Check direct stock (Single Item)
                if ($item->warehouseStock) {
                    $allTaxIds[] = $item->warehouseStock->tax_id;
                }
                
                // Check BarangKeluar (Bundle Item components)
                if ($item->barangKeluar && $item->barangKeluar->count() > 0) {
                    foreach ($item->barangKeluar as $bk) {
                        if ($bk->warehouseStock) {
                            $allTaxIds[] = $bk->warehouseStock->tax_id;
                        }
                    }
                }
            }

            // If no stock data found (maybe old order or sync issue), skip safely
            if (empty($allTaxIds)) {
                continue;
            }

            $uniqueTaxIds = array_unique($allTaxIds);
            sort($uniqueTaxIds);
            
            // Logic:
            // If Single Tax ID (count == 1) AND Multiple Invoices -> INCORRECT -> MERGE
            // If Mixed Tax IDs (count > 1) AND Multiple Invoices -> CORRECT -> SKIP
            
            if (count($uniqueTaxIds) === 1) {
                $incorrectSplits[] = [
                    'no_order' => $noOrder,
                    'tax_id' => $uniqueTaxIds[0],
                    'inv_count' => $dup->count
                ];
            }
        }

        $countIncorrect = count($incorrectSplits);
        $this->info("Identified $countIncorrect orders that are incorrectly split (Single Tax ID).");

        if ($countIncorrect === 0) {
            $this->info("All split orders appear to be valid (Mixed Tax IDs). No action needed.");
            return 0;
        }

        // Display list
        $this->table(['Order Number', 'Tax ID', 'Invoice Count'], array_slice($incorrectSplits, 0, 20));
        if ($countIncorrect > 20) {
            $this->info("... and " . ($countIncorrect - 20) . " more.");
        }

        // Auto-Merge as requested ("kalau ada merge")
        $this->info("\nMerging incorrect splits...");

        foreach ($incorrectSplits as $target) {
            $this->mergeTransactions($target['no_order']);
        }

        $this->info("Merge process completed.");
        return 0;
    }

    private function mergeTransactions($noOrder)
    {
        DB::beginTransaction();
        try {
            $transactions = ShopeeFinancialTransaction::where('no_order', $noOrder)
                ->orderBy('id')
                ->get();

            if ($transactions->count() < 2) {
                DB::rollBack();
                return;
            }

            $baseTrx = $transactions->first();
            $toMerge = $transactions->slice(1);

            foreach ($toMerge as $trx) {
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

                $trx->delete();
            }

            $baseTrx->save();
            DB::commit();
            $this->line("  Merged Order: $noOrder");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  Failed to merge $noOrder: " . $e->getMessage());
        }
    }
}

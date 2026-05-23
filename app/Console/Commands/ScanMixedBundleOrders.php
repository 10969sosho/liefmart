<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\TiktokFinancialTransaction;
use App\Models\ShopeeFinancialTransaction;

class ScanMixedBundleOrders extends Command
{
    protected $signature = 'scan:mixed-bundles';
    protected $description = 'Scan for orders containing Bundles (Paket) with mixed Tax IDs (PKP/NON PKP)';

    public function handle()
    {
        $this->info("Scanning for Order Items with Mixed Tax IDs (based on BarangKeluar)...");

        $mixedBundleOrders = [];
        $totalItemsChecked = 0;
        $totalMixedFound = 0;

        // Eager load barangKeluar and its warehouseStock
        Order::with(['orderItems.barangKeluar.warehouseStock'])
             ->chunk(100, function ($orders) use (&$mixedBundleOrders, &$totalItemsChecked, &$totalMixedFound) {
                 
            foreach ($orders as $order) {
                foreach ($order->orderItems as $item) {
                    $totalItemsChecked++;
                    
                    $taxIds = [];
                    $stockDetails = [];
                    
                    // Check actual stock usage via BarangKeluar
                    foreach ($item->barangKeluar as $bk) {
                        if ($bk->warehouseStock) {
                            $tid = $bk->warehouseStock->tax_id;
                            $taxIds[] = $tid;
                            
                            $productName = $bk->warehouseStock->product->name ?? 'Unknown';
                            $stockDetails[] = "$productName (Tax: $tid)";
                        }
                    }
                    
                    if (empty($taxIds)) continue;

                    $uniqueTaxIds = array_unique($taxIds);
                    
                    // Check for ANY mixed tax IDs
                    if (count($uniqueTaxIds) > 1) {
                         $totalMixedFound++;
                         $hasPKP = in_array(3, $uniqueTaxIds);
                         $hasNONPKP = in_array(4, $uniqueTaxIds);
                         
                         // Check Invoice Count based on Platform
                         $invCount = 0;
                         if (str_contains(strtolower($order->platform), 'tiktok')) {
                             $invCount = TiktokFinancialTransaction::where('no_order', $order->order_number)->count();
                         } elseif (str_contains(strtolower($order->platform), 'shopee')) {
                             $invCount = ShopeeFinancialTransaction::where('no_order', $order->order_number)->count();
                         }
                         
                         $mixedBundleOrders[] = [
                            'platform' => $order->platform,
                            'order_number' => $order->order_number,
                            'item_name' => $item->platformProduct->product_name ?? 'Unknown Item',
                            'tax_ids' => implode(', ', $uniqueTaxIds),
                            'inv_count' => $invCount,
                            'status' => ($invCount > 1) ? 'Split (OK)' : 'Single (Need Split?)'
                        ];
                    }
                }
            }
        });

        $this->info("Total Items Checked: $totalItemsChecked");
        $this->info("Total Mixed Items Found: $totalMixedFound");

        if (empty($mixedBundleOrders)) {
            $this->info("No orders found with Items containing mixed Tax IDs.");
        } else {
            $this->info("Found " . count($mixedBundleOrders) . " orders with Mixed Items:");
            $this->table(
                ['Platform', 'Order Number', 'Item Name', 'Tax IDs', 'Inv Count', 'Status'],
                $mixedBundleOrders
            );
        }

        return 0;
    }
}

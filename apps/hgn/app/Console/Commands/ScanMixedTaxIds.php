<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\InvoiceSequence;

class ScanMixedTaxIds extends Command
{
    protected $signature = 'order:scan-mixed-tax-ids';
    protected $description = 'Find orders containing items with mixed tax_ids that belong to the same category (Kopi PKP)';

    public function handle()
    {
        $this->info('Scanning for Orders with Mixed Tax IDs within the same Category...');

        // Fetch Tax Categories from DB to be sure
        $taxCategories = \App\Models\TaxCategory::withoutGlobalScopes()->get(['id', 'name']);
        $this->info("Available Tax Categories in DB:");
        foreach ($taxCategories as $tc) {
            $this->line("  ID {$tc->id}: {$tc->name}");
        }
        
        // Define Categories based on TaxCategory ID + PKP/Non-PKP logic
        // Based on TiktokFinancialTransaction::generateInvoiceNumber:
        // KOPI PKP: 1, 2, 5, 6 -> but split by taxStatus
        // Tax Status PKP: 1, 3, 5, 7
        // Tax Status NON-PKP: Others (2, 4, 6, 8 presumably)
        
        // Let's deduce from generateInvoiceNumber logic:
        // Category KOPI: if tax_id IN [1, 2, 5, 6]
        // Category SKINCARE: if tax_id NOT IN [1, 2, 5, 6] (so 3, 4, 7, 8 etc)
        
        // Status PKP: if tax_id IN [1, 3, 5, 7]
        // Status NON-PKP: if tax_id NOT IN [1, 3, 5, 7]
        
        // So:
        // KOPI PKP = (KOPI) AND (PKP) = [1, 5] (intersection of [1,2,5,6] and [1,3,5,7])
        // KOPI NON-PKP = (KOPI) AND (NON-PKP) = [2, 6] (intersection of [1,2,5,6] and NOT [1,3,5,7])
        // SKINCARE PKP = (SKINCARE) AND (PKP) = [3, 7] (intersection of NOT [1,2,5,6] and [1,3,5,7])
        // SKINCARE NON-PKP = (SKINCARE) AND (NON-PKP) = [4, 8] (intersection of NOT [1,2,5,6] and NOT [1,3,5,7])
        
        $categories = [
            'KOPI_PKP' => [1, 5],
            'KOPI_NONPKP' => [2, 6],
            'SKIN_PKP' => [3, 7],
            'SKIN_NONPKP' => [4, 8]
        ];

        $this->info("\nChecking based on mapping:");
        foreach ($categories as $name => $ids) {
            $this->line("  {$name}: " . implode(', ', $ids));
        }

        // Fetch orders with their items and warehouse stock info
        // We need to check orders that have multiple items with different tax_ids
        
        // Optimisation: We can filter orders that have at least 2 items
        $orders = Order::whereHas('orderItems', function($q) {
                $q->select('order_id')
                  ->groupBy('order_id')
                  ->havingRaw('COUNT(*) > 1');
            })
            ->with(['orderItems.warehouseStock'])
            ->chunk(100, function($orders) use ($categories) {
                foreach ($orders as $order) {
                    $taxIds = [];
                    $items = [];
                    
                    foreach ($order->orderItems as $item) {
                        if ($item->warehouseStock) {
                            $tid = $item->warehouseStock->tax_id;
                            $taxIds[] = $tid;
                            $items[] = [
                                'product' => $item->warehouseStock->product->name ?? 'Unknown',
                                'tax_id' => $tid
                            ];
                        }
                    }
                    
                    $uniqueTaxIds = array_unique($taxIds);
                    
                    if (count($uniqueTaxIds) > 1) {
                        // Check if these different tax_ids belong to the SAME category
                        foreach ($categories as $catName => $catIds) {
                            $intersect = array_intersect($uniqueTaxIds, $catIds);
                            
                            // If we have at least 2 different tax_ids from the SAME category group
                            if (count($intersect) > 1) {
                                $this->info("\nFound Mixed Tax IDs for Order: {$order->order_number} ({$catName})");
                                $this->line("  Tax IDs found: " . implode(', ', $intersect));
                                $this->line("  Items:");
                                foreach ($items as $item) {
                                    if (in_array($item['tax_id'], $catIds)) {
                                        $this->line("    - {$item['product']} (Tax ID: {$item['tax_id']})");
                                    }
                                }
                            }
                        }
                    }
                }
            });

        $this->info("\nScan complete.");
        return 0;
    }
}

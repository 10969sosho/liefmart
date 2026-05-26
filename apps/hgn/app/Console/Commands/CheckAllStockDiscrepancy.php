<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Http\Controllers\WarehouseStockController;
use Illuminate\Support\Facades\DB;

class CheckAllStockDiscrepancy extends Command
{
    protected $signature = 'stock:check-all-discrepancy';
    protected $description = 'Check stock discrepancy for all products between warehouse_stock and mutation calculation';

    public function handle()
    {
        $this->info("Checking stock discrepancy for all products...");
        $this->line('');
        
        // Get all products that have warehouse stock
        $products = Product::whereHas('warehouseStocks', function($q) {
                $q->where('is_damaged', false);
            })
            ->with(['warehouseStocks' => function($q) {
                $q->where('is_damaged', false);
            }])
            ->get();
        
        $this->info("Total products with stock: " . $products->count());
        $this->line('');
        
        $discrepancies = [];
        $controller = new WarehouseStockController();
        $reflection = new \ReflectionClass($controller);
        $calculateMethod = $reflection->getMethod('calculateRunningBalance');
        $calculateMethod->setAccessible(true);
        
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();
        
        foreach ($products as $product) {
            // Calculate warehouse_stock.qty (old way - menu display)
            $warehouseStockQty = WarehouseStock::where('product_id', $product->id)
                ->where('is_damaged', false)
                ->sum('qty');
            
            // Calculate using mutation logic (correct way)
            $mutationQty = $calculateMethod->invoke($controller, $product->id);
            
            // Check if there's a discrepancy
            if (abs($warehouseStockQty - $mutationQty) > 0.01) { // Allow small floating point differences
                $discrepancies[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku ?? 'N/A',
                    'name' => $product->name,
                    'warehouse_stock_qty' => $warehouseStockQty,
                    'mutation_qty' => $mutationQty,
                    'difference' => $warehouseStockQty - $mutationQty
                ];
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->line('');
        $this->line('');
        
        // Display results
        if (count($discrepancies) > 0) {
            $this->warn("Found " . count($discrepancies) . " products with discrepancies:");
            $this->line('');
            
            // Sort by difference (largest first)
            usort($discrepancies, function($a, $b) {
                return abs($b['difference']) <=> abs($a['difference']);
            });
            
            $this->table(
                ['No', 'Product ID', 'SKU', 'Product Name', 'Menu (WS Qty)', 'Mutasi (Correct)', 'Selisih'],
                array_map(function($item, $index) {
                    return [
                        $index + 1,
                        $item['product_id'],
                        $item['sku'],
                        substr($item['name'], 0, 50) . (strlen($item['name']) > 50 ? '...' : ''),
                        number_format($item['warehouse_stock_qty'], 2),
                        number_format($item['mutation_qty'], 2),
                        number_format($item['difference'], 2)
                    ];
                }, $discrepancies, array_keys($discrepancies))
            );
            
            $this->line('');
            $this->info("Summary:");
            $this->line("  Total products checked: " . $products->count());
            $this->line("  Products with discrepancies: " . count($discrepancies));
            $this->line("  Products without discrepancies: " . ($products->count() - count($discrepancies)));
            
            // Show top 10 largest discrepancies
            $this->line('');
            $this->info("Top 10 largest discrepancies:");
            $top10 = array_slice($discrepancies, 0, 10);
            foreach ($top10 as $index => $item) {
                $this->line(sprintf(
                    "  %d. %s (ID: %d) - Menu: %s, Mutasi: %s, Selisih: %s",
                    $index + 1,
                    $item['name'],
                    $item['product_id'],
                    number_format($item['warehouse_stock_qty'], 2),
                    number_format($item['mutation_qty'], 2),
                    number_format($item['difference'], 2)
                ));
            }
        } else {
            $this->info("✅ No discrepancies found! All products have matching stock quantities.");
        }
        
        return 0;
    }
}









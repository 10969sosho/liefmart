<?php

namespace App\Console\Commands;

use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanDetail;
use App\Models\Order;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixDuplicateReturDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retur:fix-duplicates {--order= : Order number to fix} {--retur= : Retur code to fix} {--all : Fix all duplicates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate retur penjualan details by merging them into single records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orderNumber = $this->option('order');
        $returCode = $this->option('retur');
        $fixAll = $this->option('all');

        if (!$orderNumber && !$returCode && !$fixAll) {
            $this->error('Please provide --order, --retur, or --all option');
            return Command::FAILURE;
        }

        try {
            DB::beginTransaction();

            if ($orderNumber) {
                $this->info("Fixing retur for order: {$orderNumber}");
                $this->fixByOrderNumber($orderNumber);
            } elseif ($returCode) {
                $this->info("Fixing retur: {$returCode}");
                $this->fixByReturCode($returCode);
            } elseif ($fixAll) {
                $this->info("Fixing all duplicate retur details...");
                $this->fixAllDuplicates();
            }

            DB::commit();
            $this->info('Successfully fixed duplicate retur details!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            Log::error('FixDuplicateReturDetails error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Fix duplicates for a specific order number
     */
    private function fixByOrderNumber($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            $this->error("Order not found: {$orderNumber}");
            return;
        }

        $returPenjualans = ReturPenjualan::where('order_id', $order->id)
            ->with(['details' => function($query) {
                $query->with('product');
            }])
            ->get();

        if ($returPenjualans->isEmpty()) {
            $this->warn("No retur found for order: {$orderNumber}");
            return;
        }

        foreach ($returPenjualans as $returPenjualan) {
            $this->fixReturDetails($returPenjualan);
        }
    }

    /**
     * Fix duplicates for a specific retur code
     */
    private function fixByReturCode($returCode)
    {
        $returPenjualan = ReturPenjualan::where('kode_retur', $returCode)
            ->with(['details' => function($query) {
                $query->with('product');
            }])
            ->first();

        if (!$returPenjualan) {
            $this->error("Retur not found: {$returCode}");
            return;
        }

        $this->fixReturDetails($returPenjualan);
    }

    /**
     * Fix all duplicates
     */
    private function fixAllDuplicates()
    {
        // Find all retur penjualans with potential duplicates
        $returPenjualans = ReturPenjualan::with(['details' => function($query) {
                $query->with('product');
            }])
            ->get();

        $fixedCount = 0;
        foreach ($returPenjualans as $returPenjualan) {
            if ($this->hasDuplicates($returPenjualan)) {
                $this->info("Fixing retur: {$returPenjualan->kode_retur}");
                $this->fixReturDetails($returPenjualan);
                $fixedCount++;
            }
        }

        $this->info("Fixed {$fixedCount} retur(s) with duplicates");
    }

    /**
     * Check if retur has duplicate details
     */
    private function hasDuplicates($returPenjualan)
    {
        $details = $returPenjualan->details;
        
        // Group by order_item_id and product_id
        $grouped = $details->groupBy(function($detail) {
            return $detail->order_item_id . '_' . $detail->product_id;
        });

        // Check if any group has more than 1 item
        foreach ($grouped as $group) {
            if ($group->count() > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fix duplicate details for a retur penjualan
     */
    private function fixReturDetails($returPenjualan)
    {
        $this->info("Processing retur: {$returPenjualan->kode_retur} (ID: {$returPenjualan->id})");

        $details = $returPenjualan->details;
        
        // Group by order_item_id and product_id
        $grouped = $details->groupBy(function($detail) {
            return $detail->order_item_id . '_' . $detail->product_id;
        });

        $fixedCount = 0;
        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                $this->warn("  Found duplicate: order_item_id={$group->first()->order_item_id}, product_id={$group->first()->product_id}, count={$group->count()}");
                
                // Get the first detail as the base
                $baseDetail = $group->first();
                $otherDetails = $group->slice(1);
                
                // Calculate total qty
                $totalQty = $group->sum('qty');
                $this->info("  Total qty: {$totalQty}");
                
                // Get order item to check original qty
                $orderItem = $baseDetail->orderItem;
                if ($orderItem) {
                    $barangKeluarItems = $orderItem->barangKeluar()->with('warehouseStock')->get();
                    $totalBarangKeluarQty = $barangKeluarItems->sum('qty');
                    
                    $this->info("  Order item qty: {$orderItem->quantity}, Barang keluar total: {$totalBarangKeluarQty}");
                    
                    // Validate total qty doesn't exceed barang keluar
                    if ($totalQty > $totalBarangKeluarQty) {
                        $this->warn("  Warning: Total retur qty ({$totalQty}) exceeds barang keluar qty ({$totalBarangKeluarQty})");
                        // Adjust to barang keluar qty
                        $totalQty = $totalBarangKeluarQty;
                    }
                }
                
                // Update base detail with total qty
                $oldQty = $baseDetail->qty;
                $baseDetail->qty = $totalQty;
                $baseDetail->save();
                $this->info("  Updated base detail ID {$baseDetail->id}: {$oldQty} -> {$totalQty}");
                
                // Fix duplicate warehouse stocks for this retur and product
                $warehouseStocks = WarehouseStock::where('source_type', 'retur_penjualan')
                    ->where('source_id', $returPenjualan->id)
                    ->where('product_id', $baseDetail->product_id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $totalStockQty = $warehouseStocks->sum('qty');
                $this->info("  Current warehouse stock records: {$warehouseStocks->count()}, Total qty: {$totalStockQty}");
                
                if ($warehouseStocks->count() > 1) {
                    $this->warn("  Found {$warehouseStocks->count()} duplicate warehouse stock records, merging...");
                    
                    // Get the first stock as the base
                    $baseStock = $warehouseStocks->first();
                    $otherStocks = $warehouseStocks->slice(1);
                    
                    // Update base stock qty to match retur detail qty
                    $oldStockQty = $baseStock->qty;
                    $baseStock->qty = $totalQty;
                    $baseStock->save();
                    $this->info("  Updated base warehouse stock ID {$baseStock->id}: {$oldStockQty} -> {$totalQty}");
                    
                    // Delete other duplicate stocks
                    foreach ($otherStocks as $stock) {
                        $this->info("  Deleting duplicate warehouse stock ID {$stock->id} (qty: {$stock->qty})");
                        $stock->delete();
                    }
                } elseif ($warehouseStocks->count() == 1) {
                    // Only one stock record, but check if qty matches
                    $stock = $warehouseStocks->first();
                    if (abs($stock->qty - $totalQty) > 0.01) {
                        $this->warn("  Adjusting warehouse stock qty from {$stock->qty} to {$totalQty}");
                        $stock->qty = $totalQty;
                        $stock->save();
                    }
                } else {
                    $this->warn("  Warning: No warehouse stock found for this retur and product!");
                }
                
                // Delete other duplicate details
                foreach ($otherDetails as $detail) {
                    $this->info("  Deleting duplicate detail ID {$detail->id} (qty: {$detail->qty})");
                    $detail->delete();
                }
                
                $fixedCount++;
            }
        }

        if ($fixedCount > 0) {
            $this->info("  Fixed {$fixedCount} duplicate group(s)");
        } else {
            $this->info("  No duplicates found");
        }
        
        // Also fix warehouse stock duplicates for all products in this retur
        $this->fixWarehouseStockDuplicates($returPenjualan);
    }
    
    /**
     * Fix duplicate warehouse stocks for a retur penjualan
     */
    private function fixWarehouseStockDuplicates($returPenjualan)
    {
        $this->info("  Checking warehouse stock duplicates...");
        
        // Get all unique products in this retur
        $productIds = $returPenjualan->details->pluck('product_id')->unique();
        
        foreach ($productIds as $productId) {
            $warehouseStocks = WarehouseStock::where('source_type', 'retur_penjualan')
                ->where('source_id', $returPenjualan->id)
                ->where('product_id', $productId)
                ->orderBy('created_at', 'asc')
                ->get();
            
            if ($warehouseStocks->count() > 1) {
                $this->warn("  Found {$warehouseStocks->count()} duplicate warehouse stock records for product ID {$productId}");
                
                // Get retur detail for this product
                $returDetail = $returPenjualan->details->where('product_id', $productId)->first();
                if (!$returDetail) {
                    $this->warn("  No retur detail found for product ID {$productId}, skipping...");
                    continue;
                }
                
                $expectedQty = $returDetail->qty;
                $totalStockQty = $warehouseStocks->sum('qty');
                
                $this->info("  Expected qty from retur detail: {$expectedQty}, Total warehouse stock qty: {$totalStockQty}");
                
                // Get the first stock as the base
                $baseStock = $warehouseStocks->first();
                $otherStocks = $warehouseStocks->slice(1);
                
                // Update base stock qty to match retur detail qty
                $oldStockQty = $baseStock->qty;
                $baseStock->qty = $expectedQty;
                $baseStock->save();
                $this->info("  Updated base warehouse stock ID {$baseStock->id}: {$oldStockQty} -> {$expectedQty}");
                
                // Delete other duplicate stocks
                foreach ($otherStocks as $stock) {
                    $this->info("  Deleting duplicate warehouse stock ID {$stock->id} (qty: {$stock->qty})");
                    $stock->delete();
                }
            } elseif ($warehouseStocks->count() == 1) {
                // Only one stock record, but check if qty matches retur detail
                $stock = $warehouseStocks->first();
                $returDetail = $returPenjualan->details->where('product_id', $productId)->first();
                
                if ($returDetail && abs($stock->qty - $returDetail->qty) > 0.01) {
                    $this->warn("  Adjusting warehouse stock qty for product ID {$productId} from {$stock->qty} to {$returDetail->qty}");
                    $stock->qty = $returDetail->qty;
                    $stock->save();
                }
            }
        }
    }
}

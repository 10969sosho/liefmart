<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLazadaBarangKeluar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:lazada-barang-keluar {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing BarangKeluar records for Lazada orders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find Lazada platform
        $lazadaPlatform = Platform::where('name', 'LIKE', '%Lazada%')->first();
        
        if (!$lazadaPlatform) {
            $this->error('❌ Lazada platform not found!');
            return Command::FAILURE;
        }

        $this->info("✅ Found Lazada platform: {$lazadaPlatform->name} (ID: {$lazadaPlatform->id})");

        // Get all Lazada orders with their order items
        $lazadaOrders = Order::where('platform_id', $lazadaPlatform->id)
            ->with([
                'orderItems.warehouseStock',
                'orderItems.platformProduct.mappingBarang',
                'platform'
            ])
            ->get();

        if ($lazadaOrders->isEmpty()) {
            $this->info('ℹ️  No Lazada orders found.');
            return Command::SUCCESS;
        }

        $this->info("📦 Found {$lazadaOrders->count()} Lazada order(s)");

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalSkipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lazadaOrders as $order) {
                $this->line("");
                $this->info("📋 Processing Order: {$order->order_number} (ID: {$order->id})");
                $this->line("   Date: {$order->tanggal}");

                foreach ($order->orderItems as $orderItem) {
                    $totalProcessed++;

                    // Check if BarangKeluar already exists for this order item
                    $existingBarangKeluar = BarangKeluar::where('order_item_id', $orderItem->id)->first();

                    if ($existingBarangKeluar) {
                        $this->line("   ⏭️  OrderItem ID {$orderItem->id}: BarangKeluar already exists (ID: {$existingBarangKeluar->id})");
                        $totalSkipped++;
                        continue;
                    }

                    // Check if order item has warehouse_stock_id
                    if (!$orderItem->warehouse_stock_id) {
                        $this->warn("   ⚠️  OrderItem ID {$orderItem->id}: No warehouse_stock_id found. Skipping...");
                        $totalSkipped++;
                        $errors[] = "OrderItem ID {$orderItem->id} (Order: {$order->order_number}): No warehouse_stock_id";
                        continue;
                    }

                    // Get warehouse stock
                    $warehouseStock = $orderItem->warehouseStock;
                    if (!$warehouseStock) {
                        $this->warn("   ⚠️  OrderItem ID {$orderItem->id}: Warehouse stock not found (ID: {$orderItem->warehouse_stock_id}). Skipping...");
                        $totalSkipped++;
                        $errors[] = "OrderItem ID {$orderItem->id} (Order: {$order->order_number}): Warehouse stock not found";
                        continue;
                    }

                    // Calculate qty - need to check mapping
                    // For now, use order_item.quantity as base
                    // In real scenario, this should be multiplied by mapping quantity
                    // But since we don't have the mapping info, we'll use a reasonable default
                    $qty = $orderItem->quantity;
                    
                    // Try to get mapping quantity if possible
                    if ($orderItem->platformProduct && $orderItem->platformProduct->mappingBarang) {
                        $activeMappings = $orderItem->platformProduct->mappingBarang->where('is_active', true);
                        if ($activeMappings->isNotEmpty()) {
                            $totalMappingQty = $activeMappings->sum('quantity');
                            if ($totalMappingQty > 0) {
                                $qty = $orderItem->quantity * $totalMappingQty;
                            }
                        }
                    }

                    // Prepare tanggal_keluar
                    $tanggalKeluar = $order->tanggal;
                    if (is_string($tanggalKeluar)) {
                        $tanggalKeluar = \Carbon\Carbon::parse($tanggalKeluar)->format('Y-m-d');
                    } elseif ($tanggalKeluar instanceof \Carbon\Carbon) {
                        $tanggalKeluar = $tanggalKeluar->format('Y-m-d');
                    } else {
                        $tanggalKeluar = now()->format('Y-m-d');
                    }

                    // Create catatan
                    $platformName = $order->platform->name ?? 'Lazada';
                    $catatan = "Penjualan online {$platformName} - Order #{$order->order_number}";

                    if (!$isDryRun) {
                        // Create BarangKeluar
                        $kodeBarangKeluar = BarangKeluar::generateKode();
                        
                        BarangKeluar::create([
                            'kode_barang_keluar' => $kodeBarangKeluar,
                            'order_item_id' => $orderItem->id,
                            'warehouse_stock_id' => $warehouseStock->id,
                            'qty' => $qty,
                            'tanggal_keluar' => $tanggalKeluar,
                            'catatan' => $catatan,
                        ]);

                        $this->info("   ✅ Created BarangKeluar for OrderItem ID {$orderItem->id}:");
                        $this->line("      - Kode: {$kodeBarangKeluar}");
                        $this->line("      - Qty: {$qty}");
                        $this->line("      - Warehouse Stock ID: {$warehouseStock->id}");
                        $this->line("      - Tanggal: {$tanggalKeluar}");
                        $totalCreated++;
                    } else {
                        $this->info("   🔍 Would create BarangKeluar for OrderItem ID {$orderItem->id}:");
                        $this->line("      - Qty: {$qty}");
                        $this->line("      - Warehouse Stock ID: {$warehouseStock->id}");
                        $this->line("      - Tanggal: {$tanggalKeluar}");
                        $this->line("      - Catatan: {$catatan}");
                        $totalCreated++;
                    }
                }
            }

            if ($isDryRun) {
                $this->line("");
                $this->warn("🔍 DRY RUN - No changes were made");
                DB::rollBack();
            } else {
                DB::commit();
                $this->line("");
                $this->info("✅ Transaction committed successfully");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // Summary
        $this->line("");
        $this->info("📊 Summary:");
        $this->line("   Total OrderItems processed: {$totalProcessed}");
        $this->line("   BarangKeluar created: {$totalCreated}");
        $this->line("   Skipped: {$totalSkipped}");

        if (!empty($errors)) {
            $this->line("");
            $this->warn("⚠️  Warnings ({$totalSkipped}):");
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
        }

        return Command::SUCCESS;
    }
}

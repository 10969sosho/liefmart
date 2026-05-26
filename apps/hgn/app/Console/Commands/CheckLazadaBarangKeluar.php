<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLazadaBarangKeluar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:lazada-barang-keluar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if there are Lazada orders and their related barang_keluar records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Checking Lazada orders and barang_keluar...');
        $this->line('');

        // Find Lazada platform
        $lazadaPlatform = Platform::where('name', 'LIKE', '%Lazada%')
            ->orWhereRaw('LOWER(name) = ?', ['lazada'])
            ->first();
        
        if (!$lazadaPlatform) {
            $this->error('❌ Lazada platform not found!');
            return Command::FAILURE;
        }

        $this->info("✅ Found Lazada platform: {$lazadaPlatform->name} (ID: {$lazadaPlatform->id})");
        $this->line('');

        // Get all Lazada orders
        $lazadaOrders = Order::where('platform_id', $lazadaPlatform->id)
            ->with(['orderItems', 'platform'])
            ->get();

        if ($lazadaOrders->isEmpty()) {
            $this->warn('ℹ️  No Lazada orders found in database.');
            return Command::SUCCESS;
        }

        $this->info("📦 Found {$lazadaOrders->count()} Lazada order(s)");
        $this->line('');

        // Check barang_keluar for Lazada orders
        $totalOrderItems = 0;
        $totalWithBarangKeluar = 0;
        $totalWithoutBarangKeluar = 0;

        $ordersWithoutBarangKeluar = [];
        $ordersWithBarangKeluar = [];

        foreach ($lazadaOrders as $order) {
            $orderItems = $order->orderItems;
            $totalOrderItems += $orderItems->count();

            $orderHasBarangKeluar = false;
            $orderMissingBarangKeluar = false;
            $orderItemDetails = [];

            foreach ($orderItems as $orderItem) {
                $barangKeluar = BarangKeluar::where('order_item_id', $orderItem->id)->first();
                
                if ($barangKeluar) {
                    $totalWithBarangKeluar++;
                    $orderHasBarangKeluar = true;
                    $orderItemDetails[] = [
                        'order_item_id' => $orderItem->id,
                        'has_barang_keluar' => true,
                        'barang_keluar_id' => $barangKeluar->id,
                        'kode_barang_keluar' => $barangKeluar->kode_barang_keluar,
                    ];
                } else {
                    $totalWithoutBarangKeluar++;
                    $orderMissingBarangKeluar = true;
                    $orderItemDetails[] = [
                        'order_item_id' => $orderItem->id,
                        'has_barang_keluar' => false,
                    ];
                }
            }

            if ($orderMissingBarangKeluar) {
                $ordersWithoutBarangKeluar[] = [
                    'order' => $order,
                    'order_items' => $orderItemDetails,
                ];
            } else {
                $ordersWithBarangKeluar[] = [
                    'order' => $order,
                    'order_items' => $orderItemDetails,
                ];
            }
        }

        // Display summary
        $this->info('📊 Summary:');
        $this->line("   Total Orders: {$lazadaOrders->count()}");
        $this->line("   Total Order Items: {$totalOrderItems}");
        $this->line("   Order Items WITH barang_keluar: {$totalWithBarangKeluar}");
        $this->line("   Order Items WITHOUT barang_keluar: {$totalWithoutBarangKeluar}");
        $this->line('');

        // Display orders without barang_keluar
        if (!empty($ordersWithoutBarangKeluar)) {
            $this->warn("⚠️  Orders WITHOUT barang_keluar ({$totalWithoutBarangKeluar} order items):");
            $this->line('');
            
            foreach ($ordersWithoutBarangKeluar as $data) {
                $order = $data['order'];
                $this->line("   📋 Order: {$order->order_number} (ID: {$order->id})");
                $this->line("      Date: {$order->tanggal}");
                $this->line("      Order Items:");
                
                foreach ($data['order_items'] as $item) {
                    if (!$item['has_barang_keluar']) {
                        $this->line("         ❌ OrderItem ID {$item['order_item_id']}: NO barang_keluar");
                    }
                }
                $this->line('');
            }
        } else {
            $this->info("✅ All Lazada order items have barang_keluar records!");
        }

        // Display orders with barang_keluar (optional, can be commented out)
        // Uncomment below if you want to see orders that already have barang_keluar
        if (!empty($ordersWithBarangKeluar)) {
            $this->info("✅ Orders WITH barang_keluar:");
            $this->line('');
            
            foreach ($ordersWithBarangKeluar as $data) {
                $order = $data['order'];
                $this->line("   📋 Order: {$order->order_number} (ID: {$order->id})");
                
                foreach ($data['order_items'] as $item) {
                    if ($item['has_barang_keluar']) {
                        $this->line("         ✅ OrderItem ID {$item['order_item_id']}: {$item['kode_barang_keluar']}");
                    }
                }
                $this->line('');
            }
        }

        // Also check directly from barang_keluar table
        $this->line('');
        $this->info('🔍 Checking barang_keluar table directly...');
        
        $barangKeluarLazada = DB::table('barang_keluar')
            ->join('order_items', 'barang_keluar.order_item_id', '=', 'order_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.platform_id', $lazadaPlatform->id)
            ->select('barang_keluar.*', 'orders.order_number', 'orders.id as order_id')
            ->get();

        $this->info("   Found {$barangKeluarLazada->count()} barang_keluar records linked to Lazada orders");
        
        if ($barangKeluarLazada->isNotEmpty()) {
            $this->line('   Sample records:');
            foreach ($barangKeluarLazada->take(5) as $bk) {
                $this->line("      - {$bk->kode_barang_keluar} (Order: {$bk->order_number}, Order ID: {$bk->order_id})");
            }
            if ($barangKeluarLazada->count() > 5) {
                $this->line("      ... and " . ($barangKeluarLazada->count() - 5) . " more");
            }
        }

        return Command::SUCCESS;
    }
}


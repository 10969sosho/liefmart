<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\MappingBarang;
use App\Models\OrderItem;
use App\Models\PlatformProduct;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixShopeeGivHijabSaffronMapping extends Command
{
    protected $signature = 'fix:shopee-giv-hijab-saffron {--dry-run : Tampilkan perubahan tanpa menulis ke database}';
    protected $description = 'Perbaiki mapping dan barang keluar untuk GIV Bodywash varian Hijab Saffron (Shopee Lamourad)';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $platformProductId = 661;
        $correctMasterProductId = 354;

        $platformProduct = PlatformProduct::with(['platform', 'mappingBarang' => fn ($q) => $q->where('is_active', true)->with('product')])
            ->find($platformProductId);

        if (! $platformProduct) {
            $this->error("PlatformProduct {$platformProductId} tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->line("Platform: " . ($platformProduct->platform?->name ?? '-') . " | PlatformProduct ID: {$platformProduct->id}");
        $this->line("Produk: {$platformProduct->platform_product_name} | Variant: {$platformProduct->variant}");

        $activeMappings = MappingBarang::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->with('product')
            ->get();

        $this->line("Mapping aktif saat ini: " . ($activeMappings->isEmpty()
            ? '-'
            : $activeMappings->map(fn ($m) => "{$m->product_id} ({$m->product?->name}) x{$m->quantity} [v{$m->version}]")->implode(' | ')));

        $orderItemToFixBarangKeluarId = 13910;
        $orderItemA = OrderItem::with(['order', 'barangKeluar.warehouseStock'])
            ->find($orderItemToFixBarangKeluarId);

        if (! $orderItemA) {
            $this->error("OrderItem {$orderItemToFixBarangKeluarId} tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->line("Target perbaikan BarangKeluar: Order #{$orderItemA->order->order_number} | OrderItem ID: {$orderItemA->id} | Qty: {$orderItemA->quantity}");

        $orderIdWithZeroQtyItems = 15019;
        $orderItemsToFixQty = OrderItem::with(['order', 'platformProduct', 'barangKeluar.warehouseStock'])
            ->where('order_id', $orderIdWithZeroQtyItems)
            ->whereIn('id', [18586, 18587])
            ->get();

        if ($orderItemsToFixQty->count() !== 2) {
            $this->error("Order {$orderIdWithZeroQtyItems} tidak memiliki 2 OrderItem target (18586, 18587).");
            return Command::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN: tidak ada perubahan yang ditulis ke database.');
            $this->previewFix($platformProductId, $correctMasterProductId, $orderItemA, $orderItemsToFixQty);
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($platformProductId, $correctMasterProductId, $orderItemA, $orderItemsToFixQty) {
            $now = now();

            $latestVersion = (int) (MappingBarang::where('platform_product_id', $platformProductId)->max('version') ?? 1);
            $parentMappingId = MappingBarang::where('platform_product_id', $platformProductId)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->orderByDesc('id')
                ->value('id');

            MappingBarang::where('platform_product_id', $platformProductId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'valid_until' => $now,
                ]);

            MappingBarang::create([
                'platform_product_id' => $platformProductId,
                'product_id' => $correctMasterProductId,
                'quantity' => 1,
                'version' => $latestVersion + 1,
                'is_active' => true,
                'valid_from' => $now,
                'valid_until' => null,
                'parent_mapping_id' => $parentMappingId,
                'change_reason' => 'Fix mapping Hijab Saffron',
            ]);

            $this->fixBarangKeluarForOrderItem($orderItemA, $correctMasterProductId);

            foreach ($orderItemsToFixQty as $item) {
                $bkTotalQty = $item->barangKeluar->sum('qty');
                $mapTotalQty = (float) (MappingBarang::where('platform_product_id', $item->platform_product_id)
                    ->where('is_active', true)
                    ->sum('quantity') ?? 0);

                if ($mapTotalQty <= 0) {
                    throw new \RuntimeException("Mapping aktif tidak ditemukan untuk platform_product_id {$item->platform_product_id}.");
                }

                $newQty = $bkTotalQty / $mapTotalQty;
                if (abs($newQty - round($newQty)) < 0.00001) {
                    $newQty = (float) round($newQty);
                }

                $item->quantity = $newQty;
                $item->save();
            }
        });

        $this->info('Perbaikan selesai.');
        return Command::SUCCESS;
    }

    private function previewFix(int $platformProductId, int $correctMasterProductId, OrderItem $orderItemA, $orderItemsToFixQty): void
    {
        $this->line("Akan membuat mapping aktif baru: platform_product_id={$platformProductId} -> product_id={$correctMasterProductId} (qty 1)");

        $existingBk = BarangKeluar::where('order_item_id', $orderItemA->id)->with('warehouseStock')->get();
        $this->line("Akan menghapus BK untuk OrderItem {$orderItemA->id}: " . $existingBk->pluck('id')->implode(', '));

        $expectedReduce = 1;
        $stocks = WarehouseStock::where('product_id', $correctMasterProductId)
            ->where('qty', '>', 0)
            ->orderBy('created_at')
            ->orderBy('tax_id', 'asc')
            ->get();

        $remaining = $expectedReduce;
        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (float) $stock->qty);
            if ($take < 1) {
                continue;
            }
            $this->line("Akan kurangi stok: warehouse_stock_id={$stock->id} product_id={$stock->product_id} ambil={$take}");
            $remaining -= $take;
        }

        foreach ($orderItemsToFixQty as $item) {
            $bkTotalQty = $item->barangKeluar->sum('qty');
            $mapTotalQty = (float) (MappingBarang::where('platform_product_id', $item->platform_product_id)
                ->where('is_active', true)
                ->sum('quantity') ?? 0);

            $this->line("Akan update OrderItem {$item->id}: qty {$item->quantity} -> " . ($mapTotalQty > 0 ? ($bkTotalQty / $mapTotalQty) : 'N/A'));
        }
    }

    private function fixBarangKeluarForOrderItem(OrderItem $orderItem, int $correctMasterProductId): void
    {
        $orderItem->loadMissing(['order', 'barangKeluar.warehouseStock']);

        $existingBks = $orderItem->barangKeluar;
        foreach ($existingBks as $bk) {
            $stock = $bk->warehouseStock;
            if ($stock) {
                $stock->qty = $stock->qty + $bk->qty;
                $stock->save();
            }
            $bk->delete();
        }

        $requiredQty = (float) $orderItem->quantity;
        $remainingQty = $requiredQty;

        $stocks = WarehouseStock::where('product_id', $correctMasterProductId)
            ->where('qty', '>', 0)
            ->orderBy('created_at')
            ->orderBy('tax_id', 'asc')
            ->get();

        $isFirstStock = true;

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $qtyToTake = min($remainingQty, (float) $stock->qty);
            if ($qtyToTake < 1) {
                continue;
            }

            if ($isFirstStock) {
                $orderItem->warehouse_stock_id = $stock->id;
                $orderItem->save();
                $isFirstStock = false;
            }

            BarangKeluar::create([
                'kode_barang_keluar' => BarangKeluar::generateKode(),
                'order_item_id' => $orderItem->id,
                'warehouse_stock_id' => $stock->id,
                'qty' => $qtyToTake,
                'tanggal_keluar' => $orderItem->order->tanggal,
                'catatan' => "Penjualan online Shopee - Order #{$orderItem->order->order_number}",
            ]);

            $stock->qty = $stock->qty - $qtyToTake;
            $stock->save();

            $remainingQty -= $qtyToTake;
        }

        if ($remainingQty > 0) {
            throw new \RuntimeException("Stok tidak cukup untuk product_id {$correctMasterProductId}. Kurang: {$remainingQty}");
        }
    }
}


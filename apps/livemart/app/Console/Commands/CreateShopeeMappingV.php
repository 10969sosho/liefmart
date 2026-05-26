<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\MappingBarang;
use App\Models\MappingBarangHistory;

class CreateShopeeMappingV extends Command
{
    protected $signature = 'mapping:fix-bioq24 {--dry-run}';
    protected $description = 'Create a new mapping version for Shopee product: [PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian';

    public function handle()
    {
        $dry = $this->option('dry-run');

        $platform = Platform::where('name', 'shopee')->first();
        if (!$platform) {
            $this->error('Platform shopee not found');
            return 1;
        }

        $productName = '[PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian';
        $platformProduct = PlatformProduct::where('platform_id', $platform->id)
            ->where('platform_product_name', $productName)
            ->first();

        if (!$platformProduct) {
            $this->error('Platform product not found: ' . $productName);
            return 1;
        }

        // Desired composition
        $items = [
            ['sku' => 'BB0063', 'qty' => 1], // BLACKCURRANT
            ['sku' => 'BB0064', 'qty' => 1], // BLUEBERRY
            ['sku' => 'BB0066', 'qty' => 2], // CENTELLA
            ['sku' => 'BB0067', 'qty' => 1], // CHAMOMILE
            ['sku' => 'BB0074', 'qty' => 1], // HONEY
            ['sku' => 'BB0076', 'qty' => 2], // LEMON
            ['sku' => 'BB0080', 'qty' => 1], // PEACH
            ['sku' => 'BB0081', 'qty' => 1], // POMEGRANATE
            ['sku' => 'BB0082', 'qty' => 2], // SEA BUCKTHORN
            ['sku' => 'BB0061', 'qty' => 1], // APPLE
            ['sku' => 'BB0065', 'qty' => 1], // CARROT
            ['sku' => 'BB0068', 'qty' => 1], // CHERRY
            ['sku' => 'BB0069', 'qty' => 1], // COCONUT
            ['sku' => 'BB0070', 'qty' => 1], // CUCUMBER
            ['sku' => 'BB0071', 'qty' => 1], // DRAGON FRUIT
            ['sku' => 'BB0072', 'qty' => 1], // GRAPE COPPER
            ['sku' => 'BB0073', 'qty' => 1], // GRAPEFRUIT
            ['sku' => 'BB0077', 'qty' => 1], // MANGOSTEEN
            ['sku' => 'BB0078', 'qty' => 1], // ORANGE
            ['sku' => 'BB0079', 'qty' => 1], // PAPAYA
            ['sku' => 'BB0083', 'qty' => 1], // TOMATO
        ];

        // Resolve product_ids by SKU
        $skuToProduct = Product::whereIn('sku', collect($items)->pluck('sku'))
            ->get(['id', 'sku', 'name'])
            ->keyBy('sku');

        foreach ($items as $i) {
            if (!isset($skuToProduct[$i['sku']])) {
                $this->error('SKU not found: ' . $i['sku']);
                return 1;
            }
        }

        // Compute next version
        $latestVersion = MappingBarang::where('platform_product_id', $platformProduct->id)->max('version') ?? 0;
        $newVersion = $latestVersion + 1;

        $this->info('Creating version v' . $newVersion . ' for platform_product_id=' . $platformProduct->id);

        if ($dry) {
            foreach ($items as $i) {
                $p = $skuToProduct[$i['sku']];
                $this->line("- {$p->name} ({$i['sku']}): qty {$i['qty']}");
            }
            return 0;
        }

        \DB::transaction(function() use ($platformProduct, $items, $skuToProduct, $newVersion) {
            // deactivate current active mappings for this platform product
            MappingBarang::where('platform_product_id', $platformProduct->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'valid_until' => now()]);

            // create new mappings for new version
            foreach ($items as $i) {
                $product = $skuToProduct[$i['sku']];
                $mapping = new MappingBarang([
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $product->id,
                    'quantity' => $i['qty'],
                    'version' => $newVersion,
                    'is_active' => true,
                    'valid_from' => now(),
                    'change_reason' => 'Fix paket 24 pcs sesuai komposisi resmi',
                ]);
                $mapping->save();

                MappingBarangHistory::create([
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $product->id,
                    'quantity' => $i['qty'],
                    'action' => 'version_create',
                    'user_id' => auth()->id() ?? 1,
                    'keterangan' => 'Create v' . $newVersion . ' - paket 24 pcs',
                ]);
            }
        });

        $this->info('Mapping version v' . $newVersion . ' created successfully.');
        return 0;
    }
}



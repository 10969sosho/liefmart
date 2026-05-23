<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\MappingBarang;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;

class ImportMappingBarangSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    /**
     * Store the command from ImportMappingBarang
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $files = [
            'mapping SHOPEE LAMOURAD (2).csv' => 'Shopee Lamourad',
            'mapping SHOPEE LIEFMARKET (2).csv' => 'Shopee Liefmarket',
            'mapping TIKTOK LAMOURAD (2).csv' => 'Tiktok Lamourad',
            'mapping TIKTOK LIEFMARKET (2).csv' => 'Tiktok Liefmarket',
        ];

        foreach ($files as $filename => $platformName) {
            $this->processFile($filename, $platformName);
        }
    }

    private function processFile($filename, $platformName)
    {
        if ($this->command) {
            $this->command->info("Processing {$platformName} from {$filename}...");
        }

        $csvPath = storage_path('app/imports/mapping/' . $filename);
        if (!File::exists($csvPath)) {
            if ($this->command) {
                $this->command->error("File not found: {$csvPath}");
            }
            return;
        }

        // Find Platform
        $platform = Platform::where('name', $platformName)->first();
        if (!$platform) {
            // Create if not exists (safety fallback, though user showed seeder)
            $platform = Platform::create(['name' => $platformName]);
            if ($this->command) {
                $this->command->info("Created Platform: {$platformName}");
            }
        }

        // Read CSV
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $count = 0;
        $success = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($records as $record) {
                $count++;
                
                // Extract Data
                $platformProductName = trim($record['NAMA BARANG PLATFORM'] ?? '');
                $platformVariantName = trim($record['VARIAN PLATFORM'] ?? '');
                $internalProductName = trim($record['NAMA BARANG'] ?? '');
                $qty = trim($record['QTY'] ?? '1');

                if (empty($platformProductName)) {
                    continue;
                }

                // Find Internal Product
                $product = Product::where('name', $internalProductName)->first();
                if (!$product) {
                    if ($this->command) {
                        // $this->command->warn("Internal Product not found: '{$internalProductName}'. Skipping row {$count}.");
                    }
                    $skipped++;
                    continue;
                }

                // Find or Create Platform Product
                // Note: Platform Product is unique by Platform + Name + Variant
                $platformProduct = PlatformProduct::firstOrCreate(
                    [
                        'platform_id' => $platform->id,
                        'platform_product_name' => $platformProductName,
                        'variant' => $platformVariantName,
                    ]
                );

                // Create Mapping
                // Mapping links PlatformProduct to Product with Quantity
                // Check if mapping already exists to avoid duplicates or update it
                $mapping = MappingBarang::updateOrCreate(
                    [
                        'platform_product_id' => $platformProduct->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => (float) $qty,
                    ]
                );

                $success++;
            }
            
            DB::commit();
            
            if ($this->command) {
                $this->command->info("Completed {$platformName}: {$success} imported, {$skipped} skipped.");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->command) {
                $this->command->error("Error processing {$filename}: " . $e->getMessage());
            }
        }
    }
}

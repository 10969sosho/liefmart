<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\MainCategory;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;

class ImportMasterBarangInternalSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    /**
     * Store the command from ImportMasterBarangInternal
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
        if ($this->command) {
            $this->command->info('Importing Master Barang Internal...');
        }
        
        // Check if the CSV file exists
        $csvPath = storage_path('app/imports/MASTER - BARANG INTERNAL (3).csv');
        if (!File::exists($csvPath)) {
            if ($this->command) {
                $this->command->error('CSV file not found at: ' . $csvPath);
            }
            return;
        }

        // Read the CSV file
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);

        // Trim headers to remove any leading/trailing spaces
        // But headers might be different from previous files.
        // Let's use getRecords() which returns associative array based on header row.
        
        $records = $csv->getRecords();
        
        // Ensure Main Category 'SKINCARE' exists
        $mainCategory = MainCategory::firstOrCreate(
            ['name' => 'SKINCARE'],
            ['description' => 'Kategori Utama SKINCARE', 'is_active' => true]
        );

        $skuCounter = 1;

        foreach ($records as $record) {
            try {
                // Clean keys just in case
                $cleanRecord = [];
                foreach ($record as $k => $v) {
                    $cleanRecord[trim($k)] = trim($v);
                }
                $record = $cleanRecord;

                // Extract fields from record
                $productName = $record['NAMA BARANG'] ?? '';


                // Clean product name: remove leading "02 - " or similar if present?
                // Example: "02 - FRES & NATURAL COLOGNE VANILLA"
                // User input says "input baru", implies clean slate.
                // Let's use the name as provided.
                $productName = trim($productName);

                // Skip if product name is empty or is header
                if (empty($productName) || $productName === 'NAMA BARANG') {
                    continue;
                }

                $brandName = trim($record['BRAND'] ?? 'KOSONG');
                if (empty($brandName)) $brandName = 'KOSONG';

                $subBrandName = trim($record['SUB BRAND'] ?? 'KOSONG');
                if (empty($subBrandName)) $subBrandName = 'KOSONG';

                $categoryName = trim($record['KATEGORI'] ?? 'KOSONG'); // Product Category
                if (empty($categoryName)) $categoryName = 'KOSONG';

                $typeName = trim($record['PRODUK'] ?? 'KOSONG'); // Product Type
                if (empty($typeName)) $typeName = 'KOSONG';

                $sizeName = trim($record['SATUAN GRAMASI/ML'] ?? 'KOSONG');
                if (empty($sizeName)) $sizeName = 'KOSONG';

                $variantName = trim($record['VARIAN'] ?? 'KOSONG');
                if (empty($variantName)) $variantName = 'KOSONG';
                
                $initialPrice = $record['HARGA AWAL'] ?? '0';
                $discountPercentage = $record['PERSENTASE DISKON'] ?? '0';
                $sku = trim($record['SKU'] ?? '-');

                // Clean up Price
                // "15000" -> 15000. No commas in sample.
                $initialPrice = preg_replace('/[^\d]/', '', $initialPrice);
                if (empty($initialPrice)) $initialPrice = 0;

                // Clean up Discount
                if (empty($discountPercentage)) $discountPercentage = 0;

                // Check for existing product with same SKU
                // Duplicate SKU handling:
                // If the CSV has duplicate SKUs for different products (e.g. BRASOV Eyelash variants sharing same barcode/SKU),
                // we must differentiate them because `products.sku` is UNIQUE.
                // Strategy: Append suffix '-1', '-2' etc if SKU already taken by DIFFERENT product name.
                
                if ($sku !== '-' && !empty($sku)) {
                    $originalSku = $sku;
                    $counter = 1;
                    // Check if SKU exists AND belongs to a DIFFERENT product
                    // If belongs to SAME product (update scenario), it's fine.
                    while (Product::where('sku', $sku)->where('name', '!=', $productName)->exists()) {
                         $sku = $originalSku . '-' . $counter;
                         $counter++;
                    }
                } else {
                    // Generate SKU logic if missing
                    $prefix = strtoupper(substr($brandName, 0, 1) . substr($productName, 0, 1));
                    $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
                    if (strlen($prefix) < 2) $prefix = 'XX';
                    
                    $sku = $prefix . sprintf('%04d', $skuCounter);
                    // Ensure generated SKU is unique globally
                    while (Product::where('sku', $sku)->exists()) {
                         $skuCounter++;
                         $sku = $prefix . sprintf('%04d', $skuCounter);
                    }
                    $skuCounter++;
                }

                // Create Hierarchy
                
                // Brand
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName, 'main_category_id' => $mainCategory->id],
                    ['description' => "Brand {$brandName}", 'is_active' => true]
                );

                // Sub Brand
                $subBrand = SubBrand::firstOrCreate(
                    ['name' => $subBrandName, 'brand_id' => $brand->id],
                    ['description' => "Sub brand {$subBrandName}", 'is_active' => true]
                );

                // Product Category
                $productCategory = ProductCategory::firstOrCreate(
                    ['name' => $categoryName, 'sub_brand_id' => $subBrand->id],
                    ['description' => "Kategori {$categoryName}", 'is_active' => true]
                );

                // Product Type
                $productType = ProductType::firstOrCreate(
                    ['name' => $typeName, 'product_category_id' => $productCategory->id],
                    ['description' => "Tipe {$typeName}", 'is_active' => true]
                );

                // Product Size
                $productSize = ProductSize::firstOrCreate(
                    ['name' => $sizeName, 'product_type_id' => $productType->id],
                    ['description' => "Ukuran {$sizeName}", 'is_active' => true]
                );

                // Product Variant
                $productVariant = ProductVariant::firstOrCreate(
                    ['name' => $variantName, 'product_size_id' => $productSize->id],
                    ['description' => "Varian {$variantName}", 'is_active' => true]
                );

                // Create Product
                $product = Product::updateOrCreate(
                    ['name' => $productName], // Match by Name to avoid duplicates if re-run
                    [
                        'sku' => $sku,
                        'main_category_id' => $mainCategory->id,
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'product_category_id' => $productCategory->id,
                        'product_type_id' => $productType->id,
                        'product_size_id' => $productSize->id,
                        'product_variant_id' => $productVariant->id,
                        'initial_price' => $initialPrice,
                        'discount_percentage' => $discountPercentage,
                        'description' => "Imported from MASTER - BARANG INTERNAL (3).csv",
                        'is_active' => true
                    ]
                );

                if ($this->command) {
                    // $this->command->info("Processed: {$productName} ({$sku})");
                }

            } catch (\Exception $e) {
                if ($this->command) {
                    $this->command->error("Error processing record: " . json_encode($record));
                    $this->command->error($e->getMessage());
                }
            }
        }
    }
}

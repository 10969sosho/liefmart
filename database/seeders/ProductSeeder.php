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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    /**
     * Store the command from ImportMasterProducts
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
        $this->command->info('Importing master products...');
        
        // Check if the CSV file exists
        $csvPath = storage_path('app/imports/MASTER - BARANG INTERNAL.csv');
        if (!File::exists($csvPath)) {
            $this->command->error('CSV file not found at: ' . $csvPath);
            $this->command->info('Please place your CSV file at the storage/app/imports directory');
            return;
        }

        // Read the CSV file
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);

        // Trim headers to remove any leading/trailing spaces
        $headers = array_map('trim', $csv->getHeader());
        $csv->setHeaderOffset(null); // Remove header offset to manually handle headers
        $records = [];
        foreach ($csv as $row) {
            $record = [];
            foreach ($headers as $i => $header) {
                $record[$header] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $records[] = $record;
        }
        $count = 0;
        $skuCounter = 1; // Counter for auto-generated SKUs

        // Get Kosmetik category ID
        $kosmetikCategory = MainCategory::where('name', 'Kosmetik')->first();
        if (!$kosmetikCategory) {
            $this->command->error('Main Category "Kosmetik" not found. Using default ID 2.');
            $kosmetikCategoryId = 2;
        } else {
            $kosmetikCategoryId = $kosmetikCategory->id;
        }

        foreach ($records as $record) {
            try {
                // Extract fields from record
                $productName = trim($record['NAMA BARANG'] ?? '');
                $brandName = trim($record['BRAND'] ?? '');
                $subBrandName = trim($record['SUB BRAND'] ?? '');
                $categoryName = trim($record['KATEGORI'] ?? '');
                $typeName = trim($record['PRODUK'] ?? '');
                $sizeName = trim($record['SATUAN GRAMASI/ML'] ?? '');
                $variantName = trim($record['VARIAN'] ?? '');
                $initialPrice = trim($record['HARGA AWAL'] ?? '0');
                $discountPercentage = trim($record['PERSENTASE DISKON'] ?? '0');
                $csvBarcode = trim($record['SKU'] ?? ''); // Read SKU from CSV and use as barcode
                $description = "Product imported from CSV";
                
                // Auto-fill empty fields with "KOSONG" to prevent skipping
                if (empty($productName)) $productName = 'KOSONG';
                if (empty($brandName)) $brandName = 'KOSONG';
                if (empty($subBrandName)) $subBrandName = 'KOSONG';
                if (empty($categoryName)) $categoryName = 'KOSONG';
                if (empty($typeName)) $typeName = 'KOSONG';
                if (empty($sizeName)) $sizeName = 'KOSONG';
                if (empty($variantName)) $variantName = 'KOSONG';
                
                // Skip if product name is still empty (should not happen now)
                if (empty($productName)) {
                    $this->command->warn('Skipping record due to missing product name: ' . json_encode($record));
                    continue;
                }

                // Determine Barcode: use CSV SKU as barcode if available
                $barcode = null;
                if (!empty($csvBarcode)) {
                    $barcode = $csvBarcode; // Use SKU from CSV as barcode
                }
                
                // Always generate SKU automatically: First letter of brand + first letter of product + incremental number
                $prefix = strtoupper(substr($brandName, 0, 1) . substr($productName, 0, 1));
                $sku = $prefix . sprintf('%04d', $skuCounter);
                $skuCounter++;
                $skuSource = "auto-generated";

                // Convert price and discount to proper numeric values
                $initialPrice = $initialPrice === '#N/A' ? 0 : (float)str_replace(',', '', $initialPrice);
                $discountPercentage = $discountPercentage === '#N/A' ? 0 : (float)str_replace(',', '', $discountPercentage);

                // Find or create Brand with Kosmetik main category
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName, 'main_category_id' => $kosmetikCategoryId],
                    [
                        'description' => "Brand {$brandName} untuk kategori Kosmetik",
                        'is_active' => true,
                        'main_category_id' => $kosmetikCategoryId
                    ]
                );

                // Find or create SubBrand linked to the Brand
                $subBrand = SubBrand::firstOrCreate(
                    ['name' => $subBrandName, 'brand_id' => $brand->id],
                    [
                        'description' => "Sub brand {$subBrandName} dari {$brandName}",
                        'is_active' => true
                    ]
                );

                // Find or create Product Category linked to the SubBrand
                $productCategory = ProductCategory::firstOrCreate(
                    ['name' => $categoryName, 'sub_brand_id' => $subBrand->id],
                    [
                        'description' => "Kategori {$categoryName} untuk {$subBrandName}",
                        'is_active' => true
                    ]
                );

                // Find or create Product Type linked to the Product Category
                $productType = ProductType::firstOrCreate(
                    ['name' => $typeName, 'product_category_id' => $productCategory->id],
                    [
                        'description' => "Tipe {$typeName} untuk kategori {$categoryName}",
                        'is_active' => true
                    ]
                );

                // Find or create Product Size linked to the Product Type
                $productSize = ProductSize::firstOrCreate(
                    ['name' => $sizeName, 'product_type_id' => $productType->id],
                    [
                        'description' => "Ukuran {$sizeName} untuk tipe {$typeName}",
                        'is_active' => true
                    ]
                );

                // Find or create Product Variant linked to the Product Size
                $productVariant = ProductVariant::firstOrCreate(
                    ['name' => $variantName, 'product_size_id' => $productSize->id],
                    [
                        'description' => "Varian {$variantName} untuk {$sizeName}",
                        'is_active' => true
                    ]
                );

                // Find or create Product with all the created relationships
                $product = Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'product_category_id' => $productCategory->id,
                        'product_type_id' => $productType->id,
                        'product_size_id' => $productSize->id,
                        'product_variant_id' => $productVariant->id,
                    ],
                    [
                        'main_category_id' => $kosmetikCategoryId,
                        'description' => $description,
                        'sku' => $sku, // Always auto-generated SKU
                        'barcode' => $barcode, // Use CSV SKU as barcode if available
                        'initial_price' => $initialPrice,
                        'discount_percentage' => $discountPercentage,
                        'is_active' => true
                    ]
                );

                // Update prices, SKU, and barcode for existing products
                if (!$product->wasRecentlyCreated) {
                    $product->update([
                        'initial_price' => $initialPrice,
                        'discount_percentage' => $discountPercentage,
                        'sku' => $sku, // Update SKU
                        'barcode' => $barcode // Update barcode
                    ]);
                }

                $count++;
                $barcodeInfo = $barcode ? "Barcode: {$barcode}" : "No barcode";
                $this->command->info("Created/updated product: {$productName} with SKU: {$sku} ({$skuSource}), {$barcodeInfo}");
            } catch (\Exception $e) {
                Log::error('Error importing product: ' . $e->getMessage());
                $this->command->error('Error importing row: ' . json_encode($record) . ' - ' . $e->getMessage());
                
                // Stop execution on error instead of continuing
                $this->command->error('Stopping import due to error. Please fix the issue and run again.');
                throw $e; // This will stop the entire seeder
            }
        }

        $this->command->info("Successfully imported {$count} products");
    }
} 
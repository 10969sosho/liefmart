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

class ImportDataMasterBarangSeeder extends Seeder
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
        if ($this->command) {
            $this->command->info('Importing Data Master Barang...');
        }
        
        // Check if the CSV file exists
        $csvPath = storage_path('app/imports/DATA MASTER BARANG.csv');
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
        $headers = array_map('trim', $csv->getHeader());
        
        $records = [];
        foreach ($csv as $row) {
            $record = [];
            foreach ($headers as $i => $header) {
                // Use header name as key, handle potential index mismatch if row has more/less columns
                // But since we use $row as assoc array from Reader with setHeaderOffset, 
                // we should iterate over $row directly or just use it.
                // Wait, league/csv with setHeaderOffset returns associative array.
                // The $headers array is just for reference or cleaning keys.
                // Let's iterate the row which is already associative (but keys might have whitespace if not cleaned).
                // Actually, standard iteration returns assoc array with original headers.
                // We need to map trimmed headers to values.
            }
            // Simpler way:
            $cleanRecord = [];
            foreach ($row as $key => $value) {
                $cleanKey = trim($key);
                $cleanRecord[$cleanKey] = trim($value);
            }
            $records[] = $cleanRecord;
        }

        $skuCounter = 1; // Counter for auto-generated SKUs

        foreach ($records as $record) {
            try {
                // Extract fields from record
                $productName = $record['NAMA BARANG'] ?? '';
                $sku = $record['SKU'] ?? '';
                $barcode = $record['Barcode'] ?? '';
                $mainCategoryName = $record['Kategori Utama'] ?? '';
                $brandName = $record['Brand'] ?? '';
                $subBrandName = $record['Sub Brand'] ?? '';
                $categoryName = $record['Kategori Produk'] ?? '';
                $typeName = $record['Tipe Produk'] ?? '';
                $sizeName = $record['Ukuran'] ?? '';
                $variantName = $record['Varian'] ?? '';
                $initialPrice = $record['Harga Awal'] ?? '0';
                $discountPercentage = $record['Presentase Diskon'] ?? '0';

                // Skip if product name is empty or is header
                if (empty($productName) || $productName === 'NAMA BARANG') {
                    continue;
                }

                // Handle Empty / KOSONG
                if (empty($mainCategoryName)) $mainCategoryName = 'LAINNYA';
                if (empty($brandName)) $brandName = 'KOSONG';
                if (empty($subBrandName)) $subBrandName = 'KOSONG';
                if (empty($categoryName)) $categoryName = 'KOSONG';
                if (empty($typeName)) $typeName = 'KOSONG';
                if (empty($sizeName)) $sizeName = 'KOSONG';
                if (empty($variantName)) $variantName = 'KOSONG';

                // Clean up Price
                $initialPrice = str_replace([',', '.'], '', $initialPrice); // Remove commas and dots (assuming thousands separator)
                // Wait, user data: "15,000". This is likely comma as thousand separator.
                // Or comma as decimal? In ID, dot is thousand, comma is decimal.
                // But example "15,000" for cologne looks like 15000.
                // If I strip both, "15,000" -> "15000".
                // If it was "15.000,00", stripping both -> "1500000".
                // Let's assume standard format from previous seeder: `(float)str_replace(',', '', $initialPrice)`.
                // If the CSV has "15,000", removing comma makes it 15000.
                // If it has "15.000", removing dot makes it 15000.
                // Let's remove ',' and '.' if they are used as separators.
                // Safe bet for "15,000" is removing comma.
                $initialPrice = preg_replace('/[^\d]/', '', $initialPrice); // Keep only digits
                if (empty($initialPrice)) $initialPrice = 0;

                $discountPercentage = str_replace(',', '.', $discountPercentage); // Ensure decimal point is dot for float
                if (empty($discountPercentage)) $discountPercentage = 0;

                // Handle Barcode
                if ($barcode === '-' || empty($barcode)) {
                    $barcode = null;
                }

                // Find or Create Hierarchy
                
                // 1. Main Category
                $mainCategory = MainCategory::firstOrCreate(
                    ['name' => $mainCategoryName],
                    ['description' => "Kategori Utama {$mainCategoryName}", 'is_active' => true]
                );

                // 2. Brand
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName, 'main_category_id' => $mainCategory->id],
                    [
                        'description' => "Brand {$brandName}",
                        'is_active' => true
                    ]
                );

                // 3. Sub Brand
                $subBrand = SubBrand::firstOrCreate(
                    ['name' => $subBrandName, 'brand_id' => $brand->id],
                    [
                        'description' => "Sub brand {$subBrandName}",
                        'is_active' => true
                    ]
                );

                // 4. Product Category
                $productCategory = ProductCategory::firstOrCreate(
                    ['name' => $categoryName, 'sub_brand_id' => $subBrand->id],
                    [
                        'description' => "Kategori {$categoryName}",
                        'is_active' => true
                    ]
                );

                // 5. Product Type
                $productType = ProductType::firstOrCreate(
                    ['name' => $typeName, 'product_category_id' => $productCategory->id],
                    [
                        'description' => "Tipe {$typeName}",
                        'is_active' => true
                    ]
                );

                // 6. Product Size
                $productSize = ProductSize::firstOrCreate(
                    ['name' => $sizeName, 'product_type_id' => $productType->id],
                    [
                        'description' => "Ukuran {$sizeName}",
                        'is_active' => true
                    ]
                );

                // 7. Product Variant
                $productVariant = ProductVariant::firstOrCreate(
                    ['name' => $variantName, 'product_size_id' => $productSize->id],
                    [
                        'description' => "Varian {$variantName}",
                        'is_active' => true
                    ]
                );

                // Generate SKU if missing
                if (empty($sku)) {
                    $prefix = strtoupper(substr($brandName, 0, 1) . substr($productName, 0, 1));
                    $sku = $prefix . sprintf('%04d', $skuCounter);
                    $skuCounter++;
                }

                // Update or Create Product
                $product = Product::updateOrCreate(
                    ['sku' => $sku], // Match by SKU
                    [
                        'name' => $productName,
                        'barcode' => $barcode,
                        'main_category_id' => $mainCategory->id,
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'product_category_id' => $productCategory->id,
                        'product_type_id' => $productType->id,
                        'product_size_id' => $productSize->id,
                        'product_variant_id' => $productVariant->id, // Storing ID
                        'initial_price' => $initialPrice,
                        'discount_percentage' => $discountPercentage,
                        'description' => "Imported from DATA MASTER BARANG.csv",
                        'is_active' => true
                    ]
                );

                if ($this->command) {
                    $this->command->info("Processed: {$productName} ({$sku})");
                }

            } catch (\Exception $e) {
                if ($this->command) {
                    $this->command->error("Error processing record: " . json_encode($record));
                    $this->command->error($e->getMessage());
                }
                Log::error("Import Error: " . $e->getMessage());
            }
        }
    }
}

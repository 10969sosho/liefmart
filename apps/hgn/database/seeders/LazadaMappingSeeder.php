<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\MainCategory;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\MappingBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LazadaMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Lazada Mapping Import...');
        
        // Pastikan platform Lazada ada
        $platform = Platform::firstOrCreate(
            ['name' => 'lazada'],
            ['name' => 'lazada', 'is_active' => true]
        );
        
        $this->command->info("Using platform: {$platform->name} (ID: {$platform->id})");
        
        // Path ke file CSV
        $csvPath = base_path('LAZADA.xlsx - mapping LAZADA.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }
        
        // Baca file CSV
        $csvData = $this->readCsvFile($csvPath);
        
        if (empty($csvData)) {
            $this->command->error('No data found in CSV file');
            return;
        }
        
        $this->command->info("Found " . count($csvData) . " rows in CSV");
        
        // Proses data
        $this->processMappingData($csvData, $platform);
        
        $this->command->info('Lazada Mapping Import completed!');
    }
    
    /**
     * Baca file CSV dan return array data
     */
    private function readCsvFile($filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return $data;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            $data[] = [
                'nama_barang_platform' => $row[0] ?? '',
                'varian_platform' => $row[1] ?? '',
                'nama_barang' => $row[2] ?? '',
                'brand' => $row[3] ?? '',
                'sub_brand' => $row[4] ?? '',
                'kategori' => $row[5] ?? '',
                'produk' => $row[6] ?? '',
                'satuan_gramasi' => $row[7] ?? '',
                'varian' => $row[8] ?? '',
                'qty' => $row[9] ?? 1,
            ];
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Proses data mapping
     */
    private function processMappingData(array $csvData, Platform $platform): void
    {
        $processedPlatformProducts = [];
        $stats = [
            'platform_products_created' => 0,
            'products_created' => 0,
            'mappings_created' => 0,
            'errors' => 0,
            'skipped' => 0
        ];
        
        DB::beginTransaction();
        
        try {
            foreach ($csvData as $index => $row) {
                $this->command->info("Processing row " . ($index + 1) . ": {$row['nama_barang_platform']}");
                
                // Skip jika data tidak lengkap
                if (empty($row['nama_barang_platform']) || empty($row['nama_barang'])) {
                    $this->command->warn("Skipping row " . ($index + 1) . " - incomplete data");
                    $stats['skipped']++;
                    continue;
                }
                
                // Buat atau ambil PlatformProduct
                $platformProductKey = $row['nama_barang_platform'] . '|' . $row['varian_platform'];
                
                if (!isset($processedPlatformProducts[$platformProductKey])) {
                    $platformProduct = $this->createOrGetPlatformProduct($platform, $row);
                    $processedPlatformProducts[$platformProductKey] = $platformProduct;
                    $stats['platform_products_created']++;
                } else {
                    $platformProduct = $processedPlatformProducts[$platformProductKey];
                }
                
                // Buat atau ambil Product internal
                $product = $this->createOrGetProduct($row);
                if ($product) {
                    $stats['products_created']++;
                    
                    // Buat mapping
                    $mapping = $this->createMapping($platformProduct, $product, $row['qty']);
                    if ($mapping) {
                        $stats['mappings_created']++;
                    }
                } else {
                    $stats['errors']++;
                }
            }
            
            DB::commit();
            
            // Tampilkan statistik
            $this->command->info('Import Statistics:');
            $this->command->info("- Platform Products Created: {$stats['platform_products_created']}");
            $this->command->info("- Products Created: {$stats['products_created']}");
            $this->command->info("- Mappings Created: {$stats['mappings_created']}");
            $this->command->info("- Errors: {$stats['errors']}");
            $this->command->info("- Skipped: {$stats['skipped']}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error processing data: " . $e->getMessage());
            Log::error("LazadaMappingSeeder Error: " . $e->getMessage());
        }
    }
    
    /**
     * Buat atau ambil PlatformProduct
     */
    private function createOrGetPlatformProduct(Platform $platform, array $row): PlatformProduct
    {
        return PlatformProduct::firstOrCreate([
            'platform_id' => $platform->id,
            'platform_product_name' => $row['nama_barang_platform'],
            'variant' => $row['varian_platform'] ?: null,
        ]);
    }
    
    /**
     * Buat atau ambil Product internal
     */
    private function createOrGetProduct(array $row): ?Product
    {
        try {
            // Cari atau buat MainCategory (default ke 1 jika tidak ada)
            $mainCategory = MainCategory::first() ?? MainCategory::create(['name' => 'Default']);
            
            // Cari atau buat Brand
            $brand = null;
            if (!empty($row['brand'])) {
                $brand = Brand::firstOrCreate(['name' => $row['brand']]);
            }
            
            // Cari atau buat SubBrand
            $subBrand = null;
            if (!empty($row['sub_brand'])) {
                $subBrand = SubBrand::firstOrCreate(['name' => $row['sub_brand']]);
            }
            
            // Cari atau buat ProductCategory (berdasarkan sub_brand_id)
            $productCategory = null;
            if (!empty($row['kategori'])) {
                $productCategory = ProductCategory::firstOrCreate([
                    'name' => $row['kategori'],
                    'sub_brand_id' => $subBrand?->id ?? 1
                ]);
            }
            
            // Cari atau buat ProductType (berdasarkan product_category_id)
            $productType = null;
            if (!empty($row['produk'])) {
                $productType = ProductType::firstOrCreate([
                    'name' => $row['produk'],
                    'product_category_id' => $productCategory?->id ?? 1
                ]);
            }
            
            // Cari atau buat ProductSize (berdasarkan product_type_id)
            $productSize = null;
            if (!empty($row['satuan_gramasi'])) {
                $productSize = ProductSize::firstOrCreate([
                    'name' => $row['satuan_gramasi'],
                    'product_type_id' => $productType?->id ?? 1
                ]);
            }
            
            // Cari atau buat ProductVariant (berdasarkan product_size_id)
            $productVariant = null;
            if (!empty($row['varian'])) {
                $productVariant = ProductVariant::firstOrCreate([
                    'name' => $row['varian'],
                    'product_size_id' => $productSize?->id ?? 1
                ]);
            }
            
            // Buat Product
            $product = Product::firstOrCreate([
                'name' => $row['nama_barang'],
                'main_category_id' => $mainCategory->id,
                'brand_id' => $brand?->id ?? 1,
                'sub_brand_id' => $subBrand?->id ?? 1,
                'product_category_id' => $productCategory?->id ?? 1,
                'product_type_id' => $productType?->id ?? 1,
                'product_size_id' => $productSize?->id ?? 1,
                'product_variant_id' => $productVariant?->id,
            ], [
                'description' => $row['nama_barang'],
                'sku' => $this->generateSku($row),
                'is_active' => true,
                'initial_price' => 0,
                'discount_percentage' => 0,
            ]);
            
            return $product;
            
        } catch (\Exception $e) {
            $this->command->error("Error creating product '{$row['nama_barang']}': " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buat mapping antara PlatformProduct dan Product
     */
    private function createMapping(PlatformProduct $platformProduct, Product $product, $quantity): ?MappingBarang
    {
        try {
            // Cek apakah mapping sudah ada
            $existingMapping = MappingBarang::where('platform_product_id', $platformProduct->id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();
            
            if ($existingMapping) {
                $this->command->warn("Mapping already exists for PlatformProduct ID {$platformProduct->id} and Product ID {$product->id}");
                return $existingMapping;
            }
            
            // Buat mapping baru
            $mapping = MappingBarang::create([
                'platform_product_id' => $platformProduct->id,
                'product_id' => $product->id,
                'quantity' => (int) $quantity,
                'version' => 1,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => null,
                'change_reason' => 'Initial import from CSV',
            ]);
            
            return $mapping;
            
        } catch (\Exception $e) {
            $this->command->error("Error creating mapping: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate SKU untuk product
     */
    private function generateSku(array $row): string
    {
        $sku = '';
        
        // Ambil bagian dari brand
        if (!empty($row['brand'])) {
            $sku .= strtoupper(substr($row['brand'], 0, 3));
        }
        
        // Ambil bagian dari nama produk
        if (!empty($row['nama_barang'])) {
            $words = explode(' ', $row['nama_barang']);
            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    $sku .= strtoupper(substr($word, 0, 2));
                }
            }
        }
        
        // Ambil bagian dari varian
        if (!empty($row['varian'])) {
            $sku .= strtoupper(substr($row['varian'], 0, 2));
        }
        
        // Ambil bagian dari ukuran
        if (!empty($row['satuan_gramasi'])) {
            $sku .= strtoupper(substr($row['satuan_gramasi'], 0, 3));
        }
        
        // Tambahkan timestamp dengan detik untuk uniqueness
        $sku .= date('mdHis'); // tanggal, bulan, jam, menit, detik
        
        // Tambahkan angka random 3 digit di akhir untuk memastikan uniqueness
        $sku .= sprintf('%03d', rand(100, 999));
        
        return substr($sku, 0, 23); // Maksimal 23 karakter
    }
}

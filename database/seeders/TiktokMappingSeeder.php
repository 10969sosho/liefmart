<?php

namespace Database\Seeders;

use App\Models\MappingBarang;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TikTokMappingSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Starting TikTokMappingSeeder...');

        // Path to the mapping file
        $filePath = storage_path('app/imports/mapping/TIKTOK.xlsx - mapping TIKTOK.csv');

        if (!file_exists($filePath)) {
            $this->command->error('Mapping file not found: ' . $filePath);
            return;
        }

        // Read the CSV file
        $csv = array_map('str_getcsv', file($filePath));
        
        // Remove header row
        array_shift($csv);

        $total = count($csv);
        $success = 0;
        $failed = 0;
        $skipped = 0;
        $failedProducts = [];
        $processedMappings = [];
        $validPlatformProducts = []; // Track platform products that have valid mappings

        // Get the TikTok platform ID
        $tiktokPlatformId = Platform::where('name', 'TIKTOK')->value('id');
        
        if (!$tiktokPlatformId) {
            $this->command->error('TIKTOK platform not found in the database.');
            return;
        }
        
        // Clear existing mappings first
        $platformProductIds = PlatformProduct::where('platform_id', $tiktokPlatformId)->pluck('id')->toArray();
        
        if (!empty($platformProductIds)) {
            $deletedCount = DB::table('mapping_barangs')
                ->whereIn('platform_product_id', $platformProductIds)
                ->delete();
            
            $this->command->info("Deleted {$deletedCount} existing TIKTOK mappings");
        } else {
            $this->command->info("No existing TIKTOK platform products found");
        }

        // Process each row
        $this->command->info('Processing mappings...');
        
        // Variables to track the current platform product and its details
        $currentPlatformName = null;
        $currentVariant = null;
        $platformProductCache = [];

        foreach ($csv as $index => $row) {
            try {
                // Log the row data for debugging
                $this->command->info("Processing row " . ($index + 2) . ": " . implode(', ', $row));

                // Extract data from the CSV columns
                $namaBarangPlatform = trim($row[0] ?? '');
                $varianPlatform = trim($row[1] ?? '');
                $namaBarang = trim($row[2] ?? '');
                
                // Get quantity from column 9 (QTY column)
                $quantity = isset($row[9]) ? intval($row[9]) : 1; // Default to 1 if no valid quantity found
                
                // Skip empty rows
                if (empty($namaBarang)) {
                    $this->command->info("Skipping empty product row " . ($index + 2));
                    continue;
                }

                // Determine platform product name and variant
                if (!empty($namaBarangPlatform)) {
                    // We have a new platform product
                    $currentPlatformName = $namaBarangPlatform;
                    $currentVariant = $varianPlatform;
                }
                
                // Use current platform product if this row doesn't specify one
                if (empty($namaBarangPlatform) && $currentPlatformName) {
                    $namaBarangPlatform = $currentPlatformName;
                    $varianPlatform = $currentVariant;
                }

                // Skip if we still don't have a platform name
                if (empty($namaBarangPlatform)) {
                    $this->command->error("Error: No platform name found in row " . ($index + 2));
                    $failed++;
                    continue;
                }

                // Get or find the platform product
                $cacheKey = $namaBarangPlatform . '|' . $varianPlatform;
                if (!isset($platformProductCache[$cacheKey])) {
                    $platformProduct = PlatformProduct::where('platform_product_name', $namaBarangPlatform)
                        ->where('variant', $varianPlatform)
                        ->where('platform_id', $tiktokPlatformId)
                        ->first();

                    if (!$platformProduct) {
                        $this->command->error("Error: PlatformProduct not found");
                        $this->command->error("Platform Name: $namaBarangPlatform");
                        $this->command->error("Variant: $varianPlatform");
                        $this->command->error("Row: " . ($index + 2));
                        $failed++;
                        continue;
                    }
                    
                    $platformProductCache[$cacheKey] = $platformProduct;
                }
                
                $platformProduct = $platformProductCache[$cacheKey];

                // Parse product details - use direct name match
                $product = Product::where('name', $namaBarang)->first();

                if (!$product) {
                    $this->command->info("Skipping mapping - Product not found: $namaBarang");
                    $skipped++;
                    continue;
                }

                // Create a unique key for this mapping to avoid duplicates
                $mappingKey = $platformProduct->id . '_' . $product->id;
                
                // Skip if we've already processed this mapping
                if (in_array($mappingKey, $processedMappings)) {
                    $this->command->info("Skipping duplicate mapping: $namaBarangPlatform -> $namaBarang");
                    continue;
                }

                // Create the mapping with the quantity from the CSV
                MappingBarang::create([
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);

                // Mark this platform product as having valid mappings
                $validPlatformProducts[$platformProduct->id] = true;

                $processedMappings[] = $mappingKey;
                $success++;
                $this->command->info("Created mapping: $namaBarangPlatform -> $namaBarang with quantity: $quantity");
            } catch (\Exception $e) {
                $failed++;
                $failedProducts[] = [
                    'row' => $index + 2,
                    'product' => $namaBarang ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                $this->command->error("Failed to import row " . ($index + 2) . ": " . ($namaBarang ?? 'Unknown') . " - " . $e->getMessage());
                $this->command->error("Error details: " . $e->getMessage());
                $this->command->error("Row data: " . implode(', ', $row));
            }
        }

        // Clean up platform products that don't have any valid mappings
        $this->command->info("Cleaning up platform products without valid mappings...");
        $platformProductsToDelete = PlatformProduct::where('platform_id', $tiktokPlatformId)
            ->whereNotIn('id', array_keys($validPlatformProducts))
            ->get();
        
        $cleanedCount = 0;
        foreach ($platformProductsToDelete as $platformProduct) {
            $platformProduct->delete();
            $cleanedCount++;
        }
        
        $this->command->info("TikTokMappingSeeder completed. Total: $total, Success: $success, Failed: $failed, Skipped: $skipped, Cleaned: $cleanedCount");
        
        if ($failed > 0) {
            $this->command->error("Failed products:");
            foreach ($failedProducts as $failedProduct) {
                $this->command->error("- Row {$failedProduct['row']}: {$failedProduct['product']} - {$failedProduct['error']}");
            }
        }
    }
} 
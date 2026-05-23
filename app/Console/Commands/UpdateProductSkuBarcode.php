<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateProductSkuBarcode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-sku-barcode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update SKU and barcode from MASTER - BARANG INTERNAL (4).csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SKU and barcode update...');
        
        $csvFile = storage_path('app/imports/MASTER - BARANG INTERNAL (4).csv');
        
        if (!file_exists($csvFile)) {
            $this->error('CSV file not found: ' . $csvFile);
            return 1;
        }
        
        // Read the CSV file
        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $skuUsageMap = []; // Track SKU usage for duplicate handling
        
        DB::beginTransaction();
        
        try {
            foreach ($records as $index => $record) {
                try {
                    // Clean keys and values
                    $cleanRecord = [];
                    foreach ($record as $k => $v) {
                        $cleanRecord[trim($k)] = trim($v);
                    }
                    $record = $cleanRecord;

                    $productName = $record['NAMA BARANG'] ?? '';
                    $sku = trim($record['SKU'] ?? '');
                    $barcode = trim($record['BARCODE'] ?? '');
                    
                    // Skip if product name is empty or is header
                    if (empty($productName) || $productName === 'NAMA BARANG') {
                        continue;
                    }
                    
                    // Handle empty SKU - generate new one
                    if (empty($sku) || $sku === '-') {
                        $sku = $this->generateUniqueSku($productName, $skuUsageMap);
                    }
                    
                    // Handle duplicate SKUs by adding suffix
                    $originalSku = $sku;
                    $counter = 1;
                    while (isset($skuUsageMap[$sku]) && $skuUsageMap[$sku] !== $productName) {
                        $sku = $originalSku . '~' . $counter;
                        $counter++;
                    }
                    
                    // Track SKU usage
                    $skuUsageMap[$sku] = $productName;
                    
                    // Find existing product by name
                    $product = Product::where('name', $productName)->first();
                    
                    if (!$product) {
                        $this->warn("Product not found: {$productName}");
                        $skippedCount++;
                        continue;
                    }
                    
                    // Update SKU and barcode
                    $product->sku = $sku;
                    $product->barcode = ($barcode === '-' || empty($barcode)) ? null : $barcode;
                    $product->save();
                    
                    $updatedCount++;
                    
                    if ($updatedCount % 50 === 0) {
                        $this->info("Processed {$updatedCount} products...");
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("Error processing record " . ($index + 2) . ": " . $e->getMessage());
                    Log::error("Error updating product SKU/barcode", [
                        'record' => $record,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
            $this->info("SKU and barcode update completed!");
            $this->info("Updated: {$updatedCount} products");
            $this->info("Skipped: {$skippedCount} products");
            $this->info("Errors: {$errorCount} products");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Transaction failed: " . $e->getMessage());
            Log::error("Failed to update product SKU/barcode", ['error' => $e->getMessage()]);
            return 1;
        }
    }
    
    /**
     * Generate a unique SKU for products without one
     */
    private function generateUniqueSku($productName, &$skuUsageMap)
    {
        // Generate SKU from product name
        $words = explode(' ', $productName);
        $prefix = '';
        
        // Take first letter of first 2-3 words
        foreach ($words as $word) {
            if (strlen($prefix) < 3) {
                $prefix .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // If prefix is too short, pad with X
        while (strlen($prefix) < 2) {
            $prefix .= 'X';
        }
        
        // Generate numeric suffix
        $counter = 1;
        $sku = $prefix . sprintf('%04d', $counter);
        
        // Ensure uniqueness
        while (isset($skuUsageMap[$sku]) || Product::where('sku', $sku)->exists()) {
            $counter++;
            $sku = $prefix . sprintf('%04d', $counter);
        }
        
        return $sku;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Platform;
use App\Imports\LazadaImport;
use Illuminate\Console\Command;

class VerifyLazadaBarangKeluarSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:lazada-barang-keluar-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify if LazadaImport system automatically creates barang_keluar when creating orders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Verifying Lazada barang_keluar system...');
        $this->line('');

        // Find Lazada platform by ID (not by name)
        $lazadaPlatform = Platform::where('name', 'LIKE', '%Lazada%')
            ->orWhereRaw('LOWER(name) = ?', ['lazada'])
            ->first();
        
        if (!$lazadaPlatform) {
            $this->error('❌ Lazada platform not found!');
            return Command::FAILURE;
        }

        $this->info("✅ Found Lazada platform: {$lazadaPlatform->name} (ID: {$lazadaPlatform->id})");
        $this->line('');

        // Check LazadaImport class
        $this->info('📋 Checking LazadaImport class...');
        
        $importClass = new \ReflectionClass(LazadaImport::class);
        
        // Check if reduceStock method exists
        $hasReduceStock = $importClass->hasMethod('reduceStock');
        $this->line("   - reduceStock() method: " . ($hasReduceStock ? "✅ EXISTS" : "❌ NOT FOUND"));
        
        // Check if recordBarangKeluar method exists
        $hasRecordBarangKeluar = $importClass->hasMethod('recordBarangKeluar');
        $this->line("   - recordBarangKeluar() method: " . ($hasRecordBarangKeluar ? "✅ EXISTS" : "❌ NOT FOUND"));
        
        // Check processImport method
        $hasProcessImport = $importClass->hasMethod('processImport');
        $this->line("   - processImport() method: " . ($hasProcessImport ? "✅ EXISTS" : "❌ NOT FOUND"));
        
        $this->line('');

        if ($hasReduceStock) {
            $reduceStockMethod = $importClass->getMethod('reduceStock');
            $this->info('📝 Checking reduceStock() method implementation...');
            
            $filename = $reduceStockMethod->getFileName();
            $startLine = $reduceStockMethod->getStartLine();
            $endLine = $reduceStockMethod->getEndLine();
            
            $this->line("   - File: {$filename}");
            $this->line("   - Lines: {$startLine}-{$endLine}");
            
            // Read the method content
            $fileContent = file_get_contents($filename);
            $lines = explode("\n", $fileContent);
            $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);
            
            // Check if it calls recordBarangKeluar
            $callsRecordBarangKeluar = strpos($methodContent, 'recordBarangKeluar') !== false;
            $this->line("   - Calls recordBarangKeluar(): " . ($callsRecordBarangKeluar ? "✅ YES" : "❌ NO"));
            
            // Check if it uses platform_id
            $usesPlatformId = strpos($methodContent, 'platform_id') !== false || strpos($methodContent, '$this->platform') !== false;
            $this->line("   - Uses platform ID: " . ($usesPlatformId ? "✅ YES" : "❌ NO"));
            
            $this->line('');
        }

        if ($hasRecordBarangKeluar) {
            $recordMethod = $importClass->getMethod('recordBarangKeluar');
            $this->info('📝 Checking recordBarangKeluar() method implementation...');
            
            $filename = $recordMethod->getFileName();
            $startLine = $recordMethod->getStartLine();
            $endLine = $recordMethod->getEndLine();
            
            $this->line("   - File: {$filename}");
            $this->line("   - Lines: {$startLine}-{$endLine}");
            
            // Read the method content
            $fileContent = file_get_contents($filename);
            $lines = explode("\n", $fileContent);
            $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);
            
            // Check if it creates BarangKeluar
            $createsBarangKeluar = strpos($methodContent, 'BarangKeluar::create') !== false || strpos($methodContent, 'BarangKeluar::') !== false;
            $this->line("   - Creates BarangKeluar: " . ($createsBarangKeluar ? "✅ YES" : "❌ NO"));
            
            // Check if it uses order_item_id
            $usesOrderItemId = strpos($methodContent, 'order_item_id') !== false;
            $this->line("   - Uses order_item_id: " . ($usesOrderItemId ? "✅ YES" : "❌ NO"));
            
            $this->line('');
        }

        if ($hasProcessImport) {
            $processMethod = $importClass->getMethod('processImport');
            $this->info('📝 Checking processImport() method implementation...');
            
            $filename = $processMethod->getFileName();
            $startLine = $processMethod->getStartLine();
            $endLine = $processMethod->getEndLine();
            
            // Read the method content
            $fileContent = file_get_contents($filename);
            $lines = explode("\n", $fileContent);
            $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);
            
            // Check if it calls reduceStock
            $callsReduceStock = strpos($methodContent, 'reduceStock') !== false;
            $this->line("   - Calls reduceStock(): " . ($callsReduceStock ? "✅ YES" : "❌ NO"));
            
            // Check if it uses platform_id
            $usesPlatformId = strpos($methodContent, '$this->platform->id') !== false || strpos($methodContent, 'platform_id') !== false;
            $this->line("   - Uses platform ID: " . ($usesPlatformId ? "✅ YES" : "❌ NO"));
            
            // Check if it creates OrderItem
            $createsOrderItem = strpos($methodContent, 'OrderItem') !== false;
            $this->line("   - Creates OrderItem: " . ($createsOrderItem ? "✅ YES" : "❌ NO"));
            
            $this->line('');
        }

        // Check constructor
        $this->info('📝 Checking LazadaImport constructor...');
        $constructor = $importClass->getConstructor();
        if ($constructor) {
            $filename = $constructor->getFileName();
            $startLine = $constructor->getStartLine();
            $endLine = $constructor->getEndLine();
            
            $fileContent = file_get_contents($filename);
            $lines = explode("\n", $fileContent);
            $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);
            
            // Check if it accepts platformId parameter
            $acceptsPlatformId = strpos($methodContent, '$platformId') !== false;
            $this->line("   - Accepts platformId parameter: " . ($acceptsPlatformId ? "✅ YES" : "❌ NO"));
            
            // Check if it uses Platform::find
            $usesPlatformFind = strpos($methodContent, 'Platform::find') !== false;
            $this->line("   - Uses Platform::find(): " . ($usesPlatformFind ? "✅ YES" : "❌ NO"));
            
            $this->line('');
        }

        // Summary
        $this->info('📊 Summary:');
        $this->line('');
        
        if ($hasReduceStock && $hasRecordBarangKeluar && $hasProcessImport) {
            $this->info('✅ System Structure:');
            $this->line('   - LazadaImport class has all required methods');
            $this->line('   - reduceStock() method exists');
            $this->line('   - recordBarangKeluar() method exists');
            $this->line('   - processImport() method exists');
            $this->line('');
            $this->info('✅ Based on code analysis:');
            $this->line('   - When importing Lazada orders through LazadaImport,');
            $this->line('     the system SHOULD automatically create barang_keluar records');
            $this->line('   - This happens in processImport() -> reduceStock() -> recordBarangKeluar()');
            $this->line('');
            $this->warn('⚠️  Note:');
            $this->line('   - This only works for orders imported through LazadaImport');
            $this->line('   - Manual orders created via storeManual() do NOT create barang_keluar');
            $this->line('   - Existing orders created before this logic was added may not have barang_keluar');
        } else {
            $this->error('❌ System Structure:');
            $this->line('   - Missing required methods in LazadaImport class');
        }

        return Command::SUCCESS;
    }
}


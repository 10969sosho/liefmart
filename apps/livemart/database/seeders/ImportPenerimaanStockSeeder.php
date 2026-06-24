<?php

namespace Database\Seeders;

use Shared\Helpers\NumberFormatter;
use App\Models\Lokasi;
use App\Models\MainCategory;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\TaxCategory;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class ImportPenerimaanStockSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    protected ?string $inputFilePath = null;

    protected ?string $inputTaxCategoryName = null;

    protected ?string $inputPoNumber = null;

    /**
     * Store the command from ImportPenerimaanStock
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    public function setImportOptions(array $options): void
    {
        $file = $options['file'] ?? null;
        $tax = $options['tax'] ?? null;
        $po = $options['po'] ?? null;

        $this->inputFilePath = $file ? (string) $file : null;
        $this->inputTaxCategoryName = $tax ? trim((string) $tax) : null;
        $this->inputPoNumber = $po ? trim((string) $po) : null;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($this->inputFilePath) {
            $taxCategoryName = $this->inputTaxCategoryName ?: $this->guessTaxCategoryName($this->inputFilePath, $this->inputPoNumber);
            if (! $taxCategoryName) {
                $this->logError('Tax category tidak bisa ditentukan. Isi --tax=HGN atau --tax=LM.');

                return;
            }

            if (! $this->inputPoNumber) {
                $this->logError('Nomor PO wajib diisi saat memakai file. Gunakan --po="BA/0011/2602/HGN-SDA"');

                return;
            }

            // Get Main Category SKINCARE
            $mainCategory = MainCategory::firstOrCreate(
                ['name' => 'SKINCARE'],
                ['description' => 'Kategori Utama SKINCARE', 'is_active' => true]
            );

            // Get Lokasi Gudang A
            $lokasi = Lokasi::where('kode', 'GUDANG_A')->first();
            if (! $lokasi) {
                $lokasi = Lokasi::find(2) ?? Lokasi::first();
            }

            $satuan = Satuan::first();

            $this->processFile(
                $this->inputFilePath,
                $taxCategoryName,
                $mainCategory,
                $lokasi,
                $satuan,
                $this->inputPoNumber
            );

            return;
        }

        // Define files to process and their Tax Category
        // Note: You mentioned two files:
        // 1. MASTER - NON PKP - ED.csv (Tax LM)
        // 2. MASTER - PKP - ED.csv (Tax HGN) - But in 'ls' check, only NON PKP was found.
        // I will assume the PKP file might be there or will be there.
        // Let's check for both, if not exists, skip.

        $files = [
            'MASTER - PKP - ED.csv' => 'HGN',
            'MASTER - NON PKP - ED.csv' => 'LM',
        ];

        // Get Main Category SKINCARE
        $mainCategory = MainCategory::firstOrCreate(
            ['name' => 'SKINCARE'],
            ['description' => 'Kategori Utama SKINCARE', 'is_active' => true]
        );

        // Get Lokasi Gudang A
        $lokasi = Lokasi::where('kode', 'GUDANG_A')->first();
        if (! $lokasi) {
            $lokasi = Lokasi::find(2) ?? Lokasi::first(); // Fallback
        }

        // Get Satuan (Assuming ID 1 for now, or default)
        $satuan = Satuan::first();

        foreach ($files as $filename => $taxCategoryName) {
            $this->processFile($filename, $taxCategoryName, $mainCategory, $lokasi, $satuan);
        }
    }

    private function processFile($filename, $taxCategoryName, $mainCategory, $lokasi, $satuan, $overridePoNumber = null)
    {
        $isAbsolutePath = is_string($filename) && (str_starts_with($filename, '/') || preg_match('/^[A-Za-z]:\\\\/', $filename));
        $csvPath = $isAbsolutePath ? $filename : storage_path('app/imports/barangdatang/'.$filename);

        if (! $isAbsolutePath && ! File::exists($csvPath)) {
            $csvPathStock = storage_path('app/imports/STOCK/'.$filename);
            if (File::exists($csvPathStock)) {
                $csvPath = $csvPathStock;
            }
        }

        if (! File::exists($csvPath)) {
            $this->logInfo("File not found: {$filename}. Skipping.");

            return;
        }

        $displayName = $isAbsolutePath ? basename($csvPath) : $filename;
        $this->logInfo("Processing {$displayName} (Tax: {$taxCategoryName})...");

        // Get Tax Category
        $taxCategory = $this->resolveTaxCategory($taxCategoryName, $mainCategory->id);
        if (! $taxCategory) {
            $this->logError("Tax Category {$taxCategoryName} not found!");

            return;
        }

        // Read CSV
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        DB::beginTransaction();
        try {
            $date = Carbon::now();
            $poNumber = $overridePoNumber ?: ('PO-IMP-'.$taxCategoryName.'-'.$date->format('YmdHis'));
            $kodePenerimaan = 'GR-'.$taxCategoryName.'-'.$date->format('YmdHis');

            // Create Penerimaan Header (Status Located directly because we will create stock)
            $existingPo = Penerimaan::where('nomor_po', $poNumber)->exists();
            if ($existingPo) {
                $this->logError("Nomor PO sudah ada: {$poNumber}");
                DB::rollBack();

                return;
            }

            $penerimaan = Penerimaan::create([
                'kode_penerimaan' => $kodePenerimaan,
                'main_category_id' => $mainCategory->id,
                'tax_category_id' => $taxCategory->id,
                'lokasi_id' => $lokasi->id,
                'nomor_po' => $poNumber,
                'tanggal_penerimaan' => $date->toDateString(),
                'metode_pembayaran' => 'Cash',
                'status' => 'Located', // Directly Located
                'catatan' => "Imported from {$displayName}",
                'total_harga' => 0,
            ]);

            $totalHarga = 0;
            $itemsProcessed = 0;
            $itemsSkipped = 0;

            foreach ($records as $record) {
                // Clean keys just in case
                $cleanRecord = [];
                foreach ($record as $k => $v) {
                    $cleanRecord[trim($k)] = trim($v);
                }
                $record = $cleanRecord;

                $productName = trim($record['NAMA BARANG'] ?? '');
                // Clean product name logic from Master import if needed (remove "02 - ")?
                // Master import kept "02 - ". So we should match exact string.
                // However, the Master Import used `trim($record['NAMA BARANG'])`.
                // Let's use `trim` here too.

                $qtyRaw = $record['QTY'] ?? '0';
                $hargaRaw = $record['HARGA'] ?? '0';
                $edString = trim($record['ED'] ?? '');

                if (empty($productName) || $productName === 'NAMA BARANG') {
                    continue;
                }

                $qty = $this->parseQty($qtyRaw);
                $hargaHpp = NumberFormatter::parseNumericValue($hargaRaw);

                // Find Product
                // Try exact match first
                $product = Product::where('name', $productName)->first();

                if (! $product) {
                    // Try removing "02 - " prefix if present in CSV but not in DB (or vice versa)
                    // In Master Import, we kept "02 - " prefix if it was in CSV.
                    // Let's assume consistent naming.
                    // But just in case, try fuzzy matching or trimmed.

                    // Try removing numeric prefix "02 - "
                    $cleanName = preg_replace('/^\d+\s*-\s*/', '', $productName);
                    $product = Product::where('name', $cleanName)->first();

                    if (! $product) {
                        // Try adding prefix "02 - " if missing?
                        // Or try LIKE search
                        $product = Product::where('name', 'LIKE', "%{$cleanName}%")->first();
                    }
                }

                if (! $product) {
                    // $this->logWarn("Product not found: {$productName}. Skipping.");
                    $itemsSkipped++;

                    continue;
                }

                $subtotal = NumberFormatter::calculateSubtotal($hargaHpp, $qty);
                $totalHarga += $subtotal;

                // Create Penerimaan Detail
                $detail = PenerimaanDetail::create([
                    'penerimaan_id' => $penerimaan->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'satuan_id' => $satuan->id,
                    'harga_hpp' => $hargaHpp,
                    'subtotal' => $subtotal,
                    'is_free' => false,
                ]);

                // Determine ED
                $expiredDate = null;
                $statusEd = 'aman';

                if (! empty($edString)) {
                    try {
                        // Normalize separators
                        $edString = str_replace(['-', '.'], '/', $edString);
                        // Handle "01/01/2030"
                        $expiredDate = Carbon::createFromFormat('d/m/Y', $edString)->format('Y-m-d');

                        $ed = Carbon::parse($expiredDate);
                        $now = Carbon::now();
                        $diffInMonths = $now->diffInMonths($ed, false);

                        if ($diffInMonths < 0) {
                            $statusEd = 'kadaluarsa';
                        } elseif ($diffInMonths <= 3) {
                            $statusEd = 'hampir_kadaluarsa';
                        }
                    } catch (\Exception $e) {
                        // Invalid date format
                    }
                }

                // Create Warehouse Stock
                WarehouseStock::create([
                    'product_id' => $product->id,
                    'lokasi_id' => $lokasi->id,
                    'penerimaan_detail_id' => $detail->id,
                    'tax_id' => $taxCategory->id,
                    'qty' => $qty,
                    'expired_date' => $expiredDate,
                    'status_ed' => $statusEd,
                    'catatan' => "Stock from {$kodePenerimaan}",
                ]);

                $itemsProcessed++;
            }

            // Update Total Harga
            $penerimaan->update(['total_harga' => $totalHarga]);

            DB::commit();
            $this->logInfo("Imported {$itemsProcessed} items for {$taxCategoryName}. Skipped {$itemsSkipped} items.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error processing {$filename}: ".$e->getMessage());
        }
    }

    private function guessTaxCategoryName(string $filePath, ?string $poNumber): ?string
    {
        $haystack = strtoupper(($poNumber ?? '').' '.basename($filePath));
        if (str_contains($haystack, 'HGN')) {
            return 'HGN';
        }
        if (str_contains($haystack, 'LM')) {
            return 'LM';
        }

        return null;
    }

    private function resolveTaxCategory(string $taxInput, int $mainCategoryId): ?TaxCategory
    {
        $taxInput = trim($taxInput);
        if ($taxInput === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $taxInput)) {
            return TaxCategory::find((int) $taxInput);
        }

        $key = strtoupper($taxInput);
        $mappedId = null;

        if ($key === 'HGN') {
            $mappedId = 3;
        }
        if ($key === 'LM') {
            $mappedId = 4;
        }

        if ($mappedId) {
            $byId = TaxCategory::find($mappedId);
            if ($byId) {
                return $byId;
            }
        }

        if (in_array($key, ['PKP'], true)) {
            return TaxCategory::where('main_category_id', $mainCategoryId)
                ->whereIn('name', ['PKP', 'HGN'])
                ->orderBy('id')
                ->first();
        }

        if (in_array($key, ['NONPKP', 'NON PKP', 'NON-PKP', 'NON_PKP', 'LM'], true)) {
            return TaxCategory::where('main_category_id', $mainCategoryId)
                ->whereIn('name', ['NON PKP', 'Non-PKP', 'LM', 'NON_PKP'])
                ->orderBy('id')
                ->first();
        }

        return TaxCategory::where('main_category_id', $mainCategoryId)
            ->where('name', $taxInput)
            ->orderBy('id')
            ->first()
            ?: TaxCategory::where('name', $taxInput)->orderBy('id')->first();
    }

    private function parseQty($value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $cleaned = preg_replace('/[^\d,.-]/', '', $value);
        if ($cleaned === '' || $cleaned === null) {
            return 0.0;
        }

        if (str_contains($cleaned, '.') && ! str_contains($cleaned, ',') && preg_match('/^\d{1,3}(\.\d{3})+$/', $cleaned)) {
            return (float) str_replace('.', '', $cleaned);
        }

        return NumberFormatter::parseNumericValue($cleaned);
    }

    private function logInfo($message)
    {
        if ($this->command) {
            $this->command->info($message);
        }
        Log::info($message);
    }

    private function logError($message)
    {
        if ($this->command) {
            $this->command->error($message);
        }
        Log::error($message);
    }

    private function logWarn($message)
    {
        if ($this->command) {
            $this->command->warn($message);
        }
        Log::warning($message);
    }
}

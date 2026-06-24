<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportPenerimaanPKPSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    /**
     * Store the command from ImportPenerimaanPKP
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
            $this->command->info('Importing Penerimaan PKP (Tax: HGN)...');
        }
        
        // Check if the CSV file exists
        $csvPath = storage_path('app/imports/barangdatang/MASTER - PKP.csv');
        if (!File::exists($csvPath)) {
            if ($this->command) {
                $this->command->error('CSV file not found at: ' . $csvPath);
            }
            return;
        }

        // Get Tax Category HGN
        $taxCategory = TaxCategory::where('name', 'HGN')->first();
        if (!$taxCategory) {
            if ($this->command) {
                $this->command->error('Tax Category HGN not found!');
            }
            return;
        }

        // Get Main Category SKINCARE
        $mainCategory = MainCategory::where('name', 'SKINCARE')->first();
        if (!$mainCategory) {
            if ($this->command) {
                $this->command->error('Main Category SKINCARE not found!');
            }
            return;
        }

        // Get Lokasi (Assuming ID 1)
        $lokasi = Lokasi::find(1);
        if (!$lokasi) {
            if ($this->command) {
                $this->command->error('Lokasi ID 1 not found!');
            }
            return;
        }

        // Get Satuan (Assuming ID 1)
        $satuan = Satuan::find(1);
        if (!$satuan) {
            if ($this->command) {
                $this->command->error('Satuan ID 1 not found!');
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
            $cleanRecord = [];
            foreach ($row as $key => $value) {
                $cleanKey = trim($key);
                $cleanRecord[$cleanKey] = trim($value);
            }
            $records[] = $cleanRecord;
        }

        // Begin transaction
        DB::beginTransaction();
        try {
            $date = Carbon::now();
            $poNumber = 'PO-IMPORT-' . $date->format('YmdHis');
            $kodePenerimaan = 'GR-' . $date->format('YmdHis');

            // Create Penerimaan Header
            $penerimaan = Penerimaan::create([
                'kode_penerimaan' => $kodePenerimaan,
                'main_category_id' => $mainCategory->id,
                'tax_category_id' => $taxCategory->id,
                'lokasi_id' => $lokasi->id,
                'nomor_po' => $poNumber,
                'tanggal_penerimaan' => $date->toDateString(),
                'metode_pembayaran' => 'Cash',
                'status' => 'Unlocated',
                'catatan' => 'Imported from MASTER - PKP.csv with Tax HGN',
                'total_harga' => 0 // Will update later
            ]);

            $totalHarga = 0;
            $itemsProcessed = 0;

            foreach ($records as $record) {
                $productName = $record['NAMA BARANG'] ?? '';
                $qty = $record['QTY'] ?? '0';
                $harga = $record['HARGA'] ?? '0';

                // Skip if product name is empty or is header
                if (empty($productName) || $productName === 'NAMA BARANG') {
                    continue;
                }

                // Clean up Qty
                $qty = (float) $qty;

                // Clean up Price (e.g. "27747,75" -> 27747.75)
                // Remove dots (thousands separator) and replace comma with dot (decimal separator)
                $cleanHarga = str_replace('.', '', $harga); // Remove dots
                $cleanHarga = str_replace(',', '.', $cleanHarga); // Replace comma with dot
                $hargaHpp = (float) $cleanHarga;

                // Find Product
                $product = Product::where('name', 'like', $productName . '%')->first(); // Use LIKE for flexibility
                if (!$product) {
                    if ($this->command) {
                        $this->command->warn("Product not found: {$productName}. Skipping...");
                    }
                    continue;
                }

                $subtotal = $qty * $hargaHpp;
                $totalHarga += $subtotal;

                // Create Penerimaan Detail
                PenerimaanDetail::create([
                    'penerimaan_id' => $penerimaan->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'satuan_id' => $satuan->id,
                    'harga_hpp' => $hargaHpp,
                    'subtotal' => $subtotal,
                    'is_free' => false,
                ]);

                $itemsProcessed++;
            }

            // Update Total Harga
            $penerimaan->update(['total_harga' => $totalHarga]);

            DB::commit();

            if ($this->command) {
                $this->command->info("Successfully imported {$itemsProcessed} items into Penerimaan ID: {$penerimaan->id}");
                $this->command->info("Kode Penerimaan: {$kodePenerimaan}");
                $this->command->info("Total Harga: " . number_format($totalHarga, 2));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->command) {
                $this->command->error("Error importing penerimaan: " . $e->getMessage());
            }
            Log::error("Import Penerimaan Error: " . $e->getMessage());
        }
    }
}

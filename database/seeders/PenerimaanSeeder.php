<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Helpers\NumberFormatter;
use Carbon\Carbon;

class PenerimaanSeeder extends Seeder
{
    public function run()
    {
        // Path to import directory
        $importPath = storage_path('app/imports/barangdatang');
        if (!is_dir($importPath)) {
            $this->command->warn('Import directory not found: ' . $importPath);
            return;
        }
        $files = glob($importPath . '/*.csv');
        if (empty($files)) {
            $this->command->warn('No CSV files found in: ' . $importPath);
            return;
        }
        foreach ($files as $filePath) {
            // Detect PKP or NONPKP from filename
            $type = stripos($filePath, 'NON PKP') !== false ? 'NONPKP' : 'PKP';
            $this->importCsv($type, $filePath);
        }
    }

    /**
     * Import a CSV file as penerimaan
     * @param string $type PKP or NONPKP
     * @param string $filePath
     */
    private function importCsv($type, $filePath)
    {
        $taxCategoryId = $type === 'PKP' ? 3 : 4;
        $paymentMethod = PaymentMethod::find(1);
        if (!$paymentMethod) {
            $this->command->error('Payment method with ID 1 not found');
            return;
        }
        $csv = array_map('str_getcsv', file($filePath));
        $header = array_shift($csv);
        $header = array_map('trim', $header); // Trim whitespace from headers
        if (array_map('strtoupper', $header) !== ['NAMA BARANG', 'QTY', 'HARGA']) {
            $this->command->error('CSV format incorrect in ' . basename($filePath) . '. Expected: NAMA BARANG, QTY, HARGA but got: ' . implode(', ', $header));
            return;
        }
        $totalHarga = 0;
        $details = [];
        foreach ($csv as $row) {
            if (count($row) < 3) {
                $this->command->error("Row with insufficient data: " . implode(',', $row));
                throw new \Exception("Row with insufficient data: " . implode(',', $row));
            }
            $productName = trim($row[0]);
            $qty = NumberFormatter::parseNumericValue(trim($row[1]));
            $harga = NumberFormatter::parseNumericValue(trim($row[2]));
            $product = Product::where('name', $productName)->first();
            if (!$product) {
                $this->command->error("Product not found: {$productName} (file: " . basename($filePath) . ")");
                throw new \Exception("Product not found: {$productName} (file: " . basename($filePath) . ")");
            }
            $subtotal = NumberFormatter::calculateSubtotal($harga, $qty);
            $totalHarga += $subtotal;
            $details[] = [
                'product_id' => $product->id,
                'qty' => $qty,
                'satuan_id' => 1,
                'harga_hpp' => $harga,
                'subtotal' => $subtotal,
            ];
        }
        if (empty($details)) {
            $this->command->error('No valid products found in ' . basename($filePath));
            throw new \Exception('No valid products found in ' . basename($filePath));
        }
        $nomorPo = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $tanggalPenerimaan = Carbon::now();
        $tanggalJatuhTempo = method_exists($paymentMethod, 'calculateDueDate') ? $paymentMethod->calculateDueDate($tanggalPenerimaan) : $tanggalPenerimaan;
        $penerimaan = Penerimaan::create([
            'kode_penerimaan' => 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'main_category_id' => 2,
            'tax_category_id' => $taxCategoryId,
            'nomor_po' => $nomorPo,
            'tanggal_penerimaan' => $tanggalPenerimaan,
            'metode_pembayaran' => 1,
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
            'total_harga' => $totalHarga,
            'status' => 'Unlocated',
            'catatan' => null,
            'lokasi_id' => 1,
        ]);
        foreach ($details as $detail) {
            PenerimaanDetail::create([
                'penerimaan_id' => $penerimaan->id,
                'product_id' => $detail['product_id'],
                'qty' => $detail['qty'],
                'satuan_id' => $detail['satuan_id'],
                'harga_hpp' => $detail['harga_hpp'],
                'diskon_persen_1' => 0,
                'diskon_nominal_1' => 0,
                'diskon_persen_2' => 0,
                'diskon_nominal_2' => 0,
                'diskon_persen_3' => 0,
                'diskon_nominal_3' => 0,
                'diskon_persen_4' => 0,
                'diskon_nominal_4' => 0,
                'diskon_persen_5' => 0,
                'diskon_nominal_5' => 0,
                'is_free' => 0,
                'subtotal' => $detail['subtotal'],
                'catatan' => null,
            ]);
        }
        $this->command->info("Imported " . count($details) . " items from " . basename($filePath) . " as {$type} penerimaan.");
    }
}
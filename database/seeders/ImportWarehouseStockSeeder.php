<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penerimaan;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportWarehouseStockSeeder extends Seeder
{
    /**
     * The command to output information
     */
    protected $command;

    /**
     * Store the command from ImportWarehouseStock
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
        $files = [
            'MASTER - PKP - ED.csv' => 'HGN',
            'MASTER - NON PKP -ED.csv' => 'LM',
        ];

        // Find Lokasi Gudang A (or create if not exists, though usually ID 2)
        $lokasi = Lokasi::where('kode', 'GUDANG_A')->first();
        if (!$lokasi) {
             // Fallback to ID 2 or create
             $lokasi = Lokasi::find(2);
             if (!$lokasi) {
                 $lokasi = Lokasi::create([
                    'kode' => 'GUDANG_A',
                    'nama' => 'Gudang A',
                    'deskripsi' => 'Gudang utama penyimpanan barang',
                 ]);
             }
        }

        foreach ($files as $filename => $taxCategoryName) {
            $this->processFile($filename, $taxCategoryName, $lokasi);
        }
    }

    private function processFile($filename, $taxCategoryName, $lokasi)
    {
        if ($this->command) {
            $this->command->info("Processing Warehouse Stock for {$taxCategoryName} from {$filename}...");
        }

        $csvPath = storage_path('app/imports/STOCK/' . $filename);
        if (!File::exists($csvPath)) {
            if ($this->command) {
                $this->command->error("File not found: {$csvPath}");
            }
            return;
        }

        // Read CSV to get ED mapping
        // Map: Product Name -> ED
        $productEDMap = [];
        
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $record) {
            $productName = trim($record['NAMA BARANG'] ?? '');
            $edString = trim($record['ED'] ?? '');
            
            if (!empty($productName) && !empty($edString)) {
                try {
                    // Normalize separators: convert '-' and '.' to '/'
                    $edString = str_replace(['-', '.'], '/', $edString);
                    $edDate = Carbon::createFromFormat('d/m/Y', $edString)->format('Y-m-d');
                    $productEDMap[$productName] = $edDate;
                } catch (\Exception $e) {
                    if ($this->command) {
                        // $this->command->warn("Invalid Date Format for {$productName}: {$edString}");
                    }
                }
            }
        }

        // Get Tax Category ID
        $taxCategory = \App\Models\TaxCategory::where('name', $taxCategoryName)->first();
        if (!$taxCategory) {
            if ($this->command) {
                $this->command->error("Tax Category {$taxCategoryName} not found.");
            }
            return;
        }

        // Get Unlocated Penerimaan for this Tax Category
        $penerimaans = Penerimaan::where('tax_category_id', $taxCategory->id)
            ->where('status', 'Unlocated')
            ->with('details.product')
            ->get();

        if ($penerimaans->isEmpty()) {
            if ($this->command) {
                $this->command->info("No Unlocated Penerimaan found for {$taxCategoryName}.");
            }
            return;
        }

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($penerimaans as $penerimaan) {
                foreach ($penerimaan->details as $detail) {
                    $productName = $detail->product->name;
                    $expiredDate = $productEDMap[$productName] ?? null;

                    // Determine Status ED
                    $statusEd = 'aman';
                    if ($expiredDate) {
                        $ed = Carbon::parse($expiredDate);
                        $now = Carbon::now();
                        $diffInMonths = $now->diffInMonths($ed, false);
                        
                        if ($diffInMonths < 0) {
                            $statusEd = 'kadaluarsa';
                        } elseif ($diffInMonths <= 3) {
                            $statusEd = 'hampir_kadaluarsa';
                        }
                    }

                    // Create Warehouse Stock
                    WarehouseStock::create([
                        'product_id' => $detail->product_id,
                        'lokasi_id' => $lokasi->id,
                        'penerimaan_detail_id' => $detail->id,
                        'tax_id' => $taxCategory->id, // Use taxCategory->id directly
                        'qty' => $detail->qty,
                        'expired_date' => $expiredDate,
                        'status_ed' => $statusEd,
                        'catatan' => 'Imported from Penerimaan ' . $penerimaan->kode_penerimaan,
                    ]);

                    $count++;
                }
                
                // Update Penerimaan Status to Located
                $penerimaan->update([
                    'status' => 'Located',
                    'lokasi_id' => $lokasi->id
                ]);
            }
            
            DB::commit();
            
            if ($this->command) {
                $this->command->info("Completed {$taxCategoryName}: Processed {$count} stock items from Penerimaan.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->command) {
                $this->command->error("Error processing {$taxCategoryName}: " . $e->getMessage());
            }
        }
    }
}

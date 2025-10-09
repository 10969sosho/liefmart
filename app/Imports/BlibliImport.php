<?php

namespace App\Imports;

use App\Models\BarangKeluar;
use App\Models\MappingBarang;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class BlibliImport extends DefaultValueBinder implements ToCollection, WithMultipleSheets, WithCustomValueBinder
{
    protected $platform;

    protected $data = [];

    protected $unmappedProducts = [];

    protected $invalidData = [];

    protected $headerIssues = [];

    protected $headerRowIndex = 0;

    protected $columnMapping = [];

    // Counter untuk statistik impor
    protected $totalRows = 0;

    protected $skippedOrders = 0;

    protected $duplicateOrders = 0;

    protected $skippedForMissingDate = 0;

    protected $skippedForMissingDay = 0;

    protected $skippedForMissingResi = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Dapatkan platform Blibli dari database
        $this->platform = Platform::where('name', 'blibli')->first();

        // Jika platform tidak ditemukan, buat baru
        if (! $this->platform) {
            $this->platform = Platform::create(['name' => 'blibli']);
        }
    }

    public function sheets(): array
    {
        return [
            0 => $this, // Gunakan sheet pertama (indeks 0)
        ];
    }
    
    /**
     * Bind value to handle different Excel formats
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        
        // Handle dates better
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // Fallback to default handling
        return parent::bindValue($cell, $value);
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = count($rows) - 1; // Kurangi 1 untuk header

        // Cari baris header
        $headerFound = false;
        foreach ($rows as $index => $row) {
            if ($this->isHeaderRow($row)) {
                $this->headerRowIndex = $index;
                $headerFound = true;
                break;
            }
        }

        if (!$headerFound) {
            $this->headerIssues[] = 'Header tidak ditemukan';
            return;
        }

        // Mapping kolom berdasarkan header
        $this->mapColumns($rows[$this->headerRowIndex]);

        // Proses data setelah header
        for ($i = $this->headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip baris kosong
            if ($this->isRowEmpty($row)) {
                continue;
            }

            // Validasi data
            $rowData = $this->validateAndProcessRow($row, $i + 1);
            
            if ($rowData) {
                $this->data[] = $rowData;
            }
        }
    }

    protected function isHeaderRow($row)
    {
        $requiredHeaders = [
            'NOMOR PESANAN',
            'TANGGAL',
            'HARI',
            'STATUS HARI',
            'NAMA PRODUK',
            'VARIASI',
            'QTY',
            'HARGA SETELAH DISKON',
            'NOMOR RESI'
        ];

        $rowValues = array_map('strtoupper', array_filter($row->toArray()));
        $matches = array_intersect($requiredHeaders, $rowValues);

        return count($matches) >= 5; // Minimal 5 header yang cocok
    }

    protected function mapColumns($headerRow)
    {
        $this->columnMapping = [
            'no_order' => null,
            'tanggal' => null,
            'hari' => null,
            'status_hari' => null,
            'nama_produk' => null,
            'variasi' => null,
            'qty' => null,
            'harga_setelah_diskon' => null,
            'no_resi' => null
        ];

        foreach ($headerRow as $index => $value) {
            $value = strtoupper(trim($value));
            
            switch ($value) {
                case 'NOMOR PESANAN':
                    $this->columnMapping['no_order'] = $index;
                    break;
                case 'TANGGAL':
                    $this->columnMapping['tanggal'] = $index;
                    break;
                case 'HARI':
                    $this->columnMapping['hari'] = $index;
                    break;
                case 'STATUS HARI':
                    $this->columnMapping['status_hari'] = $index;
                    break;
                case 'NAMA PRODUK':
                    $this->columnMapping['nama_produk'] = $index;
                    break;
                case 'VARIASI':
                    $this->columnMapping['variasi'] = $index;
                    break;
                case 'QTY':
                    $this->columnMapping['qty'] = $index;
                    break;
                case 'HARGA SETELAH DISKON':
                    $this->columnMapping['harga_setelah_diskon'] = $index;
                    break;
                case 'NOMOR RESI':
                    $this->columnMapping['no_resi'] = $index;
                    break;
            }
        }

        // Validasi mapping (abaikan kolom variasi)
        $requiredColumns = ['no_order', 'tanggal', 'hari', 'nama_produk', 'qty', 'harga_setelah_diskon', 'no_resi'];
        foreach ($requiredColumns as $key) {
            if ($this->columnMapping[$key] === null) {
                $this->headerIssues[] = "Kolom $key tidak ditemukan";
            }
        }
    }

    protected function isRowEmpty($row)
    {
        return empty(array_filter($row->toArray()));
    }

    protected function validateAndProcessRow($row, $rowNumber)
    {
        $rowData = [];
        $issues = [];

        // Ambil data berdasarkan mapping kolom
        foreach ($this->columnMapping as $key => $index) {
            $value = isset($row[$index]) ? trim($row[$index]) : null;
            
            // Special handling for status_hari to support multiple values
            if ($key === 'status_hari' && !empty($value)) {
                // If value contains comma, it's already in the correct format for multiple values
                // If not, it's a single value
                if (strpos($value, ',') === false) {
                    // Single value, keep as is
                    $rowData[$key] = $value;
                } else {
                    // Multiple values separated by comma, keep as is
                    $rowData[$key] = $value;
                }
            } else {
                $rowData[$key] = $value;
            }
        }

        // Pastikan variasi selalu ada walaupun null
        if (!isset($rowData['variasi'])) {
            $rowData['variasi'] = '';
        }

        // Log data untuk debugging
        \Log::info("Data baris $rowNumber: " . json_encode($rowData));

        // Validasi tanggal
        if (empty($rowData['tanggal'])) {
            $this->skippedForMissingDate++;
            return null;
        }

        try {
            // Log format tanggal sebelum parsing
            \Log::info("Format tanggal baris $rowNumber: " . $rowData['tanggal']);
            
            // Coba konversi dari format serial Excel ke Carbon
            if (is_numeric($rowData['tanggal'])) {
                // Excel serial date dimulai dari 1-Jan-1900 (1)
                // Untuk PHP, kita gunakan UNIX timestamp dengan Carbon, jadi perlu konversi
                // Kurangi 25569 untuk konversi ke Unix timestamp (hari antara 1-Jan-1900 dan 1-Jan-1970)
                try {
                    $excelDate = (int)$rowData['tanggal'];
                    $date = Carbon::createFromTimestamp(($excelDate - 25569) * 86400);
                    // Format tanggal untuk hanya menampilkan tanggal-bulan-tahun saja
                    $date->startOfDay(); // Set jam, menit, detik ke 00:00:00
                    $rowData['tanggal'] = $date;
                    \Log::info("Berhasil konversi tanggal Excel: $excelDate menjadi " . $date->format('Y-m-d'));
                } catch (\Exception $e) {
                    throw new \Exception("Gagal konversi tanggal Excel: " . $e->getMessage());
                }
            } else {
                // Jika bukan numeric, coba parse dengan format Y-m-d
                $rowData['tanggal'] = Carbon::createFromFormat('Y-m-d', $rowData['tanggal']);
            }
        } catch (\Exception $e) {
            \Log::error("Error parsing tanggal baris $rowNumber: " . $e->getMessage());
            $issues[] = 'Format tanggal tidak valid';
            $this->invalidData[] = [
                'row' => $rowNumber,
                'data' => $rowData,
                'issues' => $issues
            ];
            return null;
        }

        // Validasi hari
        if (empty($rowData['hari'])) {
            $this->skippedForMissingDay++;
            return null;
        }

        // Validasi nomor resi
        if (empty($rowData['no_resi'])) {
            $this->skippedForMissingResi++;
        }

        // Validasi qty
        if (!is_numeric($rowData['qty']) || $rowData['qty'] <= 0) {
            \Log::warning("QTY tidak valid pada baris $rowNumber: " . $rowData['qty']);
            $issues[] = 'QTY tidak valid';
        }

        // Validasi harga
        if (!is_numeric($rowData['harga_setelah_diskon']) || $rowData['harga_setelah_diskon'] <= 0) {
            \Log::warning("Harga tidak valid pada baris $rowNumber: " . $rowData['harga_setelah_diskon']);
            $issues[] = 'Harga tidak valid';
        }

        // Cek mapping produk
        $productMapping = PlatformProduct::where('platform_id', $this->platform->id)
            ->where('platform_product_name', $rowData['nama_produk'])
            ->first();

        if (!$productMapping) {
            // Hanya tambahkan nama produk saja ke unmapped products, tanpa variasi
            if (!in_array($rowData['nama_produk'], $this->unmappedProducts)) {
                \Log::warning("Produk tidak ditemukan pada baris $rowNumber: " . $rowData['nama_produk']);
                $this->unmappedProducts[] = $rowData['nama_produk'];
            }
        } else {
            $rowData['product_id'] = $productMapping->product_id;
        }

        if (!empty($issues)) {
            \Log::warning("Baris $rowNumber memiliki issues: " . implode(', ', $issues));
            $this->invalidData[] = [
                'row' => $rowNumber,
                'data' => $rowData,
                'issues' => $issues
            ];
            return null;
        }

        return $rowData;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getUnmappedProducts()
    {
        return $this->unmappedProducts;
    }

    public function getInvalidData()
    {
        return $this->invalidData;
    }

    public function getHeaderIssues()
    {
        return $this->headerIssues;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function getSkippedForMissingDate()
    {
        return $this->skippedForMissingDate;
    }

    public function getSkippedForMissingDay()
    {
        return $this->skippedForMissingDay;
    }

    public function getSkippedForMissingResi()
    {
        return $this->skippedForMissingResi;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Set unmapped products
     */
    public function setUnmappedProducts(array $unmappedProducts)
    {
        $this->unmappedProducts = $unmappedProducts;
    }

    public function processImport()
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'duplicates' => 0,
            'skipped' => 0,
        ];

        // Jika ada produk yang belum di-mapping atau data yang tidak valid, return error
        if (! empty($this->unmappedProducts) || ! empty($this->invalidData)) {
            return [
                'success' => 0,
                'errors' => [
                    'unmappedProducts' => $this->unmappedProducts,
                    'invalidData' => $this->invalidData,
                ],
            ];
        }

        // Kelompokkan data berdasarkan no_order untuk menangani beberapa item dalam 1 order
        $groupedData = [];
        foreach ($this->data as $row) {
            $groupedData[$row['no_order']][] = $row;
        }

        \Log::info("Starting Blibli import with " . count($groupedData) . " orders");

        // Gunakan database transaction untuk memastikan semua operasi sukses atau tidak sama sekali
        DB::beginTransaction();

        try {
            foreach ($groupedData as $orderNumber => $items) {
                // PERBAIKAN: Cek apakah order sudah ada dengan validasi yang lebih ketat (Skip Global Scope)
                // Global scope 'mainCategory' dapat menghalangi deteksi duplikat jika order sudah ada dengan main_category_id berbeda
                $existingOrder = Order::withoutGlobalScope('mainCategory')
                    ->where('order_number', $orderNumber)
                    ->where('platform_id', $this->platform->id)
                    ->first();
                
                if ($existingOrder) {
                    \Log::info("Skipping duplicate order during import: $orderNumber");
                    $results['duplicates']++;
                    continue;
                }

                \Log::info("Processing order: $orderNumber with " . count($items) . " items");

                // Pastikan tanggal dalam format yang benar
                $tanggal = $items[0]['tanggal'];
                
                // Validasi tanggal tidak kosong
                if (empty($tanggal)) {
                    $results['skipped']++;
                    \Log::warning("Skipping order $orderNumber due to empty date");
                    continue;
                }
                
                if (is_string($tanggal)) {
                    // Jika tanggal masih dalam format string, konversi ke Carbon
                    try {
                        $tanggal = Carbon::parse($tanggal);
                    } catch (\Exception $e) {
                        \Log::error("Error parsing date for order $orderNumber: " . $e->getMessage());
                        $results['skipped']++;
                        \Log::warning("Skipping order $orderNumber due to invalid date format: $tanggal");
                        continue;
                    }
                }

                // Buat order baru
                $order = new Order([
                    'order_number' => $orderNumber,
                    'tanggal' => $tanggal,
                    'hari' => $items[0]['hari'] ?? null,
                    'status_hari' => $items[0]['status_hari'] ?? null,
                    'platform_id' => $this->platform->id,
                    'status' => 'completed',
                ]);
                $order->save();

                \Log::info("Created order with ID: {$order->id}");

                // PERBAIKAN: Buat order items untuk semua item dalam order
                foreach ($items as $item) {
                    // Buat nama produk lengkap dengan variasi jika ada
                    $productName = $item['nama_produk'];
                    $variation = $item['variasi'] ?? null;
                    $fullProductName = !empty($variation) ? "$productName - $variation" : $productName;
                    
                    \Log::info("Processing item: $fullProductName, qty: {$item['qty']}");
                    
                    // Ambil platform_product dengan pencarian yang lebih fleksibel
                    $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                        ->where(function ($query) use ($productName, $variation, $fullProductName) {
                            if (!empty($variation)) {
                                // Jika ada variant, cari dengan nama produk dan variant yang tepat
                                $query->where('platform_product_name', $productName)
                                    ->where('variant', $variation);
                            } else {
                                // Jika tidak ada variant, cari dengan nama produk saja dan variant null/kosong
                                $query->where('platform_product_name', $productName)
                                    ->where(function($q) {
                                        $q->whereNull('variant')
                                          ->orWhere('variant', '');
                                    });
                            }
                        })
                        ->first();

                    // Jika tidak ditemukan dengan pencarian exact, coba dengan pencarian yang lebih fleksibel
                    // TETAPI hanya jika tidak ada variant yang spesifik
                    if (!$platformProduct) {
                        if (!empty($variation)) {
                            // Jika ada variant spesifik, jangan gunakan flexible search
                            // Biarkan platformProduct tetap null agar bisa ditangani sebagai unmapped product
                            \Log::warning("Exact match not found for product: $productName, variant: $variation. Skipping flexible search for variant-specific products.");
                        } else {
                            // Hanya gunakan flexible search jika tidak ada variant
                            \Log::warning("Exact match not found for product: $productName (no variant), trying flexible search");
                            
                            $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                                ->where(function ($query) use ($productName, $fullProductName) {
                                    // Coba cari dengan nama lengkap
                                    $query->where('platform_product_name', $fullProductName)
                                        ->orWhere('platform_product_name', 'LIKE', '%'.$fullProductName.'%')
                                        // Juga coba cari dengan nama produk saja
                                        ->orWhere('platform_product_name', $productName)
                                        ->orWhere('platform_product_name', 'LIKE', '%'.$productName.'%')
                                        ->orWhere(DB::raw('LOWER(platform_product_name)'), 'LIKE', '%'.strtolower($productName).'%');
                                })
                                ->first();
                        }
                    }

                    if (! $platformProduct) {
                        throw new \Exception("Produk '$fullProductName' tidak ditemukan di database.");
                    }
                    
                    \Log::info("Found platform product ID: {$platformProduct->id}");

                    $orderItem = new OrderItem([
                        'order_id' => $order->id,
                        'platform_product_id' => $platformProduct->id,
                        'quantity' => $item['qty'],
                        'price_after_discount' => $item['harga_setelah_diskon'],
                        'variation' => $item['variasi'] ?? null,
                        'tracking_number' => $item['no_resi'] ?? null,
                    ]);
                    $orderItem->save();
                    
                    \Log::info("Created order item with ID: {$orderItem->id}");

                    // Kurangi stok sesuai mapping dan catat barang keluar
                    $this->reduceStock($platformProduct, $item['qty'], $orderItem, $order);
                }

                $results['success']++;
                \Log::info("Successfully processed order: $orderNumber");
            }

            DB::commit();
            \Log::info("Blibli import completed successfully. Results: " . json_encode($results));
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            \Log::error("Blibli import failed: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return $results;
        }
    }

    /**
     * Catat barang keluar
     *
     * @param  OrderItem  $orderItem
     * @param  WarehouseStock  $stock
     * @param  float  $quantity
     * @param  Order  $order
     * @return void
     */
    protected function recordBarangKeluar($orderItem, $stock, $quantity, $order)
    {
        try {
            $kodeBarangKeluar = BarangKeluar::generateKode();

            // Pastikan tanggal dalam format yang benar
            $tanggalKeluar = $order->tanggal;
            if (is_string($tanggalKeluar)) {
                $tanggalKeluar = Carbon::parse($tanggalKeluar)->format('Y-m-d');
            } elseif ($tanggalKeluar instanceof Carbon) {
                $tanggalKeluar = $tanggalKeluar->format('Y-m-d');
            }

            \Log::info("Creating BarangKeluar with tanggal_keluar: $tanggalKeluar for Order #{$order->order_number}");

            BarangKeluar::create([
                'kode_barang_keluar' => $kodeBarangKeluar,
                'order_item_id' => $orderItem->id,
                'warehouse_stock_id' => $stock->id,
                'qty' => $quantity,
                'tanggal_keluar' => $tanggalKeluar,
                'catatan' => "Penjualan online Blibli - Order #{$order->order_number}",
            ]);

            \Log::info("Recorded BarangKeluar: $kodeBarangKeluar for OrderItem ID: {$orderItem->id}, Stock ID: {$stock->id}, Qty: $quantity");
        } catch (\Exception $e) {
            \Log::error('Error recording BarangKeluar: ' . $e->getMessage());
            \Log::error('Order tanggal type: ' . gettype($order->tanggal) . ', value: ' . print_r($order->tanggal, true));
            throw $e;
        }
    }

    /**
     * Kurangi stok produk berdasarkan mapping dan catat sebagai barang keluar
     *
     * @param  PlatformProduct  $platformProduct
     * @param  int  $quantity
     * @param  OrderItem  $orderItem
     * @param  Order  $order
     * @return void
     */
    protected function reduceStock($platformProduct, $quantity, $orderItem, $order)
    {
        // Ambil semua mapping barang untuk platform product ini
        $mappings = MappingBarang::where('platform_product_id', $platformProduct->id)->get();

        if ($mappings->isEmpty()) {
            throw new \Exception("Tidak ada mapping ditemukan untuk produk: {$platformProduct->platform_product_name}");
        }

        foreach ($mappings as $mapping) {
            // Hitung jumlah yang perlu dikurangi dari stok
            $qtyToReduce = $quantity * $mapping->quantity;

            // Ambil stok yang tersedia berdasarkan FIFO + prioritas HGN
            $stocks = WarehouseStock::where('product_id', $mapping->product_id)
                ->where('qty', '>', 0)
                ->orderBy('created_at', 'asc') // Layer 1: FIFO berdasarkan tanggal penerimaan
                ->orderBy('tax_id', 'asc') // Layer 2: HGN (tax_id=3) dulu, baru LM (tax_id=4)
                ->get();

            $remainingQty = $qtyToReduce;

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $qtyToTake = min($remainingQty, $stock->qty);

                // Catat barang keluar
                $this->recordBarangKeluar($orderItem, $stock, $qtyToTake, $order);

                // Kurangi stok
                $stock->qty -= $qtyToTake;
                $stock->save();

                $remainingQty -= $qtyToTake;

                \Log::info("Reduced stock: Product ID {$mapping->product_id}, Stock ID {$stock->id}, Qty taken: $qtyToTake, Remaining stock: {$stock->qty}");
            }

            if ($remainingQty > 0) {
                $product = Product::find($mapping->product_id);
                $productName = $product ? $product->name : 'Unknown Product';
                throw new \Exception("Stok tidak cukup untuk produk: $productName. Dibutuhkan: $qtyToReduce, Tersedia: " . ($qtyToReduce - $remainingQty));
            }
        }
    }
} 
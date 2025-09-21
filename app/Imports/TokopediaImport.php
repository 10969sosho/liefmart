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

class TokopediaImport implements ToCollection, WithMultipleSheets
{
    protected $platform;

    protected $data = [];

    protected $unmappedProducts = [];

    protected $invalidData = [];

    protected $headerIssues = [];

    protected $headerRowIndex = 0;

    protected $columnMapping = [];

    protected $headers = [];

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
        // Dapatkan platform Tokopedia dari database
        $this->platform = Platform::where('name', 'tokopedia')->first();

        // Jika platform tidak ditemukan, throw exception
        if (! $this->platform) {
            throw new \Exception('Platform Tokopedia tidak ditemukan di database.');
        }
    }

    public function sheets(): array
    {
        return [
            'Laporan Penjualan' => $this,
        ];
    }

    public function collection(Collection $rows)
    {
        // Set total baris awal
        $this->totalRows = count($rows);

        // Debug: Log semua baris
        \Log::info('Total rows in Excel:', ['count' => $this->totalRows]);

        // Cari indeks baris header
        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === false) {
            $this->headerIssues[] = 'Format header tidak ditemukan. Pastikan file memiliki header yang sesuai.';
            return;
        }

        // Debug: Log header row index
        \Log::info('Header row index:', ['index' => $headerRowIndex]);

        $this->headerRowIndex = $headerRowIndex;

        // Ambil header dari baris yang ditemukan
        $headers = $rows[$headerRowIndex];
        
        // Store headers for later reference
        $this->headers = $headers->toArray();

        // Debug: Log headers
        \Log::info('Headers:', $this->headers);

        // Buat mapping kolom
        $this->mapColumns($headers);

        // Validasi mapping kolom
        if (! $this->validateColumnMapping()) {
            return;
        }

        // Tracking nomor pesanan untuk mencegah duplikasi dalam satu file
        $orderNumbersInFile = [];

        // Proses data dimulai dari baris setelah header
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Debug: Log setiap baris
            \Log::info("Row $i:", $row->toArray());

            // Pastikan baris tidak kosong
            if ($this->isEmptyRow($row)) {
                \Log::info("Empty row skipped: $i");

                continue;
            }

            // Ambil data sesuai mapping
            $processedRow = $this->processRow($row);

            // Debug: Log processed row
            \Log::info("Processed Row $i:", $processedRow);

            // Validasi data
            $validationErrors = $this->validateRow($processedRow);
            if (! empty($validationErrors)) {
                $this->invalidData[] = 'Baris #'.($i - $headerRowIndex).': '.implode(', ', $validationErrors);
                \Log::warning("Validation errors for row $i:", $validationErrors);

                continue;
            }

            // Skip jika tanggal, no_resi, atau hari tidak ada meskipun header ada
            if ($this->shouldSkipRow($processedRow)) {
                $this->skippedOrders++;
                \Log::info("Row skipped due to missing required field: $i");

                continue;
            }

            // Cek apakah produk sudah di-mapping
            $this->checkProductMapping($processedRow['nama_barang']);

            // Jika data yang sama dalam satu file, gunakan data yang pertama
            if (! isset($orderNumbersInFile[$processedRow['no_order']])) {
                $orderNumbersInFile[$processedRow['no_order']] = 1;
            }

            // Tambahkan ke data yang valid
            $this->data[] = $processedRow;
        }

        // Debug: Log final data dan unmapped products
        \Log::info('Final Data:', $this->data);
        \Log::info('Unmapped Products:', $this->unmappedProducts);
    }

    /**
     * Memeriksa apakah baris harus dilewati karena field yang diperlukan tidak ada
     *
     * @param  array  $row
     * @return bool
     */
    protected function shouldSkipRow($row)
    {
        // Periksa jika header tanggal, no_resi, atau hari ada tetapi datanya kosong
        $shouldSkip = false;

        if ($this->columnMapping['tanggal'] !== null && empty($row['tanggal'])) {
            $this->skippedForMissingDate++;
            $shouldSkip = true;
        }

        if ($this->columnMapping['no_resi'] !== null && empty($row['no_resi'])) {
            $this->skippedForMissingResi++;
            $shouldSkip = true;
        }

        if ($this->columnMapping['hari'] !== null && empty($row['hari'])) {
            $this->skippedForMissingDay++;
            $shouldSkip = true;
        }

        return $shouldSkip;
    }

    /**
     * Cari baris header dalam data
     *
     * @return int|bool
     */
    protected function findHeaderRow(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Periksa apakah baris mengandung header yang diharapkan
            if ($this->isHeaderRow($row)) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Periksa apakah baris adalah baris header
     *
     * @param  Collection  $row
     * @return bool
     */
    protected function isHeaderRow($row)
    {
        // Only check for TANGGAL column
        $expectedColumn = 'TANGGAL';

        foreach ($row as $cell) {
            if (is_string($cell)) {
                // Remove whitespace and case-insensitive
                $normalizedCell = trim(strtoupper($cell));
                $normalizedColumn = trim(strtoupper($expectedColumn));

                // More flexible detection
                if ($normalizedCell === $normalizedColumn ||
                    strpos($normalizedCell, $normalizedColumn) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mapping kolom dari header
     *
     * @param  Collection  $headers
     * @return void
     */
    protected function mapColumns($headers)
    {
        \Log::info('Original Headers:', $headers->toArray());

        $this->columnMapping = [
            'no_order' => $this->findColumnIndex($headers, ['NOMOR PESANAN', 'NO PESANAN', 'ORDER ID']),
            'tanggal' => $this->findColumnIndex($headers, ['TANGGAL', 'TGL', 'DATE']),
            'nama_barang' => $this->findColumnIndex($headers, ['NAMA PRODUK', 'PRODUK', 'NAMA BARANG']),
            'qty' => $this->findColumnIndex($headers, ['QTY', 'QUANTITY', 'JUMLAH']),
            'harga_setelah_diskon' => $this->findColumnIndex($headers, ['HARGA SETELAH DISKON', 'HARGA']),
            'no_resi' => $this->findColumnIndex($headers, ['NO RESI', 'RESI', 'NOMOR RESI']),
            'hari' => $this->findColumnIndex($headers, ['HARI', 'DAY']),
            'status_hari' => $this->findColumnIndex($headers, ['STATUS HARI', 'STATUS']),
        ];

        \Log::info('Column Mapping:', $this->columnMapping);
    }

    /**
     * Temukan indeks kolom berdasarkan kemungkinan nama kolom
     *
     * @param  Collection  $headers
     * @param  array  $possibleNames
     * @return int|null
     */
    protected function findColumnIndex($headers, $possibleNames)
    {
        \Log::info('Searching for possible names:', $possibleNames);

        foreach ($headers as $index => $header) {
            if (! is_string($header)) {
                continue;
            }

            $upperHeader = strtoupper(trim($header));

            foreach ($possibleNames as $name) {
                $upperName = strtoupper(trim($name));

                \Log::info("Comparing: Header '$upperHeader' with Name '$upperName'");

                if ($upperHeader === $upperName || strpos($upperHeader, $upperName) !== false) {
                    \Log::info("Match found for $name at index $index");

                    return $index;
                }
            }
        }

        \Log::warning('No matching column found for names: '.implode(', ', $possibleNames));

        return null;
    }

    /**
     * Validasi mapping kolom yang dibutuhkan
     *
     * @return bool
     */
    protected function validateColumnMapping()
    {
        // Only check for tanggal column
        if ($this->columnMapping['tanggal'] === null) {
            $this->headerIssues[] = 'Kolom TANGGAL tidak ditemukan';
            return false;
        }

        return true;
    }

    /**
     * Proses baris data sesuai mapping kolom
     *
     * @param  Collection  $row
     * @return array
     */
    protected function processRow($row)
    {
        $result = [];
        
        // Ensure row is converted to array for processing
        $rowData = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : $row;
        \Log::info("Processing row data:", array_slice($rowData, 0, min(40, count($rowData))));

        foreach ($this->columnMapping as $key => $index) {
            // Skip if index is not found
            if ($index === null) {
                $result[$key] = null;
                continue;
            }
            
            // Get the raw value from mapped column
            $rawValue = isset($rowData[$index]) ? $rowData[$index] : null;
            
            // Use our calculateValue method to properly process the value
            $calculatedValue = $this->calculateValue($key, $rawValue, $rowData);
            $result[$key] = $calculatedValue;
            
            // Enhanced logging for important fields
            if ($key === 'tanggal') {
                \Log::info("TANGGAL processed: original value = " . (is_string($rawValue) ? $rawValue : 'non-string') . 
                           ", calculated value = " . (is_string($calculatedValue) ? $calculatedValue : 'non-string'));
            }
            elseif ($key === 'harga_setelah_diskon') {
                $originalValueLog = is_string($rawValue) ? $rawValue : (is_numeric($rawValue) ? strval($rawValue) : 'non-string');
                $calculatedValueLog = is_string($calculatedValue) ? $calculatedValue : (is_numeric($calculatedValue) ? strval($calculatedValue) : 'non-string');
                
                \Log::info("HARGA processed: original value = $originalValueLog, calculated value = $calculatedValueLog");
            }
        }

        \Log::info("Processed row result:", $result);
        return $result;
    }

    /**
     * Periksa apakah produk sudah dimapping di database
     *
     * @param  string  $productName
     * @return void
     */
    protected function checkProductMapping($productName)
    {
        if (empty($productName)) {
            return;
        }

        // Debug: Log nama produk yang sedang diperiksa
        \Log::info("Checking product mapping for: $productName");

        // Cari produk platform dengan nama yang mirip
        $platformProduct = PlatformProduct::where('platform_product_name', 'like', "%$productName%")
            ->first();

        if ($platformProduct) {
            // Debug: Log detail platform product
            \Log::info('Platform Product found', [
                'id' => $platformProduct->id,
                'name' => $platformProduct->platform_product_name,
            ]);

            // Periksa mapping
            $mappingExists = MappingBarang::where('platform_product_id', $platformProduct->id)
                ->exists();

            // Debug: Log status mapping
            \Log::info('Mapping exists: '.($mappingExists ? 'Yes' : 'No'));

            // Jika tidak ada mapping, tambahkan ke unmapped
            if (! $mappingExists) {
                if (! in_array($productName, $this->unmappedProducts)) {
                    $this->unmappedProducts[] = $productName;
                }
            }
        } else {
            // Debug: Tidak menemukan platform product
            \Log::warning("No platform product found for: $productName");

            // Tambahkan ke unmapped jika tidak ditemukan
            if (! in_array($productName, $this->unmappedProducts)) {
                $this->unmappedProducts[] = $productName;
            }
        }
    }

    /**
     * Validasi baris data
     *
     * @param  array  $row
     * @return array
     */
    protected function validateRow($row)
    {
        $errors = [];

        if (empty($row['no_order'])) {
            $errors[] = 'Nomor Order tidak boleh kosong';
        }
        if (empty($row['nama_barang'])) {
            $errors[] = 'Nama Barang tidak boleh kosong';
        }
        if (empty($row['qty']) || ! is_numeric($row['qty']) || $row['qty'] <= 0) {
            $errors[] = 'Quantity harus berupa angka positif';
        }
        if (empty($row['harga_setelah_diskon']) || ! is_numeric($row['harga_setelah_diskon']) || $row['harga_setelah_diskon'] < 0) {
            $errors[] = 'Harga harus berupa angka tidak negatif';
        }

        if (! empty($errors)) {
            \Log::info('Validation Errors:', $errors);
            $this->invalidData[] = 'Baris: '.json_encode($row).' -> '.implode(', ', $errors);
        }

        return $errors;
    }

    /**
     * Periksa apakah baris kosong
     *
     * @param  Collection  $row
     * @return bool
     */
    protected function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (! empty($cell)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Proses import data ke database
     *
     * @return array
     */
    public function processImport()
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'duplicates' => 0, // Tambahkan counter untuk duplikasi
            'skipped' => 0,    // Tambahkan counter untuk yang dilewati
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

        // Gunakan database transaction untuk memastikan semua operasi sukses atau tidak sama sekali
        DB::beginTransaction();

        try {
            // Proses import data ke database
            foreach ($groupedData as $orderNumber => $orderItems) {
                try {
                    // Ambil item pertama untuk informasi order
                    $firstItem = $orderItems[0];

                    // Parse tanggal
                    $tanggal = null;
                    if (!empty($firstItem['tanggal'])) {
                        try {
                            $tanggal = $this->parseDate($firstItem['tanggal']);
                            
                            if (empty($tanggal)) {
                                $results['skipped']++;
                                \Log::warning("Skipping order $orderNumber due to invalid date format: " . ($firstItem['tanggal'] ?? 'null'));
                                continue;
                            }
                        } catch (\Exception $e) {
                            // Jika format tanggal tidak valid, lewati order ini
                            $results['skipped']++;
                            \Log::warning("Exception parsing date for order $orderNumber: " . $e->getMessage());
                            continue;
                        }
                    } else {
                        // Jika tanggal kosong, skip order ini
                        $results['skipped']++;
                        \Log::warning("Skipping order $orderNumber due to empty date");
                        continue;
                    }

                    // Tentukan hari dari tanggal jika hari tidak tersedia
                    $hari = ! empty($firstItem['hari']) ? $firstItem['hari'] : null;

                    // Ambil status_hari dari data
                    $statusHari = ! empty($firstItem['status_hari']) ? $firstItem['status_hari'] : null;

                    // Cek apakah order sudah ada di database
                    $existingOrder = Order::where('platform_id', $this->platform->id)
                        ->where('order_number', $orderNumber)
                        ->first();

                    if ($existingOrder) {
                        // Order sudah ada, increment counter dan skip
                        $results['duplicates']++;
                        \Log::info("Duplicate order skipped: $orderNumber");

                        continue;
                    }

                    // Buat order baru
                    $order = new Order([
                        'platform_id' => $this->platform->id,
                        'order_number' => $orderNumber,
                        'tanggal' => $tanggal,
                        'hari' => $hari,
                        'status_hari' => $statusHari,
                        'status' => 'imported',
                    ]);
                    $order->save();

                    // Proses setiap item dalam order
                    foreach ($orderItems as $item) {
                        // Ambil platform_product dengan pencarian yang lebih fleksibel
                        $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                            ->where(function ($query) use ($item) {
                                $productName = $item['nama_barang'];
                                $query->where('platform_product_name', $productName)
                                    ->orWhere('platform_product_name', 'LIKE', '%'.$productName.'%')
                                    ->orWhere(DB::raw('LOWER(platform_product_name)'), 'LIKE', '%'.strtolower($productName).'%');
                            })
                            ->first();

                        if (! $platformProduct) {
                            throw new \Exception("Produk '$fullProductName' tidak ditemukan di database.");
                        }

                        // Verifikasi stok terlebih dahulu
                        $checkStockResult = $this->checkStock($platformProduct, $item['qty']);
                        if (! $checkStockResult['success']) {
                            throw new \Exception("Stok tidak cukup untuk produk {$checkStockResult['product_name']}");
                        }

                        // Buat order item
                        $orderItem = new OrderItem([
                            'order_id' => $order->id,
                            'platform_product_id' => $platformProduct->id,
                            'quantity' => $item['qty'],
                            'price_after_discount' => $item['harga_setelah_diskon'],
                            'tracking_number' => $item['no_resi'] ?? null,
                        ]);
                        $orderItem->save();

                        // Kurangi stok sesuai mapping dan catat barang keluar
                        $this->reduceStock($platformProduct, $item['qty'], $orderItem);
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Error memproses order $orderNumber: ".$e->getMessage();
                    \Log::error("Import error for order $orderNumber: ".$e->getMessage());
                    throw $e;
                }
            }

            // Jika tidak ada error, commit transaksi
            DB::commit();
        } catch (\Exception $e) {
            // Jika ada error, rollback semua perubahan
            DB::rollBack();
            $results['errors'][] = 'Error dalam transaksi: '.$e->getMessage();
            \Log::error('Transaction error in processImport: '.$e->getMessage());
        }

        // Tambahkan informasi tentang order yang dilewati
        $results['skipped'] += $this->skippedOrders;
        $results['duplicates'] += $this->duplicateOrders;

        return $results;
    }

    /**
     * Memeriksa apakah stok mencukupi tanpa menguranginya
     *
     * @param  PlatformProduct  $platformProduct
     * @param  int  $quantity
     * @return array
     */
    protected function checkStock($platformProduct, $quantity)
    {
        // Ambil semua mapping barang untuk platform product ini
        $mappings = MappingBarang::where('platform_product_id', $platformProduct->id)->get();

        foreach ($mappings as $mapping) {
            // Hitung jumlah yang perlu dikurangi dari stok
            $qtyToReduce = $quantity * $mapping->quantity;

            // Ambil total stok yang tersedia
            $availableStock = WarehouseStock::where('product_id', $mapping->product_id)
                ->where('qty', '>', 0)
                ->sum('qty');

            // Jika stok tidak cukup, return error
            if ($availableStock < $qtyToReduce) {
                $product = Product::find($mapping->product_id);
                $productName = $product ? $product->name : 'Unknown Product';

                return [
                    'success' => false,
                    'product_id' => $mapping->product_id,
                    'product_name' => $productName,
                    'required' => $qtyToReduce,
                    'available' => $availableStock,
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Catat barang keluar
     *
     * @param  OrderItem  $orderItem
     * @param  WarehouseStock  $stock
     * @param  float  $quantity
     * @return void
     */
    protected function recordBarangKeluar($orderItem, $stock, $quantity)
    {
        try {
            $kodeBarangKeluar = BarangKeluar::generateKode();

            // Log warehouse_stock_id for debugging
            \Log::info("Creating BarangKeluar with warehouse_stock_id: {$stock->id} and order_item_id: {$orderItem->id}");

            BarangKeluar::create([
                'kode_barang_keluar' => $kodeBarangKeluar,
                'order_item_id' => $orderItem->id,
                'warehouse_stock_id' => $stock->id,
                'qty' => $quantity,
                'tanggal_keluar' => $orderItem->order->tanggal,
                'catatan' => "Penjualan online Tokopedia - Order #{$orderItem->order->order_number}",
            ]);

            \Log::info("Recorded BarangKeluar: $kodeBarangKeluar for OrderItem ID: {$orderItem->id}, Stock ID: {$stock->id}, Qty: $quantity");
        } catch (\Exception $e) {
            \Log::error('Error recording BarangKeluar: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Kurangi stok produk berdasarkan mapping dan catat sebagai barang keluar
     * 
     * Update: Sekarang menyimpan warehouse_stock_id ke order_item untuk konsistensi dengan input manual
     *
     * @param  PlatformProduct  $platformProduct
     * @param  int  $quantity
     * @param  OrderItem  $orderItem
     * @return void
     */
    protected function reduceStock($platformProduct, $quantity, $orderItem)
    {
        try {
            // Ambil semua mapping barang untuk platform product ini
            $mappings = MappingBarang::where('platform_product_id', $platformProduct->id)->get();
            \Log::info("Reducing stock for platform product: {$platformProduct->platform_product_name}, Quantity: {$quantity}");
            \Log::info('Found mappings: '.$mappings->count());

            foreach ($mappings as $mapping) {
                // Hitung jumlah yang perlu dikurangi dari stok
                $qtyToReduce = $quantity * $mapping->quantity;
                \Log::info("Processing mapping for product ID: {$mapping->product_id}, Qty to reduce: {$qtyToReduce}");

                // Ambil stok produk dari warehouse berdasarkan tanggal ED (prioritaskan yang lebih awal)
                $stocks = WarehouseStock::where('product_id', $mapping->product_id)
                    ->where('qty', '>', 0)
                    ->orderBy('expired_date') // FIFO berdasarkan tanggal kadaluarsa
                    ->get();
                \Log::info('Found stock records: '.$stocks->count());

                $remainingQty = $qtyToReduce;
                $isFirstStock = true;  // Flag untuk menandai stok pertama

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    // Hitung quantity yang akan dikurangi dari stok ini
                    $qtyToTake = min($remainingQty, $stock->qty);
                    \Log::info("Reducing from stock ID: {$stock->id}, Available: {$stock->qty}, Taking: {$qtyToTake}");

                    // Set warehouse_stock_id pada order item jika ini adalah stok pertama yang digunakan
                    if ($isFirstStock) {
                        $orderItem->warehouse_stock_id = $stock->id;
                        $orderItem->save();
                        $isFirstStock = false;
                        \Log::info("Set warehouse_stock_id: {$stock->id} for OrderItem ID: {$orderItem->id}");
                    }

                    // Catat barang keluar sebelum kurangi stok
                    $this->recordBarangKeluar($orderItem, $stock, $qtyToTake);

                    // Kurangi stok
                    $stock->qty -= $qtyToTake;
                    $stock->save();

                    // Update sisa quantity yang perlu dikurangi
                    $remainingQty -= $qtyToTake;
                }

                // Jika masih ada sisa quantity yang perlu dikurangi, stok tidak cukup
                if ($remainingQty > 0) {
                    \Log::warning("Insufficient stock for product ID: {$mapping->product_id}, Missing qty: {$remainingQty}");
                    throw new \Exception("Stok tidak cukup untuk produk {$mapping->product->name}");
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in reduceStock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Dapatkan data yang sudah diproses
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Dapatkan daftar produk yang belum dimapping
     *
     * @return array
     */
    public function getUnmappedProducts()
    {
        return $this->unmappedProducts;
    }

    /**
     * Dapatkan daftar data yang tidak valid
     *
     * @return array
     */
    public function getInvalidData()
    {
        return $this->invalidData;
    }

    /**
     * Dapatkan masalah terkait header
     *
     * @return array
     */
    public function getHeaderIssues()
    {
        return $this->headerIssues;
    }

    /**
     * Dapatkan total baris dalam file Excel
     *
     * @return int
     */
    public function getTotalRows()
    {
        return $this->totalRows;
    }

    /**
     * Dapatkan jumlah baris yang dilewati karena tanggal kosong
     *
     * @return int
     */
    public function getSkippedForMissingDate()
    {
        return $this->skippedForMissingDate;
    }

    /**
     * Dapatkan jumlah baris yang dilewati karena hari kosong
     *
     * @return int
     */
    public function getSkippedForMissingDay()
    {
        return $this->skippedForMissingDay;
    }

    /**
     * Dapatkan jumlah baris yang dilewati karena no resi kosong
     *
     * @return int
     */
    public function getSkippedForMissingResi()
    {
        return $this->skippedForMissingResi;
    }

    /**
     * Set data untuk diimport
     */
    public function setData(array $data)
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

    /**
     * Parse tanggal dari berbagai format ke format Y-m-d
     * 
     * @param string $dateStr
     * @return string|null
     */
    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            // Log the original date string
            \Log::info("Parsing date: " . $dateStr);
            
            // Clean up the date string
            $dateStr = trim($dateStr);
            
            // Try to parse with various formats - IMPORTANT: order matters!
            // For Tokopedia, prioritize d/m/Y format (day/month/year)
            $formats = ['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y', 'Y-m-d', 'd-m-Y'];
            
            foreach ($formats as $format) {
                try {
                    $dateObj = Carbon::createFromFormat($format, $dateStr);
                    if ($dateObj) {
                        $result = $dateObj->format('Y-m-d');
                        \Log::info("Successfully parsed date: $dateStr to $result using format $format");
                        return $result;
                    }
                } catch (\Exception $e) {
                    // Just log the error and continue to next format
                    \Log::debug("Failed to parse date with format $format: " . $e->getMessage());
                    continue;
                }
            }
            
            // Last resort - try Carbon's parse method with additional error handling
            try {
                $carbonDate = Carbon::parse($dateStr);
                $result = $carbonDate->format('Y-m-d');
                \Log::info("Successfully parsed date using Carbon::parse: $dateStr to $result");
                return $result;
            } catch (\Exception $e) {
                \Log::error("Failed to parse date with Carbon::parse: " . $e->getMessage());
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to parse date: $dateStr - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract and calculate a value for a specific field type from the row data
     * Used primarily for specific value processing while respecting header mappings
     * 
     * @param string $fieldType Type of field (tanggal, no_order, etc)
     * @param mixed $rawValue The raw value from the Excel cell
     * @param array $rowData Complete row data array
     * @return mixed Processed value
     */
    protected function calculateValue($fieldType, $rawValue, $rowData)
    {
        \Log::info("Calculating value for $fieldType, raw value: " . (is_string($rawValue) ? $rawValue : 'non-string'));
        
        // Special handling for tanggal field
        if ($fieldType === 'tanggal') {
            if (is_string($rawValue) && !empty($rawValue)) {
                // If it has time component, extract just the date part
                if (strpos($rawValue, ' ') !== false) {
                    $parts = explode(' ', $rawValue);
                    $dateOnly = $parts[0];
                    \Log::info("Using direct date value (split): $dateOnly");
                    return $dateOnly;
                }
                \Log::info("Using direct date value: $rawValue");
                return $rawValue;
            }
            
            \Log::warning("Empty or invalid date value");
            return null;
        }
        
        // Special handling for harga_setelah_diskon field
        if ($fieldType === 'harga_setelah_diskon') {
            // Clean up price value if it's a string
            if (is_string($rawValue)) {
                $cleanPrice = preg_replace('/[^\d.]/', '', $rawValue);
                if (!empty($cleanPrice)) {
                    \Log::info("Using cleaned price value: $cleanPrice (from $rawValue)");
                    return $cleanPrice;
                }
            } else if (is_numeric($rawValue)) {
                \Log::info("Using direct numeric price value: $rawValue");
                return $rawValue;
            }
            
            \Log::warning("Empty or invalid price value");
            return 0;
        }
        
        // For no_order field
        if ($fieldType === 'no_order') {
            if (is_string($rawValue) && !empty($rawValue)) {
                // Clean up any quotes or whitespace
                $cleanValue = trim(str_replace('"', '', $rawValue));
                \Log::info("Using cleaned order number: $cleanValue");
                return $cleanValue;
            }
            return $rawValue;
        }

        // For variasi field
        if ($fieldType === 'variasi') {
            if (is_string($rawValue)) {
                return trim($rawValue);
            }
            return $rawValue;
        }
        
        // For no_resi field
        if ($fieldType === 'no_resi') {
            if (is_string($rawValue)) {
                return trim($rawValue);
            }
            return $rawValue;
        }
        
        // For status_hari field - support multiple values separated by comma
        if ($fieldType === 'status_hari') {
            if (is_string($rawValue) && !empty($rawValue)) {
                $value = trim($rawValue);
                // If value contains comma, it's already in the correct format for multiple values
                // If not, it's a single value
                if (strpos($value, ',') === false) {
                    // Single value, keep as is
                    return $value;
                } else {
                    // Multiple values separated by comma, keep as is
                    return $value;
                }
            }
            return $rawValue;
        }
        
        // Default: return the raw value
        return $rawValue;
    }
    
    /**
     * Get header value for a specific column index
     * 
     * @param int $columnIndex Index of the column
     * @return string|null Header value
     */
    protected function getHeaderValue($columnIndex)
    {
        if ($this->headerRowIndex === false || empty($this->headers)) {
            return null;
        }
        
        return $this->headers[$columnIndex] ?? null;
    }
}

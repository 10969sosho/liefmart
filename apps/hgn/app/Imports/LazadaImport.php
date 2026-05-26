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

class LazadaImport implements ToCollection, WithMultipleSheets
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
     * 
     * @param int|null $platformId ID platform (jika null, akan menggunakan platform_id dari session atau default)
     */
    public function __construct($platformId = null)
    {
        // Jika platform_id diberikan, gunakan itu
        if ($platformId !== null) {
            $this->platform = Platform::find($platformId);
        } else {
            // Coba ambil dari session jika ada
            $platformId = session('platform_id');
            if ($platformId) {
                $this->platform = Platform::find($platformId);
            } else {
                // Fallback: cari berdasarkan nama (untuk backward compatibility)
                $this->platform = Platform::where('name', 'lazada')->first();
            }
        }

        // Jika platform tidak ditemukan, throw exception
        if (!$this->platform) {
            throw new \Exception('Platform tidak ditemukan di database.');
        }
    }

    /**
     * Get platform property for external access
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    public function sheets(): array
    {
        return [
            0 => $this, // Gunakan sheet pertama (indeks 0)
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
        \Log::info('Headers:', $headers->toArray());

        // Buat mapping kolom
        $this->mapColumns($headers);

        // Validasi mapping kolom
        if (!$this->validateColumnMapping()) {
            return;
        }

        // Tracking nomor pesanan untuk mencegah duplikasi dalam satu file
        $orderNumbersInFile = [];
        
        // Log the column mapping for debugging
        \Log::info('Final Column Mapping:', $this->columnMapping);

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
            if (!empty($validationErrors)) {
                $this->invalidData[] = 'Baris #' . ($i - $headerRowIndex) . ': ' . implode(', ', $validationErrors);
                \Log::warning("Validation errors for row $i:", $validationErrors);
                continue;
            }

            // Skip jika tanggal, no_resi, atau hari tidak ada meskipun header ada
            if ($this->shouldSkipRow($processedRow)) {
                $this->skippedOrders++;
                \Log::info("Row skipped due to missing required field: $i");
                continue;
            }

            // Variant HANYA diambil dari kolom variant, bukan dari pemotongan nama produk
            $variation = isset($processedRow['variasi']) && trim($processedRow['variasi']) !== '' ? trim($processedRow['variasi']) : '';
            
            // Debug: Log variant yang diambil
            \Log::info("Row variant check - nama_barang: '{$processedRow['nama_barang']}', variasi from Excel: '{$variation}'");
            
            // Cek apakah produk sudah di-mapping
            $this->checkProductMapping($processedRow['nama_barang'], $variation);

            // Cari platform product ID untuk validasi stok di preview
            // PENTING: Variant diambil HANYA dari kolom variant, bukan dari parsing nama produk
            // Handle variant kosong: cari dengan empty string atau NULL
            if (empty($variation)) {
                $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                    ->where('platform_product_name', $processedRow['nama_barang'])
                    ->where(function($query) {
                        $query->where('variant', '')
                              ->orWhereNull('variant');
                    })
                    ->first();
            } else {
                // Jika variant ada, cari dengan variant yang spesifik
                $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                    ->where('platform_product_name', $processedRow['nama_barang'])
                    ->where('variant', $variation)
                    ->first();
            }
            
            if ($platformProduct) {
                $processedRow['platform_product_id'] = $platformProduct->id;
                \Log::info("Platform product found - ID: {$platformProduct->id}, variant: '{$platformProduct->variant}'");
            } else {
                \Log::warning("Platform product NOT found - nama_barang: '{$processedRow['nama_barang']}', variant: '{$variation}'");
            }

            // Jika data yang sama dalam satu file, gunakan data yang pertama
            if (!isset($orderNumbersInFile[$processedRow['no_order']])) {
                $orderNumbersInFile[$processedRow['no_order']] = 1;
            } else {
                $this->duplicateOrders++;
                \Log::info("Duplicate order in file: {$processedRow['no_order']}");
            }

            // Tambahkan ke data yang valid
            $this->data[] = $processedRow;
        }

        // Debug: Log final data dan unmapped products
        \Log::info('Final Data Count:', ['count' => count($this->data)]);
        \Log::info('Unmapped Products Count:', ['count' => count($this->unmappedProducts)]);
    }

    /**
     * Memeriksa apakah baris harus dilewati karena field yang diperlukan tidak ada
     */
    protected function shouldSkipRow($row)
    {
        // Periksa jika header tanggal atau no_resi ada tetapi datanya kosong
        $shouldSkip = false;

        if (isset($this->columnMapping['tanggal']) && $this->columnMapping['tanggal'] !== null && empty($row['tanggal'])) {
            $this->skippedForMissingDate++;
            $shouldSkip = true;
        }

        if (isset($this->columnMapping['no_resi']) && $this->columnMapping['no_resi'] !== null && isset($row['no_resi']) && empty($row['no_resi'])) {
            $this->skippedForMissingResi++;
            $shouldSkip = true;
        }

        // Catat saja jika hari kosong tapi jangan lewati baris
        if (isset($this->columnMapping['hari']) && $this->columnMapping['hari'] !== null && empty($row['hari'])) {
            $this->skippedForMissingDay++;
        }

        return $shouldSkip;
    }

    /**
     * Cari baris header berdasarkan pola yang diketahui
     */
    protected function findHeaderRow($rows)
    {
        $requiredHeaders = ['TANGGAL', 'NOMOR PESANAN', 'QTY', 'HARGA SETELAH DISKON', 'PRODUK'];
        
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $foundHeaders = 0;
            
            foreach ($requiredHeaders as $requiredHeader) {
                foreach ($rowArray as $cell) {
                    if (stripos($cell, $requiredHeader) !== false) {
                        $foundHeaders++;
                        break;
                    }
                }
            }
            
            // Jika minimal 4 dari 5 header ditemukan, anggap ini header row
            if ($foundHeaders >= 4) {
                return $index;
            }
        }
        
        return false;
    }

    /**
     * Mapping kolom berdasarkan header yang ditemukan
     */
    protected function mapColumns($headers)
    {
        $this->columnMapping = [
            'tanggal' => null,
            'hari' => null,
            'status_hari' => null,
            'no_order' => null,
            'qty' => null,
            'harga_setelah_diskon' => null,
            'nama_barang' => null,
            'variasi' => null,
            'no_resi' => null,
        ];

        foreach ($headers as $index => $header) {
            $header = trim($header);
            
            // Mapping berdasarkan pola yang fleksibel
            if (stripos($header, 'TANGGAL') !== false) {
                $this->columnMapping['tanggal'] = $index;
            } elseif (stripos($header, 'HARI') !== false && stripos($header, 'STATUS') === false) {
                $this->columnMapping['hari'] = $index;
            } elseif (stripos($header, 'STATUS HARI') !== false) {
                $this->columnMapping['status_hari'] = $index;
            } elseif (stripos($header, 'NOMOR PESANAN') !== false) {
                $this->columnMapping['no_order'] = $index;
            } elseif (stripos($header, 'QTY') !== false) {
                $this->columnMapping['qty'] = $index;
            } elseif (stripos($header, 'HARGA SETELAH DISKON') !== false) {
                $this->columnMapping['harga_setelah_diskon'] = $index;
            } elseif (stripos($header, 'PRODUK') !== false) {
                $this->columnMapping['nama_barang'] = $index;
            } elseif (stripos($header, 'VARIAN') !== false || stripos($header, 'VARIASI') !== false || stripos($header, 'VARIANT') !== false) {
                // Deteksi kolom variant dengan berbagai nama: VARIAN, VARIASI, atau VARIANT
                $this->columnMapping['variasi'] = $index;
            } elseif (stripos($header, 'NOMOR RESI') !== false) {
                $this->columnMapping['no_resi'] = $index;
            }
        }
    }

    /**
     * Validasi mapping kolom
     */
    protected function validateColumnMapping()
    {
        $requiredColumns = ['tanggal', 'no_order', 'qty', 'harga_setelah_diskon', 'nama_barang'];
        
        foreach ($requiredColumns as $column) {
            if ($this->columnMapping[$column] === null) {
                $this->headerIssues[] = "Kolom {$column} tidak ditemukan dalam header.";
                return false;
            }
        }
        
        return true;
    }

    /**
     * Proses baris data sesuai mapping
     * PENTING: Variant diambil HANYA dari kolom variant, bukan dari pemotongan nama produk
     */
    protected function processRow($row)
    {
        $processedRow = [];
        
        foreach ($this->columnMapping as $field => $columnIndex) {
            // Cek apakah kolom ada di mapping dan index ada di row
            if ($columnIndex !== null && array_key_exists($columnIndex, $row->toArray())) {
                $cellValue = $row[$columnIndex];
                
                // For nama_barang, preserve exactly as in Excel (no trim, no normalization)
                // For variasi, ambil HANYA dari kolom variant, bukan dari parsing nama produk
                if ($field === 'nama_barang') {
                    $processedRow[$field] = $cellValue;
                } elseif ($field === 'variasi') {
                    // Variant HANYA dari kolom variant Excel, tidak ada parsing dari nama produk
                    // Handle cell kosong: null, empty string, atau whitespace semua menjadi empty string
                    if (is_null($cellValue) || $cellValue === '' || trim((string)$cellValue) === '') {
                        $processedRow[$field] = '';
                    } else {
                        $processedRow[$field] = trim((string)$cellValue);
                    }
                } else {
                    $processedRow[$field] = trim($row[$columnIndex]);
                }
            } else {
                // Jika kolom tidak ada di mapping atau tidak ada di row, set null atau empty string
                if ($field === 'variasi') {
                    $processedRow[$field] = ''; // Variant kosong jika kolom tidak ada
                } else {
                    $processedRow[$field] = null;
                }
            }
        }
        
        return $processedRow;
    }

    /**
     * Validasi data baris
     */
    protected function validateRow($row)
    {
        $errors = [];
        
        if (empty($row['no_order'])) {
            $errors[] = 'Nomor pesanan kosong';
        }
        
        if (empty($row['nama_barang'])) {
            $errors[] = 'Nama barang kosong';
        }
        
        if (empty($row['qty']) || !is_numeric($row['qty'])) {
            $errors[] = 'Quantity tidak valid';
        }
        
        if (empty($row['harga_setelah_diskon']) || !is_numeric($row['harga_setelah_diskon'])) {
            $errors[] = 'Harga tidak valid';
        }
        
        return $errors;
    }

    /**
     * Cek apakah produk sudah di-mapping
     * PENTING: Variant diambil HANYA dari kolom variant, bukan dari pemotongan nama produk
     */
    protected function checkProductMapping($productName, $variation = '')
    {
        // Normalisasi variant: pastikan empty string jika null atau kosong
        $variation = $variation ?? '';
        $variation = trim($variation);
        
        // Handle variant kosong: cari dengan empty string atau NULL
        if (empty($variation)) {
            $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                ->where('platform_product_name', $productName)
                ->where(function($query) {
                    $query->where('variant', '')
                          ->orWhereNull('variant');
                })
                ->first();
        } else {
            // Jika variant ada, cari dengan variant yang spesifik
            $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                ->where('platform_product_name', $productName)
                ->where('variant', $variation)
                ->first();
        }
        
        if (!$platformProduct) {
            // Simpan sebagai array untuk memisahkan nama dan variant dengan jelas
            // Variant HANYA dari kolom variant Excel, bukan dari parsing nama produk
            $productKey = [
                'nama_barang' => $productName,
                'variasi' => $variation
            ];
            
            // Cek apakah produk dengan nama dan variant yang sama sudah ada
            $exists = false;
            foreach ($this->unmappedProducts as $existing) {
                if (is_array($existing)) {
                    if ($existing['nama_barang'] === $productName && $existing['variasi'] === $variation) {
                        $exists = true;
                        break;
                    }
                } else {
                    // Backward compatibility: jika ada string format lama, skip
                    // (tidak perlu konversi karena format baru lebih jelas)
                }
            }
            
            if (!$exists) {
                $this->unmappedProducts[] = $productKey;
            }
        }
    }

    /**
     * Cek apakah baris kosong
     */
    protected function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse tanggal dari berbagai format
     */
    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            // Coba parse dengan Carbon
            $carbonDate = Carbon::parse($dateStr);
            return $carbonDate->format('Y-m-d');
        } catch (\Exception $e) {
            \Log::error("Failed to parse date: $dateStr - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Proses import data ke database
     */
    public function processImport()
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'duplicates' => 0, 
            'skipped' => 0,
            'failed_orders' => [],
        ];

        // Jika ada produk yang belum di-mapping atau data yang tidak valid, return error
        if (!empty($this->unmappedProducts) || !empty($this->invalidData)) {
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

                    // Validasi tanggal kosong sebelum parsing
                    if (empty($firstItem['tanggal']) || is_null($firstItem['tanggal']) || trim((string)$firstItem['tanggal']) === '') {
                        $results['skipped']++;
                        \Log::warning("Skipping order $orderNumber due to empty date: " . ($firstItem['tanggal'] ?? 'null'));
                        continue;
                    }
                    
                    // Parse tanggal menggunakan helper method
                    $tanggal = $this->parseDate($firstItem['tanggal']);
                    
                    if (empty($tanggal)) {
                        $results['skipped']++;
                        \Log::warning("Skipping order $orderNumber due to invalid date format: " . ($firstItem['tanggal'] ?? 'null'));
                        continue;
                    }

                    // Tentukan hari dari tanggal jika hari tidak tersedia
                    $hari = !empty($firstItem['hari']) ? $firstItem['hari'] : null;

                    // Ambil status_hari dari data
                    $statusHari = !empty($firstItem['status_hari']) ? $firstItem['status_hari'] : null;

                    // Cek apakah order sudah ada di database
                    $existingOrder = Order::where('platform_id', $this->platform->id)->where('order_number', $orderNumber)->first();

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
                        // Ambil nama produk dan variant HANYA dari kolom Excel (tidak parsing dari nama)
                        $productName = $item['nama_barang'];
                        // Variant HANYA diambil dari kolom variant, bukan dari pemotongan nama produk
                        $variation = isset($item['variasi']) && trim($item['variasi']) !== '' ? trim($item['variasi']) : '';
                        
                        // Tentukan apakah ini row variant berdasarkan kolom variant (bukan dari nama produk)
                        $isVariantRow = !empty($variation);
                        
                        \Log::info("Processing item: Product='{$productName}', Variant='{$variation}', IsVariantRow=" . ($isVariantRow ? 'Yes' : 'No') . ", qty: {$item['qty']}");
                        
                        // Ambil platform_product berdasarkan nama produk dan variant dari kolom variant
                        // PENTING: Variant diambil HANYA dari kolom variant, bukan dari parsing nama produk
                        // Handle variant kosong: cari dengan empty string atau NULL
                        if (empty($variation)) {
                            $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                                ->where('platform_product_name', $productName)
                                ->where(function($query) {
                                    $query->where('variant', '')
                                          ->orWhereNull('variant');
                                })
                                ->first();
                        } else {
                            // Jika variant ada, cari dengan variant yang spesifik
                            $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                                ->where('platform_product_name', $productName)
                                ->where('variant', $variation)
                                ->first();
                        }

                        // Jika masih tidak ditemukan, coba dengan nama yang mirip (tanpa mempertimbangkan variant dari nama)
                        if (!$platformProduct) {
                            if (empty($variation)) {
                                $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                                    ->where('platform_product_name', 'like', '%' . $productName . '%')
                                    ->where(function($query) {
                                        $query->where('variant', '')
                                              ->orWhereNull('variant');
                                    })
                                    ->first();
                            } else {
                                $platformProduct = PlatformProduct::where('platform_id', $this->platform->id)
                                    ->where('platform_product_name', 'like', '%' . $productName . '%')
                                    ->where('variant', $variation) // Tetap gunakan variant dari kolom variant
                                    ->first();
                            }
                        }

                        if (!$platformProduct) {
                            throw new \Exception("Produk '$fullProductName' tidak ditemukan di database.");
                        }

                        // Verifikasi stok terlebih dahulu
                        $checkStockResult = $this->checkStock($platformProduct, $item['qty']);
                        if (!$checkStockResult['success']) {
                            throw new \Exception("Stok tidak cukup untuk produk {$checkStockResult['product_name']}");
                        }

                        // Ensure price is a proper numeric value
                        $priceValue = $item['harga_setelah_diskon'];
                        if (is_string($priceValue)) {
                            $priceValue = preg_replace('/[^\d.]/', '', $priceValue);
                        }
                        
                        // Variant untuk order item: HANYA dari kolom variant, bukan dari parsing nama produk
                        $orderItemVariation = isset($item['variasi']) && trim($item['variasi']) !== '' ? trim($item['variasi']) : null;
                        
                        // Buat order item
                        $orderItem = new OrderItem([
                            'order_id' => $order->id,
                            'platform_product_id' => $platformProduct->id,
                            'quantity' => $item['qty'],
                            'price_after_discount' => $priceValue,
                            'tracking_number' => $item['no_resi'] ?? null,
                            'variasi' => $orderItemVariation, // Variant dari kolom variant, bukan dari parsing nama
                        ]);
                        $orderItem->save();

                        // Kurangi stok sesuai mapping dan catat barang keluar
                        $this->reduceStock($platformProduct, $item['qty'], $orderItem);
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed_orders'][] = [
                        'order_number' => $orderNumber,
                        'error' => $e->getMessage()
                    ];
                    \Log::error("Failed to process order $orderNumber: " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $results['errors'][] = $e->getMessage();
            \Log::error('Transaction failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Memeriksa apakah stok mencukupi tanpa menguranginya
     */
    protected function checkStock($platformProduct, $quantity)
    {
        // Ambil semua mapping barang AKTIF untuk platform product ini
        $mappings = MappingBarang::where('platform_product_id', $platformProduct->id)
            ->where('is_active', true)
            ->get();

        foreach ($mappings as $mapping) {
            // Hitung jumlah yang perlu dikurangi dari stok
            $qtyToReduce = $quantity * $mapping->quantity;

            // Ambil total stok yang tersedia
            $availableStock = WarehouseStock::where('product_id', $mapping->product_id)->where('qty', '>', 0)->sum('qty');

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
     */
    protected function recordBarangKeluar($orderItem, $stock, $quantity)
    {
        try {
            $kodeBarangKeluar = BarangKeluar::generateKode();
            
            // Get order to access tanggal and platform name
            $order = $orderItem->order;
            $platformName = $order->platform->name ?? 'Lazada';
            
            // Use order tanggal, fallback to now if not available
            $tanggalKeluar = $order->tanggal ?? now();
            if (is_string($tanggalKeluar)) {
                $tanggalKeluar = \Carbon\Carbon::parse($tanggalKeluar)->format('Y-m-d');
            } elseif ($tanggalKeluar instanceof \Carbon\Carbon) {
                $tanggalKeluar = $tanggalKeluar->format('Y-m-d');
            } else {
                $tanggalKeluar = now()->format('Y-m-d');
            }
            
            BarangKeluar::create([
                'kode_barang_keluar' => $kodeBarangKeluar,
                'order_item_id' => $orderItem->id,
                'warehouse_stock_id' => $stock->id,
                'qty' => $quantity,
                'tanggal_keluar' => $tanggalKeluar,
                'catatan' => "Penjualan online {$platformName} - Order #{$order->order_number}",
            ]);
            
            \Log::info("Recorded barang keluar: $kodeBarangKeluar, qty: $quantity");
        } catch (\Exception $e) {
            \Log::error('Failed to record barang keluar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Kurangi stok sesuai mapping
     */
    protected function reduceStock($platformProduct, $quantity, $orderItem)
    {
        try {
            // Ambil semua mapping barang AKTIF untuk platform product ini
            $mappings = MappingBarang::where('platform_product_id', $platformProduct->id)
                ->where('is_active', true)
                ->get();
            \Log::info("Reducing stock for platform product: {$platformProduct->platform_product_name}, Quantity: {$quantity}");
            \Log::info('Found mappings: ' . $mappings->count());

            foreach ($mappings as $mapping) {
                // Hitung jumlah yang perlu dikurangi dari stok
                $qtyToReduce = $quantity * $mapping->quantity;
                \Log::info("Processing mapping for product ID: {$mapping->product_id}, Qty to reduce: {$qtyToReduce}");

                // Ambil stok produk dari warehouse berdasarkan FIFO + prioritas HGN
                $stocks = WarehouseStock::where('product_id', $mapping->product_id)
                    ->where('qty', '>', 0)
                    ->orderBy('created_at') // Layer 1: FIFO berdasarkan tanggal penerimaan
                    ->orderBy('tax_id', 'asc') // Layer 2: HGN (tax_id=3) dulu, baru LM (tax_id=4)
                    ->get();
                \Log::info('Found stock records: ' . $stocks->count());

                $remainingQty = $qtyToReduce;
                $isFirstStock = true;  // Flag untuk menandai stok pertama

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    // Hitung quantity yang akan dikurangi dari stok ini
                    // Pastikan qty minimal 1 (tidak ada desimal seperti 0.5)
                    $qtyToTake = min($remainingQty, $stock->qty);
                    
                    // Jika qtyToTake kurang dari 1, skip stock ini dan lanjut ke stock berikutnya
                    if ($qtyToTake < 1) {
                        continue;
                    }
                    
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
                    $productName = $mapping->product ? $mapping->product->name : "Product ID: {$mapping->product_id}";
                    throw new \Exception("Stok tidak cukup untuk produk {$productName}");
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in reduceStock: ' . $e->getMessage());
            throw $e;
        }
    }

    // Getter methods
    public function getData() { return $this->data; }
    public function getUnmappedProducts() { return $this->unmappedProducts; }
    public function getInvalidData() { return $this->invalidData; }
    public function getHeaderIssues() { return $this->headerIssues; }
    public function getTotalRows() { return $this->totalRows; }
    public function getSkippedForMissingDate() { return $this->skippedForMissingDate; }
    public function getSkippedForMissingDay() { return $this->skippedForMissingDay; }
    public function getSkippedForMissingResi() { return $this->skippedForMissingResi; }

    // Setter methods untuk data dari session
    public function setData($data) { $this->data = $data; }
    public function setUnmappedProducts($unmappedProducts) { $this->unmappedProducts = $unmappedProducts; }
}
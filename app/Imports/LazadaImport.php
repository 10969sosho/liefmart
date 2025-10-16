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
     */
    public function __construct()
    {
        // Dapatkan platform Lazada dari database
        $this->platform = Platform::where('name', 'lazada')->first();

        // Jika platform tidak ditemukan, throw exception
        if (!$this->platform) {
            throw new \Exception('Platform Lazada tidak ditemukan di database.');
        }
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

        // Proses data dari baris setelah header
        $this->processData($rows, $headerRowIndex);

        // Debug: Log final data dan unmapped products
        \Log::info('Final Data count:', ['count' => count($this->data)]);
        \Log::info('Unmapped Products count:', ['count' => count($this->unmappedProducts)]);
    }

    /**
     * Cari baris header dalam data
     *
     * @return int|bool
     */
    protected function findHeaderRow(Collection $rows)
    {
        // Daftar header yang kita cari untuk Lazada
        $headerVariations = [
            'no_order' => ['NOMOR PESANAN', 'ORDER ID', 'NO PESANAN', 'ORDER NUMBER'],
            'tanggal' => ['TANGGAL', 'TANGGAL PESANAN', 'ORDER DATE', 'TANGGAL ORDER'],
            'hari' => ['HARI', 'DAY'],
            'status_hari' => ['STATUS HARI', 'STATUS'],
            'nama_barang' => ['PRODUK', 'NAMA PRODUK', 'NAMA BARANG', 'PRODUCT NAME'],
            'variasi' => ['VARIAN', 'VARIANT', 'VARIAN'],
            'qty' => ['QTY', 'JUMLAH', 'QUANTITY'],
            'harga_setelah_diskon' => ['HARGA SETELAH DISKON', 'HARGA', 'PRICE', 'SUBTOTAL'],
        ];

        // Buat list header yang flat untuk pencocokan
        $allPossibleHeaders = [];
        foreach ($headerVariations as $variations) {
            $allPossibleHeaders = array_merge($allPossibleHeaders, $variations);
        }

        // Array untuk menyimpan skor kecocokan untuk setiap baris
        $matchScores = [];

        // Cek 30 baris pertama untuk efisiensi
        foreach ($rows as $index => $row) {
            if ($index >= 30) break; // Batasi pencarian untuk efisiensi
            
            $score = 0;
            $matchedHeaders = [];
            
            foreach ($row as $cell) {
                if (is_string($cell)) {
                    $normalizedCell = trim(strtoupper($cell));
                    
                    // Cek apakah cell ini cocok dengan salah satu header yang diharapkan
                    foreach ($allPossibleHeaders as $expectedHeader) {
                        if ($normalizedCell === $expectedHeader || 
                            strpos($normalizedCell, $expectedHeader) !== false) {
                            $score++;
                            $matchedHeaders[] = $normalizedCell;
                            break; // Hanya hitung sekali per cell
                        }
                    }
                }
            }
            
            $matchScores[$index] = $score;
            
            // Jika skor cukup tinggi (minimal 4 header yang cocok), kemungkinan ini header
            if ($score >= 4) {
                \Log::info("Potential header row at index $index with score $score", [
                    'matched_headers' => $matchedHeaders
                ]);
                return $index;
            }
        }

        \Log::error("No suitable header row found. Best scores:", $matchScores);
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
            'hari' => $this->findColumnIndex($headers, ['HARI', 'DAY']),
            'status_hari' => $this->findColumnIndex($headers, ['STATUS HARI', 'STATUS']),
            'nama_barang' => $this->findColumnIndex($headers, ['PRODUK', 'NAMA PRODUK', 'NAMA BARANG']),
            'variasi' => $this->findColumnIndex($headers, ['VARIAN', 'VARIANT']),
            'qty' => $this->findColumnIndex($headers, ['QTY', 'QUANTITY', 'JUMLAH']),
            'harga_setelah_diskon' => $this->findColumnIndex($headers, ['HARGA SETELAH DISKON', 'HARGA']),
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

                if ($upperHeader === $upperName || strpos($upperHeader, $name) !== false) {
                    \Log::info("Match found for $name at index $index");

                    return $index;
                }
            }
        }

        \Log::warning('No matching column found for names: '.implode(', ', $possibleNames));

        return null;
    }

    /**
     * Validasi mapping kolom
     *
     * @return bool
     */
    protected function validateColumnMapping()
    {
        $requiredColumns = ['no_order', 'tanggal', 'nama_barang', 'qty', 'harga_setelah_diskon'];
        
        foreach ($requiredColumns as $column) {
            if ($this->columnMapping[$column] === null) {
                $this->headerIssues[] = "Kolom {$column} tidak ditemukan. Pastikan header sesuai dengan format Lazada.";
                \Log::error("Required column {$column} not found");
                return false;
            }
        }

        return true;
    }

    /**
     * Proses data dari baris setelah header
     *
     * @param  Collection  $rows
     * @param  int  $headerRowIndex
     * @return void
     */
    protected function processData(Collection $rows, $headerRowIndex)
    {
        $orderNumbersInFile = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Skip baris kosong
            if (empty(array_filter($row->toArray()))) {
                continue;
            }

            try {
                // Proses baris data
                $processedRow = $this->processRow($row, $i);
                
                if (!$processedRow) {
                    continue;
                }

                // Cek duplikat dalam file
                $orderNumber = $processedRow['no_order'];
                $fullProductName = $processedRow['nama_barang'] . ' - ' . ($processedRow['variasi'] ?? '');
                $duplicateKey = $orderNumber . '_' . $fullProductName;
                
                if (isset($orderNumbersInFile[$duplicateKey])) {
                    $this->duplicateOrders++;
                    \Log::info("Duplicate order+product in file: {$orderNumber} - {$fullProductName}");
                    continue;
                }
                
                // Cek duplikat di database
                $existingOrder = Order::where('order_number', $orderNumber)->first();
                if ($existingOrder) {
                    \Log::info("Duplicate order in database: {$orderNumber} - will be skipped");
                    continue;
                }
                
                // Tandai order+produk ini sudah diproses
                $orderNumbersInFile[$duplicateKey] = 1;
                
                // Tambahkan ke data yang valid
                $this->data[] = $processedRow;
            } catch (\Exception $e) {
                \Log::error("Error processing row $i: " . $e->getMessage());
                $this->invalidData[] = 'Baris #'.($i - $headerRowIndex).': Error: ' . $e->getMessage();
            }
        }
    }

    /**
     * Proses satu baris data
     *
     * @param  Collection  $row
     * @param  int  $rowIndex
     * @return array|null
     */
    protected function processRow(Collection $row, $rowIndex)
    {
        $processedData = [];

        // Ambil data berdasarkan mapping kolom
        foreach ($this->columnMapping as $field => $columnIndex) {
            if ($columnIndex !== null && isset($row[$columnIndex])) {
                $processedData[$field] = $row[$columnIndex];
            } else {
                $processedData[$field] = null;
            }
        }

        // Validasi data yang diperlukan
        if (empty($processedData['no_order']) || empty($processedData['tanggal']) || 
            empty($processedData['nama_barang']) || empty($processedData['qty']) || 
            empty($processedData['harga_setelah_diskon'])) {
            $this->skippedOrders++;
            return null;
        }

        // Konversi tanggal
        try {
            if (is_numeric($processedData['tanggal'])) {
                // Excel date format
                $processedData['tanggal'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($processedData['tanggal'])->format('Y-m-d');
            } else {
                $processedData['tanggal'] = Carbon::parse($processedData['tanggal'])->format('Y-m-d');
            }
        } catch (\Exception $e) {
            \Log::error("Error parsing date: " . $e->getMessage());
            $this->skippedOrders++;
            return null;
        }

        // Konversi qty dan harga
        $processedData['qty'] = (int) $processedData['qty'];
        $processedData['harga_setelah_diskon'] = (float) str_replace(',', '', $processedData['harga_setelah_diskon']);

        // Cari atau buat platform product
        $platformProduct = $this->findOrCreatePlatformProduct($processedData['nama_barang'], $processedData['variasi']);
        
        if (!$platformProduct) {
            $this->unmappedProducts[] = [
                'nama_barang' => $processedData['nama_barang'],
                'variasi' => $processedData['variasi'],
                'row' => $rowIndex
            ];
            return null;
        }

        $processedData['platform_product_id'] = $platformProduct->id;

        return $processedData;
    }

    /**
     * Cari atau buat platform product
     *
     * @param  string  $namaBarang
     * @param  string|null  $variasi
     * @return PlatformProduct|null
     */
    protected function findOrCreatePlatformProduct($namaBarang, $variasi = null)
    {
        // Cari platform product yang sudah ada
        $query = PlatformProduct::where('platform_id', $this->platform->id)
            ->where('platform_product_name', $namaBarang);
            
        if ($variasi) {
            $query->where('variant', $variasi);
        } else {
            $query->whereNull('variant');
        }
        
        $platformProduct = $query->first();
        
        if (!$platformProduct) {
            // Buat platform product baru
            $platformProduct = PlatformProduct::create([
                'platform_id' => $this->platform->id,
                'platform_product_name' => $namaBarang,
                'variant' => $variasi,
            ]);
        }
        
        return $platformProduct;
    }

    /**
     * Simpan data ke database
     *
     * @return array
     */
    public function saveToDatabase()
    {
        if (empty($this->data)) {
            return [
                'success' => false,
                'message' => 'Tidak ada data yang valid untuk disimpan.',
                'stats' => $this->getStats()
            ];
        }

        try {
            DB::beginTransaction();

            $savedOrders = 0;
            $savedItems = 0;

            foreach ($this->data as $row) {
                // Buat order
                $order = Order::create([
                    'platform_id' => $this->platform->id,
                    'order_number' => $row['no_order'],
                    'tanggal' => $row['tanggal'],
                    'hari' => $row['hari'],
                    'status_hari' => $row['status_hari'],
                    'customer_name' => 'Lazada Customer', // Default customer name
                    'total' => $row['harga_setelah_diskon'],
                    'status' => 'completed',
                ]);

                // Buat order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'platform_product_id' => $row['platform_product_id'],
                    'qty' => $row['qty'],
                    'harga' => $row['harga_setelah_diskon'],
                ]);

                $savedOrders++;
                $savedItems++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Berhasil menyimpan {$savedOrders} order dan {$savedItems} item.",
                'stats' => $this->getStats()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving Lazada data: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage(),
                'stats' => $this->getStats()
            ];
        }
    }

    /**
     * Dapatkan statistik import
     *
     * @return array
     */
    public function getStats()
    {
        return [
            'total_rows' => $this->totalRows,
            'valid_data' => count($this->data),
            'skipped_orders' => $this->skippedOrders,
            'duplicate_orders' => $this->duplicateOrders,
            'unmapped_products' => count($this->unmappedProducts),
            'invalid_data' => count($this->invalidData),
            'header_issues' => count($this->headerIssues),
        ];
    }

    /**
     * Dapatkan data yang tidak ter-mapping
     *
     * @return array
     */
    public function getUnmappedProducts()
    {
        return $this->unmappedProducts;
    }

    /**
     * Dapatkan data yang tidak valid
     *
     * @return array
     */
    public function getInvalidData()
    {
        return $this->invalidData;
    }

    /**
     * Dapatkan masalah header
     *
     * @return array
     */
    public function getHeaderIssues()
    {
        return $this->headerIssues;
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
     * Dapatkan total baris
     *
     * @return int
     */
    public function getTotalRows()
    {
        return $this->totalRows;
    }
}

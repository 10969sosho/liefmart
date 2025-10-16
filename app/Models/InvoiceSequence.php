<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_month',
        'counter',
        'last_updated',
        'category_type',  // Menambahkan kategori (KOPI, SKINCARE)
        'sales_type',     // Menambahkan jenis penjualan (ONLINE, OFFLINE)
        'tax_status'      // Menambahkan status pajak (PKP, NON PKP)
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    // Konstanta untuk kategori
    const CATEGORY_KOPI = 'KOPI';
    const CATEGORY_SKINCARE = 'SKINCARE';
    
    // Konstanta untuk jenis penjualan
    const SALES_ONLINE = 'ONLINE';
    const SALES_OFFLINE = 'OFFLINE';
    
    // Konstanta untuk status pajak
    const TAX_PKP = 'PKP';
    const TAX_NON_PKP = 'NON_PKP';

    /**
     * Mendapatkan nomor invoice berikutnya untuk kombinasi kategori, jenis penjualan, dan status pajak
     * dengan counter terpisah per kombinasi berdasarkan tanggal ORDER
     * 
     * @param string $category Kategori (KOPI/SKINCARE)
     * @param string $salesType Jenis penjualan (ONLINE/OFFLINE)
     * @param string $taxStatus Status pajak (PKP/NON_PKP)
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return array Array berisi nomor invoice dan counter
     */
    public static function getNextInvoiceNumber($category, $salesType, $taxStatus, $orderDate = null)
    {
        // Tanggal order WAJIB ada - TIDAK BOLEH NULL
        if (!$orderDate) {
            throw new \Exception("Tanggal order wajib ada untuk generate invoice number.");
        }
        
        // Format tahun-bulan berdasarkan tanggal ORDER (YYMM)
        $yearMonth = Carbon::parse($orderDate)->format('ym');
        
        // Menggunakan transaksi database untuk memastikan counter tidak duplikat
        $counter = DB::transaction(function() use ($yearMonth, $category, $salesType, $taxStatus) {
            // Dapatkan atau buat record counter untuk kombinasi spesifik
            $sequenceCounter = self::lockForUpdate()->firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'category_type' => $category,
                    'sales_type' => $salesType,
                    'tax_status' => $taxStatus
                ],
                [
                    'counter' => 0,
                    'last_updated' => now()
                ]
            );
            
            // SMART INVOICE SEQUENCE - Ambil semua nomor invoice dari SEMUA tabel untuk bulan/kategori/tipe/status ini
            $suffix = '';
            if ($category === self::CATEGORY_KOPI) {
                if ($salesType === self::SALES_ONLINE) {
                    $suffix = 'HPNSDA-OLK';
                } else { // OFFLINE
                    $suffix = 'HPNSDA-KOP';
                }
            } else { // SKINCARE
                if ($salesType === self::SALES_ONLINE) {
                    $suffix = 'HGNSDA-OL';
                } else { // OFFLINE
                    $suffix = 'HGNSDA-KOS';
                }
            }
            
            $taxCode = ($taxStatus === self::TAX_PKP) ? '01' : '02';
            $pattern = "%/$yearMonth/$suffix/$taxCode";
            
            // Ambil dari semua tabel yang relevan
            $existingInvoices = collect();
            
            // TikTok
            $tiktokInvoices = \App\Models\TiktokFinancialTransaction::where('no_invoice', 'like', $pattern)
                ->pluck('no_invoice');
            $existingInvoices = $existingInvoices->concat($tiktokInvoices);
            
            // Shopee
            $shopeeInvoices = \App\Models\ShopeeFinancialTransaction::where('no_invoice', 'like', $pattern)
                ->pluck('no_invoice');
            $existingInvoices = $existingInvoices->concat($shopeeInvoices);
            
            // Tokopedia (jika ada)
            try {
                $tokopediaInvoices = \App\Models\TokopediaFinancialTransaction::where('no_invoice', 'like', $pattern)
                    ->pluck('no_invoice');
                $existingInvoices = $existingInvoices->concat($tokopediaInvoices);
            } catch (\Exception $e) {
                // Table might not exist
            }
            
            // Blibli (jika ada)
            try {
                $blibliInvoices = \App\Models\BlibliFinancialTransaction::where('no_invoice', 'like', $pattern)
                    ->pluck('no_invoice');
                $existingInvoices = $existingInvoices->concat($blibliInvoices);
            } catch (\Exception $e) {
                // Table might not exist
            }
            
            // Extract nomor counter dari invoice numbers
            $existing = $existingInvoices
                ->map(fn($v) => intval(substr($v, 0, 6)))
                ->unique()
                ->sort()
                ->values();
            
            if ($existing->isEmpty()) {
                // Jika tidak ada data sama sekali → mulai dari 1
                $nextCounter = 1;
            } else {
                // Cari nomor terkecil yang hilang (untuk gap filling)
                $expected = 1;
                foreach ($existing as $num) {
                    if ($num != $expected) break;
                    $expected++;
                }
                
                // Kalau ada gap, isi gap dulu
                if ($expected <= $existing->max()) {
                    $nextCounter = $expected;
                } else {
                    // Kalau tidak ada gap, lanjut dari nomor terakhir
                    $nextCounter = $existing->max() + 1;
                }
            }
            
            // Update counter di tabel sequence agar sinkron
            $sequenceCounter->counter = $nextCounter;
            $sequenceCounter->last_updated = now();
            $sequenceCounter->save();
            
            return $nextCounter;
        });
        
        // Format nomor invoice berdasarkan kombinasi kategori, jenis penjualan dan status pajak
        $invoiceNumber = self::formatInvoiceNumber($category, $salesType, $taxStatus, $yearMonth, $counter);
        
        return [
            'invoice_number' => $invoiceNumber,
            'counter' => $counter
        ];
    }
    
    /**
     * Mendapatkan nomor invoice yang sudah ada untuk kombinasi tertentu
     * 
     * @param string $yearMonth Tahun dan bulan format YYMM
     * @param string $category Kategori (KOPI/SKINCARE)
     * @param string $salesType Jenis penjualan (ONLINE/OFFLINE)
     * @param string $taxStatus Status pajak (PKP/NON_PKP)
     * @return array Array nomor invoice yang sudah ada
     */
    private static function getExistingInvoiceNumbers($yearMonth, $category, $salesType, $taxStatus)
    {
        try {
            // Cari semua invoice yang sudah ada untuk kombinasi ini
            $existingInvoices = collect();
            
            // Cek di semua tabel financial transaction
            $tables = [
                'tiktok_financial_transactions',
                'shopee_financial_transactions', 
                'blibli_financial_transactions',
                'tokopedia_financial_transactions'
            ];
            
            // Buat pattern untuk mencari invoice berdasarkan kombinasi
            // Format invoice: {counter}/{yearMonth}/{suffix}/{taxCode}
            // Contoh: 000001/2503/HPNSDA-OLK/01
            $pattern = '%/' . $yearMonth . '/%';
            
            foreach ($tables as $table) {
                try {
                    $invoices = DB::table($table)
                        ->where('no_invoice', 'like', $pattern)
                        ->pluck('no_invoice');
                        
                    $existingInvoices = $existingInvoices->concat($invoices);
                } catch (\Exception $e) {
                    // Skip table if it doesn't exist or has issues
                    \Log::warning("Error querying table {$table}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Extract nomor urut dari invoice number
            $numbers = $existingInvoices->map(function($invoice) {
                // Format: 000001/2503/HPNSDA-OLK/01
                // Ambil bagian pertama sebagai nomor urut
                $parts = explode('/', $invoice);
                if (count($parts) >= 1) {
                    $counter = $parts[0];
                    return intval($counter);
                }
                return null;
            })->filter()->sort()->values();
            
            return $numbers->toArray();
        } catch (\Exception $e) {
            \Log::error("Error in getExistingInvoiceNumbers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format nomor invoice berdasarkan kombinasi kategori, jenis penjualan, dan status pajak
     * 
     * @param string $category Kategori (KOPI/SKINCARE)
     * @param string $salesType Jenis penjualan (ONLINE/OFFLINE)
     * @param string $taxStatus Status pajak (PKP/NON_PKP)
     * @param string $yearMonth Tahun dan bulan format YYMM
     * @param int $counter Nomor urut
     * @return string Nomor invoice terformat
     */
    private static function formatInvoiceNumber($category, $salesType, $taxStatus, $yearMonth, $counter)
    {
        // Format counter (6 digit untuk online, 4 digit untuk offline)
        $counterFormat = ($salesType === self::SALES_ONLINE) ? '%06d' : '%04d';
        $formattedCounter = sprintf($counterFormat, $counter);
        
        // Format suffix berdasarkan kategori, jenis penjualan, dan status pajak
        $suffix = '';
        
        if ($category === self::CATEGORY_KOPI) {
            if ($salesType === self::SALES_ONLINE) {
                $suffix = 'HPNSDA-OLK';
            } else { // OFFLINE
                $suffix = 'HPNSDA-KOP';
            }
        } else { // SKINCARE
            if ($salesType === self::SALES_ONLINE) {
                $suffix = 'HGNSDA-OL';
            } else { // OFFLINE
                $suffix = 'HGNSDA-KOS';
            }
        }
        
        // Tambahkan kode pajak
        $taxCode = ($taxStatus === self::TAX_PKP) ? '01' : '02';
        
        // Format: {counter}/{yearMonth}/{suffix}/{taxCode}
        // Contoh: 000001/2503/HPNSDA-OLK/01
        return $formattedCounter . '/' . $yearMonth . '/' . $suffix . '/' . $taxCode;
    }

    /**
     * Mendapatkan batch invoice numbers untuk banyak transaksi
     * Method ini digunakan untuk menghasilkan banyak nomor invoice dalam satu transaksi database
     * 
     * @param string $category Kategori (KOPI/SKINCARE)
     * @param string $salesType Jenis penjualan (ONLINE/OFFLINE)
     * @param string $taxStatus Status pajak (PKP/NON_PKP)
     * @param int $count Jumlah nomor invoice yang diperlukan
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return array Array berisi nomor-nomor invoice
     */
    public static function getBatchInvoiceNumbers($category, $salesType, $taxStatus, $count, $orderDate = null)
    {
        if ($count <= 0) {
            return [];
        }

        // Jika tidak ada tanggal ORDER, gunakan tanggal saat ini
        if (!$orderDate) {
            $orderDate = Carbon::now()->format('Y-m-d');
        }
        
        // Format tahun-bulan berdasarkan tanggal ORDER (YYMM)
        $yearMonth = Carbon::parse($orderDate)->format('ym');
        
        // Menggunakan transaksi database untuk memastikan counter tidak duplikat
        return DB::transaction(function() use ($yearMonth, $category, $salesType, $taxStatus, $count) {
            // Dapatkan atau buat record counter untuk kombinasi spesifik
            $sequenceCounter = self::firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'category_type' => $category,
                    'sales_type' => $salesType,
                    'tax_status' => $taxStatus
                ],
                [
                    'counter' => 0,
                    'last_updated' => now()
                ]
            );
            
            $startCounter = $sequenceCounter->counter + 1;
            $endCounter = $startCounter + $count - 1;
            
            // Update counter
            $sequenceCounter->counter = $endCounter;
            $sequenceCounter->last_updated = now();
            $sequenceCounter->save();
            
            // Generate invoice numbers
            $invoiceNumbers = [];
            for ($i = $startCounter; $i <= $endCounter; $i++) {
                $invoiceNumbers[] = [
                    'invoice_number' => self::formatInvoiceNumber($category, $salesType, $taxStatus, $yearMonth, $i),
                    'counter' => $i
                ];
            }
            
            return $invoiceNumbers;
        });
    }

    /**
     * Get the next invoice number based on tax ID (backward compatibility)
     * 
     * @param int $taxId The tax ID for determining the suffix format
     * @param bool $isOnline Whether this is an online or offline transaction
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return array Returns [invoice_number, counter]
     */
    public static function getNextInvoiceNumberByTaxId($taxId, $isOnline = true, $orderDate = null)
    {
        $categoryType = '';
        $salesType = '';
        $taxStatus = '';
        
        switch($taxId) {
            // KOPI category
            case 1: 
                $categoryType = self::CATEGORY_KOPI;
                $salesType = self::SALES_ONLINE;
                $taxStatus = self::TAX_PKP;
                break;
            case 2: 
                $categoryType = self::CATEGORY_KOPI;
                $salesType = self::SALES_ONLINE;
                $taxStatus = self::TAX_NON_PKP;
                break;
            case 5: 
                $categoryType = self::CATEGORY_KOPI;
                $salesType = self::SALES_OFFLINE;
                $taxStatus = self::TAX_PKP;
                break;
            case 6: 
                $categoryType = self::CATEGORY_KOPI;
                $salesType = self::SALES_OFFLINE;
                $taxStatus = self::TAX_NON_PKP;
                break;
            
            // SKINCARE category
            case 3: 
                $categoryType = self::CATEGORY_SKINCARE;
                $salesType = self::SALES_ONLINE;
                $taxStatus = self::TAX_PKP;
                break;
            case 4: 
                $categoryType = self::CATEGORY_SKINCARE;
                $salesType = self::SALES_ONLINE;
                $taxStatus = self::TAX_NON_PKP;
                break;
            case 7: 
                $categoryType = self::CATEGORY_SKINCARE;
                $salesType = self::SALES_OFFLINE;
                $taxStatus = self::TAX_PKP;
                break;
            case 8: 
                $categoryType = self::CATEGORY_SKINCARE;
                $salesType = self::SALES_OFFLINE;
                $taxStatus = self::TAX_NON_PKP;
                break;
            
            default: 
                $categoryType = self::CATEGORY_SKINCARE;
                $salesType = self::SALES_ONLINE;
                $taxStatus = self::TAX_PKP;
                break;
        }
        
        return self::getNextInvoiceNumber($categoryType, $salesType, $taxStatus, $orderDate);
    }
} 
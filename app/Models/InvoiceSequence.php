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
     * dengan counter yang terpusat (tidak terpisah per kategori)
     * 
     * @param string $category Kategori (KOPI/SKINCARE)
     * @param string $salesType Jenis penjualan (ONLINE/OFFLINE)
     * @param string $taxStatus Status pajak (PKP/NON_PKP)
     * @return array Array berisi nomor invoice dan counter
     */
    public static function getNextInvoiceNumber($category, $salesType, $taxStatus)
    {
        // Format tahun-bulan saat ini (YYMM)
        $yearMonth = Carbon::now()->format('ym');
        
        // Menggunakan transaksi database untuk memastikan counter tidak duplikat
        $counter = DB::transaction(function() use ($yearMonth) {
            // Dapatkan atau buat record global counter untuk bulan dan tahun ini
            $globalCounter = self::firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'category_type' => 'GLOBAL',
                    'sales_type' => 'GLOBAL',
                    'tax_status' => 'GLOBAL'
                ],
                [
                    'counter' => 0,
                    'last_updated' => now()
                ]
            );
            
            // Tingkatkan counter
            $globalCounter->counter += 1;
            $globalCounter->last_updated = now();
            $globalCounter->save();
            
            return $globalCounter->counter;
        });
        
        // Format nomor invoice berdasarkan kombinasi kategori, jenis penjualan dan status pajak
        $invoiceNumber = self::formatInvoiceNumber($category, $salesType, $taxStatus, $yearMonth, $counter);
        
        return [
            'invoice_number' => $invoiceNumber,
            'counter' => $counter
        ];
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
     * @return array Array berisi nomor-nomor invoice
     */
    public static function getBatchInvoiceNumbers($category, $salesType, $taxStatus, $count)
    {
        if ($count <= 0) {
            return [];
        }

        // Format tahun-bulan saat ini (YYMM)
        $yearMonth = Carbon::now()->format('ym');
        
        // Menggunakan transaksi database untuk memastikan counter tidak duplikat
        return DB::transaction(function() use ($yearMonth, $category, $salesType, $taxStatus, $count) {
            // Dapatkan atau buat record global counter untuk bulan dan tahun ini
            $globalCounter = self::firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'category_type' => 'GLOBAL',
                    'sales_type' => 'GLOBAL',
                    'tax_status' => 'GLOBAL'
                ],
                [
                    'counter' => 0,
                    'last_updated' => now()
                ]
            );
            
            $startCounter = $globalCounter->counter + 1;
            $endCounter = $startCounter + $count - 1;
            
            // Update counter
            $globalCounter->counter = $endCounter;
            $globalCounter->last_updated = now();
            $globalCounter->save();
            
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
     * @return array Returns [invoice_number, counter]
     */
    public static function getNextInvoiceNumberByTaxId($taxId, $isOnline = true)
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
        
        return self::getNextInvoiceNumber($categoryType, $salesType, $taxStatus);
    }
} 
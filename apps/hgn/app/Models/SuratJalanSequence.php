<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuratJalanSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_month',
        'counter',
        'last_updated',
        'main_category_id',  // Kategori utama (HPNSDA, HGNSDA)
        'tax_id'            // Tax ID untuk menentukan suffix
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    /**
     * Mendapatkan nomor Surat Jalan berikutnya berdasarkan tanggal ORDER
     * 
     * @param int $taxId Tax ID untuk menentukan suffix
     * @param int $mainCategoryId Main category ID
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return array Array berisi nomor SJ dan counter
     */
    public static function getNextSuratJalanNumber($taxId, $mainCategoryId, $orderDate = null)
    {
        // Jika tidak ada tanggal ORDER, gunakan tanggal saat ini
        if (!$orderDate) {
            $orderDate = Carbon::now()->format('Y-m-d');
        }
        
        // Format tahun-bulan berdasarkan tanggal ORDER (YYMM)
        $yearMonth = Carbon::parse($orderDate)->format('ym');
        
        // Menggunakan transaksi database untuk memastikan counter tidak duplikat
        $counter = DB::transaction(function() use ($yearMonth, $taxId, $mainCategoryId) {
            // Dapatkan atau buat record counter untuk kombinasi spesifik
            $sequenceCounter = self::firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'tax_id' => $taxId,
                    'main_category_id' => $mainCategoryId
                ],
                [
                    'counter' => 0,
                    'last_updated' => now()
                ]
            );
            
            // Tingkatkan counter
            $sequenceCounter->counter += 1;
            $sequenceCounter->last_updated = now();
            $sequenceCounter->save();
            
            return $sequenceCounter->counter;
        });
        
        // Format nomor SJ berdasarkan kombinasi tax_id dan main_category_id
        $suratJalanNumber = self::formatSuratJalanNumber($taxId, $mainCategoryId, $yearMonth, $counter);
        
        return [
            'surat_jalan_number' => $suratJalanNumber,
            'counter' => $counter
        ];
    }
    
    /**
     * Format nomor Surat Jalan berdasarkan tax_id dan main_category_id
     * 
     * @param int $taxId Tax ID
     * @param int $mainCategoryId Main category ID
     * @param string $yearMonth Tahun dan bulan format YYMM
     * @param int $counter Nomor urut
     * @return string Nomor SJ terformat
     */
    private static function formatSuratJalanNumber($taxId, $mainCategoryId, $yearMonth, $counter)
    {
        // Format counter (4 digit)
        $formattedCounter = sprintf('%04d', $counter);
        
        // Format suffix berdasarkan tax_id dan main_category_id
        $suffix = '';
        
        if ($mainCategoryId == 1) { // HPNSDA (KOPI)
            if ($taxId == 1) { // PKP
                $suffix = "HPNSDA-KOP/01";
            } elseif ($taxId == 2) { // Non PKP
                $suffix = "HPNSDA-KOP/02";
            } else {
                // Default if tax ID not specified
                $suffix = "HPNSDA-KOP/01";
            }
        } elseif ($mainCategoryId == 2) { // HGNSDA (SKINCARE)
            if ($taxId == 3) { // HGN PKP
                $suffix = "HGNSDA-KOS/01";
            } elseif ($taxId == 4) { // LM Non PKP
                $suffix = "HGNSDA-KOS/02";
            } else {
                // Default if tax ID not specified
                $suffix = "HGNSDA-KOS/01";
            }
        } else {
            // Fallback untuk main category yang tidak dikenal
            $suffix = "HGNSDA-KOS/01";
        }
        
        // Format: {counter}/{yearMonth}/{suffix}
        // Contoh: 0001/2508/HGNSDA-KOS/01
        return $formattedCounter . '/' . $yearMonth . '/' . $suffix;
    }

    /**
     * Mendapatkan batch Surat Jalan numbers untuk banyak transaksi
     * 
     * @param int $taxId Tax ID
     * @param int $mainCategoryId Main category ID
     * @param int $count Jumlah nomor SJ yang diperlukan
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return array Array berisi nomor-nomor SJ
     */
    public static function getBatchSuratJalanNumbers($taxId, $mainCategoryId, $count, $orderDate = null)
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
        return DB::transaction(function() use ($yearMonth, $taxId, $mainCategoryId, $count) {
            // Dapatkan atau buat record counter untuk kombinasi spesifik
            $sequenceCounter = self::firstOrCreate(
                [
                    'year_month' => $yearMonth,
                    'tax_id' => $taxId,
                    'main_category_id' => $mainCategoryId
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
            
            // Generate SJ numbers
            $suratJalanNumbers = [];
            for ($i = $startCounter; $i <= $endCounter; $i++) {
                $suratJalanNumbers[] = [
                    'surat_jalan_number' => self::formatSuratJalanNumber($taxId, $mainCategoryId, $yearMonth, $i),
                    'counter' => $i
                ];
            }
            
            return $suratJalanNumbers;
        });
    }
}

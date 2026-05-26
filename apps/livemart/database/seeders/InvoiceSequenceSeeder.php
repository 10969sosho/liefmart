<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvoiceSequence;
use Carbon\Carbon;

class InvoiceSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Dapatkan bulan dan tahun saat ini
        $currentYearMonth = Carbon::now()->format('ym');
        
        // Tambahkan record GLOBAL untuk sistem counter terpusat
        InvoiceSequence::firstOrCreate(
            [
                'year_month' => $currentYearMonth,
                'category_type' => 'GLOBAL',
                'sales_type' => 'GLOBAL',
                'tax_status' => 'GLOBAL',
            ],
            [
                'counter' => 0,
                'last_updated' => now(),
            ]
        );
        
        // Array untuk kategori, jenis penjualan, dan status pajak
        // Ini tetap diperlukan untuk mendukung template invoice yang berbeda
        $categories = [
            InvoiceSequence::CATEGORY_KOPI,
            InvoiceSequence::CATEGORY_SKINCARE
        ];
        
        $salesTypes = [
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::SALES_OFFLINE
        ];
        
        $taxStatuses = [
            InvoiceSequence::TAX_PKP,
            InvoiceSequence::TAX_NON_PKP
        ];
        
        $count = 0;
        $existingCount = 0;
        
        // Buat semua kombinasi yang mungkin
        foreach ($categories as $category) {
            foreach ($salesTypes as $salesType) {
                foreach ($taxStatuses as $taxStatus) {
                    // Periksa apakah kombinasi sudah ada
                    $exists = InvoiceSequence::where([
                        'year_month' => $currentYearMonth,
                        'category_type' => $category,
                        'sales_type' => $salesType,
                        'tax_status' => $taxStatus
                    ])->exists();
                    
                    if (!$exists) {
                        InvoiceSequence::create([
                            'year_month' => $currentYearMonth,
                            'category_type' => $category,
                            'sales_type' => $salesType,
                            'tax_status' => $taxStatus,
                            'counter' => 0, // Mulai dari 0, akan bertambah saat pertama kali digunakan
                            'last_updated' => now()
                        ]);
                        
                        $count++;
                    } else {
                        $existingCount++;
                    }
                }
            }
        }
        
        // Tampilkan pesan sukses
        $this->command->info("$count kombinasi invoice sequence baru dibuat untuk bulan $currentYearMonth");
        if ($existingCount > 0) {
            $this->command->info("$existingCount kombinasi sudah ada dan dilewati");
        }
    }
}

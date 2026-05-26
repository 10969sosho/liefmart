<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceSequence;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TiktokFinancialTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ambil bulan dan tahun saat ini
        $yearMonth = date('ym');
        
        // Dapatkan counter tertinggi dari semua transaksi yang ada
        $highestCounter = $this->getHighestExistingCounter();
        
        // Jika tidak ada transaksi, mulai dari 0
        if ($highestCounter <= 0) {
            $highestCounter = 0;
        }
        
        // Tambahkan record GLOBAL untuk sistem counter terpusat
        DB::table('invoice_sequences')->insert([
            'year_month' => $yearMonth,
            'counter' => $highestCounter,
            'last_updated' => now(),
            'category_type' => 'GLOBAL',
            'sales_type' => 'GLOBAL',
            'tax_status' => 'GLOBAL',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Hapus record GLOBAL
        DB::table('invoice_sequences')
            ->where('category_type', 'GLOBAL')
            ->where('sales_type', 'GLOBAL')
            ->where('tax_status', 'GLOBAL')
            ->delete();
    }
    
    /**
     * Mendapatkan nomor counter tertinggi dari semua transaksi yang ada
     */
    private function getHighestExistingCounter()
    {
        // Pola regex untuk mengekstrak nomor counter dari nomor invoice
        $counterPattern = '/^(\d+)\//';
        
        // Array untuk menyimpan semua nomor counter
        $allCounters = [];
        
        // Fungsi untuk mengekstrak counter dari nomor invoice
        $extractCounter = function($invoiceNumber) use ($counterPattern) {
            if (preg_match($counterPattern, $invoiceNumber, $matches)) {
                return (int) $matches[1];
            }
            return 0;
        };
        
        // Kumpulkan semua nomor invoice dari semua platform
        $shopeeInvoices = ShopeeFinancialTransaction::pluck('no_invoice')->toArray();
        $tiktokInvoices = TiktokFinancialTransaction::pluck('no_invoice')->toArray();
        
        // Gabungkan semua nomor invoice
        $allInvoices = array_merge($shopeeInvoices, $tiktokInvoices);
        
        // Ekstrak counter dari setiap nomor invoice
        foreach ($allInvoices as $invoiceNumber) {
            $counter = $extractCounter($invoiceNumber);
            if ($counter > 0) {
                $allCounters[] = $counter;
            }
        }
        
        // Jika tidak ada counter, kembalikan 0
        if (empty($allCounters)) {
            return 0;
        }
        
        // Kembalikan counter tertinggi
        return max($allCounters);
    }
};

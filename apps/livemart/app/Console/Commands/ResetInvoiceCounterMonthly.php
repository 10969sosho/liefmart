<?php

namespace App\Console\Commands;

use App\Models\InvoiceSequence;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetInvoiceCounterMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:reset-counter-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset invoice counter setiap pergantian bulan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses reset counter invoice bulanan...');
        
        try {
            $currentMonth = Carbon::now()->format('ym');
            $lastMonth = Carbon::now()->subMonth()->format('ym');
            
            // Cek apakah sudah ada record untuk bulan ini
            $existingThisMonth = InvoiceSequence::where('year_month', $currentMonth)->exists();
            
            if ($existingThisMonth) {
                $this->info('Counter untuk bulan ini sudah ada, tidak perlu reset.');
                return Command::SUCCESS;
            }
            
            // Ambil record dari bulan lalu untuk referensi
            $lastMonthRecords = InvoiceSequence::where('year_month', $lastMonth)->get();
            
            if ($lastMonthRecords->isEmpty()) {
                $this->info('Tidak ada record dari bulan lalu untuk direferensikan.');
                return Command::SUCCESS;
            }
            
            // Buat record baru untuk bulan ini berdasarkan kombinasi yang ada di bulan lalu
            $createdCount = 0;
            foreach ($lastMonthRecords as $record) {
                InvoiceSequence::create([
                    'year_month' => $currentMonth,
                    'category_type' => $record->category_type,
                    'sales_type' => $record->sales_type,
                    'tax_status' => $record->tax_status,
                    'counter' => 0, // Reset counter ke 0 untuk bulan baru
                    'last_updated' => now()
                ]);
                $createdCount++;
            }
            
            $this->info("Berhasil membuat {$createdCount} record counter untuk bulan ini.");
            
            Log::info('Invoice counter reset monthly completed', [
                'current_month' => $currentMonth,
                'last_month' => $lastMonth,
                'created_records' => $createdCount
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            Log::error('Error resetting invoice counter monthly', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
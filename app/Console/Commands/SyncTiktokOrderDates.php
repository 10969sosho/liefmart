<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class SyncTiktokOrderDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:sync-order-dates {--dry-run : Run without actually updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi tanggal_order di tiktok_financial_transactions dengan tanggal di tabel orders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Mode DRY RUN - Tidak ada perubahan yang akan disimpan');
        } else {
            $this->warn('⚠️  Mode LIVE - Perubahan akan disimpan ke database');
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan?')) {
                $this->info('Operasi dibatalkan.');
                return Command::SUCCESS;
            }
        }
        
        $this->info('🚀 Memulai sinkronisasi tanggal order...');
        $this->newLine();
        
        // Get all transactions with their orders
        $transactions = TiktokFinancialTransaction::with('order')->get();
        
        $totalTransactions = $transactions->count();
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        $this->info("Total transaksi yang akan dicek: {$totalTransactions}");
        $this->newLine();
        
        $bar = $this->output->createProgressBar($totalTransactions);
        $bar->start();
        
        foreach ($transactions as $trx) {
            $bar->advance();
            
            // Skip if no order found
            if (!$trx->order) {
                // Try to find order by order_number
                $order = Order::where('order_number', $trx->no_order)->first();
                
                if (!$order) {
                    $this->newLine();
                    $this->warn("⚠️  Order tidak ditemukan untuk transaksi: {$trx->no_order}");
                    $errors++;
                    continue;
                }
                
                $trx->order = $order;
            }
            
            // Compare dates
            $trxDate = \Carbon\Carbon::parse($trx->tanggal_order)->format('Y-m-d');
            $orderDate = \Carbon\Carbon::parse($trx->order->tanggal)->format('Y-m-d');
            
            if ($trxDate != $orderDate) {
                $this->newLine();
                $this->line("📝 Order: {$trx->no_order}");
                $this->line("   Tanggal lama: {$trxDate}");
                $this->line("   Tanggal baru: {$orderDate}");
                
                if (!$dryRun) {
                    $trx->tanggal_order = $trx->order->tanggal;
                    $trx->save();
                    
                    Log::info("Tanggal order diperbaiki untuk {$trx->no_order}", [
                        'old_date' => $trxDate,
                        'new_date' => $orderDate
                    ]);
                }
                
                $updated++;
            } else {
                $skipped++;
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Summary
        $this->info('✅ Sinkronisasi selesai!');
        $this->newLine();
        $this->table(
            ['Kategori', 'Jumlah'],
            [
                ['Total Transaksi', $totalTransactions],
                ['Diperbaiki', $updated],
                ['Sudah Benar (Skip)', $skipped],
                ['Error/Order Tidak Ketemu', $errors],
            ]
        );
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 Ini adalah DRY RUN - tidak ada perubahan yang disimpan');
            $this->info('Jalankan tanpa --dry-run untuk menyimpan perubahan:');
            $this->line('php artisan tiktok:sync-order-dates');
        } else {
            $this->newLine();
            $this->info("💾 {$updated} transaksi telah diperbaiki dan disimpan ke database");
        }
        
        return Command::SUCCESS;
    }
}


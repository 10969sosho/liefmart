<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LazadaFinancialTransaction;
use Illuminate\Support\Facades\DB;

class DeleteLazadaFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lazada:delete-financial-data {--force : Force delete without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all Lazada financial transactions data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');
        
        // Count total transactions
        $totalCount = LazadaFinancialTransaction::count();
        
        if ($totalCount == 0) {
            $this->info('Tidak ada data financial Lazada yang perlu dihapus.');
            return 0;
        }
        
        $this->info("Total data financial Lazada yang akan dihapus: {$totalCount}");
        
        if (!$force) {
            if (!$this->confirm('Apakah Anda yakin ingin menghapus semua data financial Lazada?', true)) {
                $this->info('Operasi dibatalkan.');
                return 0;
            }
        }
        
        try {
            DB::beginTransaction();
            
            $this->info('Menghapus data financial Lazada...');
            
            // Delete all Lazada financial transactions
            $deleted = LazadaFinancialTransaction::query()->delete();
            
            DB::commit();
            
            $this->info("Berhasil menghapus {$deleted} data financial Lazada.");
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            return 1;
        }
    }
}


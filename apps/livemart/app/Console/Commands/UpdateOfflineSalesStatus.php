<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OfflineSale;

class UpdateOfflineSalesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offline-sales:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update offline sales status based on payment status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update offline sales status...');
        
        // Get all offline sales without global scope
        $offlineSales = OfflineSale::withoutGlobalScope('mainCategory')->get();
        
        $updated = 0;
        $total = $offlineSales->count();
        
        foreach ($offlineSales as $sale) {
            $oldStatus = $sale->status;
            $sale->updateStatusBasedOnPayment();
            
            if ($sale->status !== $oldStatus) {
                $updated++;
                $this->line("Updated sale {$sale->surat_jalan_number}: {$oldStatus} -> {$sale->status}");
            }
        }
        
        $this->info("Completed! Updated {$updated} out of {$total} offline sales.");
        
        return 0;
    }
}

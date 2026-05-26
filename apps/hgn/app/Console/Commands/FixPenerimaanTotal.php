<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use Illuminate\Console\Command;

class FixPenerimaanTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:fix-total {--id= : Specific penerimaan ID to fix} {--all : Fix all penerimaan records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix penerimaan total inconsistencies by recalculating from detail items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking penerimaan total inconsistencies...');

        if ($this->option('id')) {
            $penerimaan = Penerimaan::find($this->option('id'));
            if (!$penerimaan) {
                $this->error('Penerimaan with ID ' . $this->option('id') . ' not found.');
                return 1;
            }
            $this->fixPenerimaan($penerimaan);
        } elseif ($this->option('all')) {
            $penerimaanList = Penerimaan::with('details')->get();
            $this->info('Found ' . $penerimaanList->count() . ' penerimaan records to check.');
            
            $fixedCount = 0;
            $inconsistentCount = 0;
            
            foreach ($penerimaanList as $penerimaan) {
                if (!$penerimaan->isTotalConsistent()) {
                    $inconsistentCount++;
                    $this->fixPenerimaan($penerimaan);
                    $fixedCount++;
                }
            }
            
            $this->info("Fixed {$fixedCount} out of {$inconsistentCount} inconsistent records.");
        } else {
            $this->error('Please specify --id or --all option.');
            return 1;
        }

        $this->info('Done!');
        return 0;
    }

    private function fixPenerimaan(Penerimaan $penerimaan)
    {
        $oldTotal = $penerimaan->total_harga;
        $calculatedTotal = $penerimaan->calculated_total;
        
        $this->line("Penerimaan ID: {$penerimaan->id} ({$penerimaan->kode_penerimaan})");
        $this->line("  Stored total: Rp " . number_format($oldTotal, 2, ',', '.'));
        $this->line("  Calculated total: Rp " . number_format($calculatedTotal, 2, ',', '.'));
        
        if (abs($oldTotal - $calculatedTotal) >= 0.01) {
            $penerimaan->recalculateTotalHarga();
            $this->line("  ✅ Fixed: Updated total to Rp " . number_format($calculatedTotal, 2, ',', '.'));
        } else {
            $this->line("  ✅ Already consistent");
        }
    }
}

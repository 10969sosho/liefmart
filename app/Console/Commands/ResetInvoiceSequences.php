<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

class ResetInvoiceSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:reset-sequences {--year-month=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset invoice sequences to start from 1 for a specific year-month or current month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yearMonth = $this->option('year-month') ?: date('ym');
        $force = $this->option('force');

        $this->info("Resetting invoice sequences for year-month: {$yearMonth}");

        if (!$force) {
            if (!$this->confirm("This will reset all invoice sequences for {$yearMonth}. Are you sure?")) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Get all sequences for the specified year-month
            $sequences = InvoiceSequence::where('year_month', $yearMonth)->get();

            if ($sequences->isEmpty()) {
                $this->info("No sequences found for year-month: {$yearMonth}");
                return;
            }

            $this->info("Found " . $sequences->count() . " sequences for {$yearMonth}");

            // Reset each sequence counter to 0
            foreach ($sequences as $sequence) {
                $this->line("Resetting {$sequence->category_type} | {$sequence->sales_type} | {$sequence->tax_status} from {$sequence->counter} to 0");
                $sequence->counter = 0;
                $sequence->last_updated = now();
                $sequence->save();
            }

            DB::commit();
            $this->info("Successfully reset all invoice sequences for {$yearMonth}");
            $this->info("Next invoice numbers will start from 000001");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error resetting sequences: " . $e->getMessage());
        }
    }
}

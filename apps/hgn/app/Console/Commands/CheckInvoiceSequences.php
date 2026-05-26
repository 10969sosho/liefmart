<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceSequence;
use App\Models\TiktokFinancialTransaction;
use App\Models\ShopeeFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
use App\Models\TokopediaFinancialTransaction;

class CheckInvoiceSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:check-sequences {--year-month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current invoice sequence status and recent invoice numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yearMonth = $this->option('year-month') ?: date('ym');

        $this->info("Invoice Sequence Status for year-month: {$yearMonth}");
        $this->line("=" . str_repeat("=", 60));

        // Show current sequences
        $sequences = InvoiceSequence::where('year_month', $yearMonth)
            ->orderBy('category_type')
            ->orderBy('sales_type')
            ->orderBy('tax_status')
            ->get();

        if ($sequences->isEmpty()) {
            $this->warn("No sequences found for year-month: {$yearMonth}");
            return;
        }

        $this->info("Current Sequences:");
        $this->line("Year-Month | Category  | Sales Type | Tax Status | Counter | Last Updated");
        $this->line("-" . str_repeat("-", 80));

        foreach ($sequences as $sequence) {
            $this->line(sprintf(
                "%s | %-8s | %-9s | %-9s | %6d | %s",
                $sequence->year_month,
                $sequence->category_type,
                $sequence->sales_type,
                $sequence->tax_status,
                $sequence->counter,
                $sequence->last_updated->format('Y-m-d H:i:s')
            ));
        }

        $this->line("");

        // Show recent invoice numbers
        $this->info("Recent Invoice Numbers:");
        $this->line("Platform  | Invoice Number | Created At");
        $this->line("-" . str_repeat("-", 50));

        // Check each platform
        $platforms = [
            'TikTok' => TiktokFinancialTransaction::class,
            'Shopee' => ShopeeFinancialTransaction::class,
            'Blibli' => BlibliFinancialTransaction::class,
            'Tokopedia' => TokopediaFinancialTransaction::class,
        ];

        foreach ($platforms as $platformName => $modelClass) {
            $recent = $modelClass::orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['no_invoice', 'created_at']);

            foreach ($recent as $transaction) {
                $this->line(sprintf(
                    "%-8s | %-14s | %s",
                    $platformName,
                    $transaction->no_invoice,
                    $transaction->created_at->format('Y-m-d H:i:s')
                ));
            }
        }

        $this->line("");
        $this->info("Summary:");
        $this->line("- Total sequences: " . $sequences->count());
        $this->line("- Highest counter: " . $sequences->max('counter'));
        $this->line("- Lowest counter: " . $sequences->min('counter'));
    }
}

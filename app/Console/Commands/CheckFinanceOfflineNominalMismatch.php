<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;
use App\Models\OfflineSale;
use Illuminate\Support\Facades\DB;

class CheckFinanceOfflineNominalMismatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:check-nominal-mismatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check finance_offlines invoices with nominal mismatch against offline_sales';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Checking for nominal mismatches between finance_offlines and offline_sales...');
        $this->newLine();

        // Get all finance_offlines with their related barang_keluar and offline_sale_items
        $invoices = FinanceOffline::with([
            'barangKeluarItems.offlineSaleItem.offlineSale'
        ])->get();

        $mismatches = [];
        $threshold = 0.01; // Minimum difference to report (in case of rounding)

        foreach ($invoices as $invoice) {
            try {
                // Calculate expected nominal from offline_sale_items
                $expectedNominal = $this->calculateExpectedNominal($invoice);
                $currentNominal = (float) $invoice->nominal;
                $difference = abs($currentNominal - $expectedNominal);

                if ($difference >= $threshold) {
                    // Get related offline_sales for this invoice
                    $offlineSales = $this->getRelatedOfflineSales($invoice);
                    
                    $mismatches[] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'current_nominal' => $currentNominal,
                        'expected_nominal' => $expectedNominal,
                        'difference' => $difference,
                        'offline_sales' => $offlineSales,
                        'tanggal_invoice' => $invoice->tanggal_invoice,
                        'status' => $invoice->status,
                    ];
                }
            } catch (\Exception $e) {
                $this->error("❌ Error processing invoice {$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        // Display results
        if (empty($mismatches)) {
            $this->info('✅ All invoices have correct nominal values!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found " . count($mismatches) . " invoice(s) with nominal mismatches:");
        $this->newLine();

        // Create table
        $headers = [
            'Invoice Number',
            'Current Nominal',
            'Expected Nominal',
            'Difference',
            'Offline Sales (SJ)',
            'Sales Total',
            'Invoice Date',
            'Status'
        ];

        $rows = [];
        $totalDifference = 0;

        foreach ($mismatches as $mismatch) {
            $salesInfo = [];
            $salesTotal = 0;
            
            foreach ($mismatch['offline_sales'] as $sale) {
                $salesInfo[] = $sale['surat_jalan_number'];
                $salesTotal += $sale['total_amount'];
            }

            $rows[] = [
                $mismatch['invoice_number'],
                'Rp ' . number_format($mismatch['current_nominal'], 0, ',', '.'),
                'Rp ' . number_format($mismatch['expected_nominal'], 0, ',', '.'),
                'Rp ' . number_format($mismatch['difference'], 0, ',', '.'),
                implode(', ', array_unique($salesInfo)),
                'Rp ' . number_format($salesTotal, 0, ',', '.'),
                $mismatch['tanggal_invoice']->format('Y-m-d'),
                $mismatch['status']
            ];

            $totalDifference += $mismatch['difference'];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   - Total mismatches: " . count($mismatches));
        $this->info("   - Total difference: Rp " . number_format($totalDifference, 0, ',', '.'));
        $this->newLine();

        // Save detailed report to file
        $reportPath = storage_path('logs/finance_nominal_mismatch_' . date('Y-m-d_H-i-s') . '.txt');
        $this->saveDetailedReport($mismatches, $reportPath);
        $this->info("📄 Detailed report saved to: {$reportPath}");

        return Command::SUCCESS;
    }

    /**
     * Calculate expected nominal from offline_sale_items
     */
    private function calculateExpectedNominal(FinanceOffline $invoice)
    {
        $barangKeluarItems = $invoice->barangKeluarItems;
        
        if ($barangKeluarItems->isEmpty()) {
            return 0;
        }

        // Calculate nominal from unique offline_sale items (same logic as recalculateNominal)
        $nominal = 0;
        $processedSaleItemIds = [];
        
        foreach ($barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem) {
                $saleItemId = $bk->offlineSaleItem->id;
                
                // Only count each sale item once
                if (!in_array($saleItemId, $processedSaleItemIds)) {
                    $subtotal = (float) ($bk->offlineSaleItem->subtotal ?? 0);
                    $nominal += $subtotal;
                    $processedSaleItemIds[] = $saleItemId;
                }
            }
        }

        // Format to 2 decimal places (same as NumberFormatter::formatForDatabase)
        return round($nominal, 2);
    }

    /**
     * Get related offline_sales for an invoice
     */
    private function getRelatedOfflineSales(FinanceOffline $invoice)
    {
        $offlineSales = [];
        
        foreach ($invoice->barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                $sale = $bk->offlineSaleItem->offlineSale;
                
                // Avoid duplicates
                if (!isset($offlineSales[$sale->id])) {
                    $offlineSales[$sale->id] = [
                        'id' => $sale->id,
                        'surat_jalan_number' => $sale->surat_jalan_number,
                        'subtotal' => (float) $sale->subtotal,
                        'tax_amount' => (float) $sale->tax_amount,
                        'total_amount' => (float) $sale->total_amount,
                        'sale_date' => $sale->sale_date,
                    ];
                }
            }
        }

        return array_values($offlineSales);
    }

    /**
     * Save detailed report to file
     */
    private function saveDetailedReport(array $mismatches, string $filePath)
    {
        $content = "FINANCE OFFLINE NOMINAL MISMATCH REPORT\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat("=", 80) . "\n\n";

        foreach ($mismatches as $index => $mismatch) {
            $content .= "Invoice #" . ($index + 1) . "\n";
            $content .= str_repeat("-", 80) . "\n";
            $content .= "Invoice Number     : {$mismatch['invoice_number']}\n";
            $content .= "Invoice ID         : {$mismatch['invoice_id']}\n";
            $content .= "Current Nominal    : Rp " . number_format($mismatch['current_nominal'], 2, ',', '.') . "\n";
            $content .= "Expected Nominal   : Rp " . number_format($mismatch['expected_nominal'], 2, ',', '.') . "\n";
            $content .= "Difference         : Rp " . number_format($mismatch['difference'], 2, ',', '.') . "\n";
            $content .= "Invoice Date       : {$mismatch['tanggal_invoice']->format('Y-m-d')}\n";
            $content .= "Status             : {$mismatch['status']}\n";
            $content .= "\nRelated Offline Sales:\n";

            foreach ($mismatch['offline_sales'] as $sale) {
                $content .= "  - SJ: {$sale['surat_jalan_number']}\n";
                $content .= "    Subtotal  : Rp " . number_format($sale['subtotal'], 2, ',', '.') . "\n";
                $content .= "    Tax Amount: Rp " . number_format($sale['tax_amount'], 2, ',', '.') . "\n";
                $content .= "    Total     : Rp " . number_format($sale['total_amount'], 2, ',', '.') . "\n";
                $content .= "    Sale Date : {$sale['sale_date']->format('Y-m-d')}\n";
                $content .= "\n";
            }

            $content .= "\n";
        }

        $content .= "\n" . str_repeat("=", 80) . "\n";
        $content .= "Total Mismatches: " . count($mismatches) . "\n";
        $totalDifference = array_sum(array_column($mismatches, 'difference'));
        $content .= "Total Difference: Rp " . number_format($totalDifference, 2, ',', '.') . "\n";

        file_put_contents($filePath, $content);
    }
}


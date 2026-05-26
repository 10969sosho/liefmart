<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;
use App\Models\ReturOfflineSale;
use Illuminate\Support\Facades\DB;

class VerifyOfflineInvoiceCalculation extends Command
{
    protected $signature = 'invoice:verify-offline {--invoice-id= : Verify specific invoice ID}';
    protected $description = 'Verify all offline invoice calculations (retur and non-retur)';

    public function handle()
    {
        $invoiceId = $this->option('invoice-id');
        
        if ($invoiceId) {
            $invoices = FinanceOffline::where('id', $invoiceId)->get();
        } else {
            $invoices = FinanceOffline::with([
                'barangKeluarItems',
                'barangKeluarItems.warehouseStock',
                'barangKeluarItems.offlineSaleItem',
                'barangKeluarItems.offlineSaleItem.offlineSale',
                'payments'
            ])->get();
        }

        $this->info("Verifying " . $invoices->count() . " invoices...\n");

        $totalIssues = 0;
        $refundedCount = 0;
        $refundedTotal = 0;
        $nonRefundedCount = 0;
        $nonRefundedTotal = 0;

        foreach ($invoices as $invoice) {
            $firstItem = $invoice->barangKeluarItems->first();
            $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id 
                ? $firstItem->warehouseStock->tax_id 
                : null;

            // Get all unique offline sales from this invoice
            $offlineSales = collect();
            foreach ($invoice->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                    $offlineSales->push($bk->offlineSaleItem->offlineSale);
                }
            }
            $offlineSales = $offlineSales->unique('id');

            // Calculate retur amount
            $returAmount = 0;
            foreach ($offlineSales as $sale) {
                $sale->load('items');
                
                $totalSaleItemsValue = 0;
                foreach ($sale->items as $saleItem) {
                    $itemValue = $this->calculateItemValue($saleItem);
                    $totalSaleItemsValue += $itemValue;
                }
                
                $invoiceItemsValue = 0;
                foreach ($invoice->barangKeluarItems as $bk) {
                    if ($bk->offlineSaleItem && $bk->offlineSaleItem->offline_sale_id == $sale->id) {
                        $saleItem = $bk->offlineSaleItem;
                        $itemValue = $this->calculateItemValue($saleItem);
                        $invoiceItemsValue += $itemValue;
                    }
                }
                
                $proportion = $totalSaleItemsValue > 0 ? ($invoiceItemsValue / $totalSaleItemsValue) : 0;
                $saleDPP = $sale->tax_amount > 0 ? $sale->total_amount : $sale->subtotal;
                
                $saleReturAmount = 0;
                $returs = ReturOfflineSale::where('offline_sale_id', $sale->id)
                    ->where('status', 'selesai')
                    ->get();
                
                foreach ($returs as $retur) {
                    foreach ($retur->details as $detail) {
                        $offlineSaleItem = $detail->offlineSaleItem;
                        if ($offlineSaleItem) {
                            $returItemValue = $this->calculateItemValue($offlineSaleItem);
                            $returProportion = $totalSaleItemsValue > 0 ? ($returItemValue / $totalSaleItemsValue) : 0;
                            $returQtyProportion = $offlineSaleItem->quantity > 0 ? ($detail->qty / $offlineSaleItem->quantity) : 0;
                            $saleReturAmount += $saleDPP * $returProportion * $returQtyProportion;
                        }
                    }
                }
                
                $proportionalRetur = $saleReturAmount * $proportion;
                $returAmount += $proportionalRetur;
            }

            $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);
            $dpp = $invoice->nominal;
            $netDPP = max(0, $dpp - $returAmount);
            $netDPP = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);

            $netPPN = 0;
            if ($taxId == 3) {
                $netDPP11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($netDPP);
                $netPPN = \App\Helpers\NumberFormatter::calculatePPN($netDPP11_12);
                $netPPN = \App\Helpers\NumberFormatter::roundToWholeNumber($netPPN);
            }

            $netTotal = $netDPP + $netPPN;
            $netTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($netTotal);

            $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->payments->sum('amount'));
            $dbStatus = $invoice->status ?? 'unpaid';

            // Check if status matches retur amount
            $isRefunded = ($dbStatus == 'refunded' || $dbStatus == 'retur_full');
            $shouldBeRefunded = ($netTotal == 0 && $returAmount >= $dpp);

            if ($isRefunded) {
                $refundedCount++;
                $refundedTotal += $invoice->nominal;
            } else {
                $nonRefundedCount++;
                $nonRefundedTotal += $netTotal;
            }

            // Report issues
            if ($isRefunded && !$shouldBeRefunded) {
                $this->warn("Invoice {$invoice->invoice_number}: Status is refunded but netTotal is not 0");
                $this->line("  - Nominal: " . number_format($invoice->nominal, 0, ',', '.'));
                $this->line("  - Retur Amount: " . number_format($returAmount, 0, ',', '.'));
                $this->line("  - Net Total: " . number_format($netTotal, 0, ',', '.'));
                $totalIssues++;
            }

            if (!$isRefunded && $shouldBeRefunded) {
                $this->warn("Invoice {$invoice->invoice_number}: Should be refunded but status is {$dbStatus}");
                $this->line("  - Nominal: " . number_format($invoice->nominal, 0, ',', '.'));
                $this->line("  - Retur Amount: " . number_format($returAmount, 0, ',', '.'));
                $this->line("  - Net Total: " . number_format($netTotal, 0, ',', '.'));
                $totalIssues++;
            }

            if ($isRefunded && $invoice->nominal != 0) {
                $this->warn("Invoice {$invoice->invoice_number}: Status is refunded but nominal is not 0");
                $this->line("  - Nominal: " . number_format($invoice->nominal, 0, ',', '.'));
                $totalIssues++;
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Total Invoices: " . $invoices->count());
        $this->info("Refunded Invoices: {$refundedCount} (Total Nominal: Rp " . number_format($refundedTotal, 0, ',', '.') . ")");
        $this->info("Non-Refunded Invoices: {$nonRefundedCount} (Total Net: Rp " . number_format($nonRefundedTotal, 0, ',', '.') . ")");
        $this->info("Issues Found: {$totalIssues}");

        if ($totalIssues == 0) {
            $this->info("\n✓ All invoices are correctly calculated!");
        } else {
            $this->error("\n✗ Found {$totalIssues} issues that need attention.");
        }

        return 0;
    }

    private function calculateItemValue($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        
        $currentTotal = $basePrice * $qty;
        
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }
        
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }
        
        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }
}


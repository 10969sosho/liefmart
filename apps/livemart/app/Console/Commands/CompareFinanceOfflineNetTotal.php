<?php

namespace App\Console\Commands;

use App\Models\FinanceOffline;
use App\Models\ReturOfflineSale;
use Illuminate\Console\Command;

class CompareFinanceOfflineNetTotal extends Command
{
    protected $signature = 'finance:compare-offline-nettotal {--limit=50 : Max rows to show} {--invoice-id= : Compare specific invoice ID}';
    protected $description = 'Compare offline invoice net total calculation: legacy vs current (after retur + PPN)';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $invoiceId = $this->option('invoice-id');

        $query = FinanceOffline::query()->with([
            'barangKeluarItems.warehouseStock',
            'barangKeluarItems.offlineSaleItem',
            'barangKeluarItems.offlineSaleItem.offlineSale',
            'payments',
        ]);

        if ($invoiceId) {
            $query->where('id', $invoiceId);
        }

        $invoices = $query->get();

        $rows = [];
        $diffCount = 0;
        $compared = 0;

        foreach ($invoices as $invoice) {
            $compared++;
            try {
                $newNetTotal = $this->calculateNetTotalCurrent($invoice);
                $legacyNetTotal = $this->calculateNetTotalLegacy($invoice);
                $diff = $newNetTotal - $legacyNetTotal;

                if (abs($diff) <= 1) {
                    continue;
                }

                $diffCount++;

                if (count($rows) >= $limit) {
                    continue;
                }

                $taxId = $this->getTaxId($invoice);
                $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->payments->sum('amount'));
                $rows[] = [
                    $invoice->id,
                    $invoice->invoice_number,
                    $invoice->status ?? 'unpaid',
                    $taxId ?? '-',
                    number_format((float) $invoice->nominal, 0, ',', '.'),
                    number_format($newNetTotal, 0, ',', '.'),
                    number_format($legacyNetTotal, 0, ',', '.'),
                    number_format($diff, 0, ',', '.'),
                    number_format($totalPaid, 0, ',', '.'),
                ];
            } catch (\Throwable $e) {
                if (count($rows) < $limit) {
                    $rows[] = [
                        $invoice->id,
                        $invoice->invoice_number,
                        $invoice->status ?? 'unpaid',
                        '-',
                        number_format((float) $invoice->nominal, 0, ',', '.'),
                        'ERR',
                        'ERR',
                        $e->getMessage(),
                        '-',
                    ];
                }
            }
        }

        $this->info('Compared invoices: ' . $compared);
        $this->info('Invoices with diff > 1: ' . $diffCount);
        $this->newLine();

        $this->table(
            ['ID', 'Invoice', 'Status', 'Tax', 'Nominal(DB)', 'NetTotal(Current)', 'NetTotal(Legacy)', 'Diff', 'TotalPaid'],
            $rows
        );

        $this->newLine();
        $this->line('Legend:');
        $this->line('- Current: DPP original (qty+retur) - retur(discount) + PPN (PKP)');
        $this->line('- Legacy : nominal(DB) - retur(proportional) + PPN (PKP)');

        return Command::SUCCESS;
    }

    private function getTaxId(FinanceOffline $invoice): ?int
    {
        $firstItem = $invoice->barangKeluarItems->first();
        return $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id
            ? (int) $firstItem->warehouseStock->tax_id
            : null;
    }

    private function calculateNetTotalCurrent(FinanceOffline $invoice): int
    {
        $taxId = $this->getTaxId($invoice);

        $firstItem = $invoice->barangKeluarItems->first();
        $offlineSale = ($firstItem && $firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale)
            ? $firstItem->offlineSaleItem->offlineSale
            : null;

        $returAmount = 0;
        $dppOriginal = 0;

        $returs = collect();
        if ($offlineSale) {
            $returs = \App\Models\ReturOfflineSale::where('offline_sale_id', $offlineSale->id)
                ->where('status', 'selesai')
                ->with('details.offlineSaleItem')
                ->get();

            $processedSaleItemIds = [];
            foreach ($invoice->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem) {
                    $osi = $bk->offlineSaleItem;
                    $saleItemId = $osi->id;

                    if (!in_array($saleItemId, $processedSaleItemIds)) {
                        $currentQty = (float) ($osi->quantity ?? 0);
                        $currentSubtotal = (float) ($osi->subtotal ?? 0);

                        $returnedQty = 0;
                        foreach ($returs as $retur) {
                            foreach ($retur->details as $detail) {
                                if ($detail->offline_sale_item_id == $osi->id) {
                                    $returnedQty += (float) ($detail->qty ?? 0);
                                }
                            }
                        }

                        $originalQty = $currentQty + $returnedQty;

                        if ($currentQty > 0) {
                            $subtotalPerUnit = $currentSubtotal / $currentQty;
                            $originalSubtotal = $subtotalPerUnit * $originalQty;
                        } else {
                            $originalSubtotal = (float) ($osi->unit_price ?? 0) * $originalQty;
                        }

                        $dppOriginal += $originalSubtotal;
                        $processedSaleItemIds[] = $saleItemId;
                    }
                }
            }

            foreach ($returs as $retur) {
                foreach ($retur->details as $detail) {
                    $offlineSaleItem = $detail->offlineSaleItem;
                    if ($offlineSaleItem) {
                        $qtyRetur = (float) ($detail->qty ?? 0);
                        $basePrice = (float) ($offlineSaleItem->unit_price ?? 0);
                        $currentTotal = $basePrice * $qtyRetur;

                        for ($i = 1; $i <= 5; $i++) {
                            $percentField = "discount_percent_" . $i;
                            $discountPercent = (float) ($offlineSaleItem->$percentField ?? 0);
                            if ($discountPercent > 0) {
                                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
                            }
                        }

                        for ($i = 1; $i <= 5; $i++) {
                            $amountField = "discount_amount_" . $i;
                            $discountAmount = (float) ($offlineSaleItem->$amountField ?? 0);
                            if ($discountAmount > 0) {
                                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qtyRetur);
                            }
                        }

                        $returAmount += \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
                    }
                }
            }
        } else {
            $dppOriginal = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->nominal);
        }

        $dppOriginal = \App\Helpers\NumberFormatter::roundToWholeNumber($dppOriginal);
        $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);

        $netDPP = max(0, $dppOriginal - $returAmount);
        $netDPP = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);

        $netPPN = 0;
        if ($taxId == 3) {
            $netDPP11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($netDPP);
            $netPPN = \App\Helpers\NumberFormatter::calculatePPN($netDPP11_12);
            $netPPN = \App\Helpers\NumberFormatter::roundToWholeNumber($netPPN);
        }

        return \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP + $netPPN);
    }

    private function calculateNetTotalLegacy(FinanceOffline $invoice): int
    {
        $taxId = $this->getTaxId($invoice);

        $offlineSales = collect();
        foreach ($invoice->barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                $offlineSales->push($bk->offlineSaleItem->offlineSale);
            }
        }
        $offlineSales = $offlineSales->unique('id');

        $returAmount = 0;
        foreach ($offlineSales as $sale) {
            $sale->loadMissing('items');

            $totalSaleItemsValue = 0;
            foreach ($sale->items as $saleItem) {
                $totalSaleItemsValue += $this->calculateItemValue($saleItem);
            }

            $invoiceItemsValue = 0;
            foreach ($invoice->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem && $bk->offlineSaleItem->offline_sale_id == $sale->id) {
                    $invoiceItemsValue += $this->calculateItemValue($bk->offlineSaleItem);
                }
            }

            $proportion = $totalSaleItemsValue > 0 ? ($invoiceItemsValue / $totalSaleItemsValue) : 0;
            $saleDPP = ((float) ($sale->tax_amount ?? 0)) > 0 ? (float) $sale->total_amount : (float) $sale->subtotal;

            $saleReturAmount = 0;
            $returs = ReturOfflineSale::where('offline_sale_id', $sale->id)
                ->where('status', 'selesai')
                ->with('details.offlineSaleItem')
                ->get();

            foreach ($returs as $retur) {
                foreach ($retur->details as $detail) {
                    $offlineSaleItem = $detail->offlineSaleItem;
                    if ($offlineSaleItem) {
                        $returItemValue = $this->calculateItemValue($offlineSaleItem);
                        $returProportion = $totalSaleItemsValue > 0 ? ($returItemValue / $totalSaleItemsValue) : 0;
                        $returQtyProportion = ((float) ($offlineSaleItem->quantity ?? 0)) > 0 ? ((float) ($detail->qty ?? 0) / (float) $offlineSaleItem->quantity) : 0;
                        $saleReturAmount += $saleDPP * $returProportion * $returQtyProportion;
                    }
                }
            }

            $returAmount += $saleReturAmount * $proportion;
        }

        $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);
        $dpp = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->nominal);
        $netDPP = max(0, $dpp - $returAmount);
        $netDPP = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);

        $netPPN = 0;
        if ($taxId == 3) {
            $netDPP11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($netDPP);
            $netPPN = \App\Helpers\NumberFormatter::calculatePPN($netDPP11_12);
            $netPPN = \App\Helpers\NumberFormatter::roundToWholeNumber($netPPN);
        }

        return \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP + $netPPN);
    }

    private function calculateItemValue($item): float
    {
        $basePrice = (float) ($item->unit_price ?? 0);
        $qty = (float) ($item->quantity ?? 0);

        $currentTotal = $basePrice * $qty;

        for ($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float) ($item->$percentField ?? 0);
            if ($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float) ($item->$amountField ?? 0);
            if ($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }

        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }
}

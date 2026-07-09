<?php

namespace App\Exports\Handlers\GrossProfit;

use App\Exports\GrossProfitOfflineExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Helpers\NumberFormatter;

class GrossProfitOfflineHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'gross_profit_offline';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $selectedInvoice = $filters['invoice_number'] ?? null;
        $selectedPO = $filters['po_number'] ?? null;
        $selectedSKU = $filters['sku'] ?? null;
        $selectedCustomer = $filters['customer_id'] ?? null;

        // Build query
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->where('status', 'paid')
            ->with([
                'items',
                'items.product',
                'items.warehouseStock',
                'items.warehouseStock.penerimaanDetail',
                'items.barangKeluar.financeOffline',
                'customerInfo',
            ]);

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }

        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }

        if ($selectedInvoice) {
            $query->whereHas('items.barangKeluar.financeOffline', function ($q) use ($selectedInvoice) {
                $q->where('invoice_number', 'like', '%' . $selectedInvoice . '%');
            });
        }

        if ($selectedPO) {
            $query->where('surat_jalan_number', 'like', '%' . $selectedPO . '%');
        }

        if ($selectedSKU) {
            $query->whereHas('items.product', function ($q) use ($selectedSKU) {
                $q->where('sku', 'like', '%' . $selectedSKU . '%');
            });
        }

        $sales = $query->get();

        // Process sales data for profit calculation
        $profitData = $sales->map(function ($sale) {
            $totalPaymentAmount = 0;
            $paymentDate = null;

            $financeOffline = $sale->finance_offline;
            if ($financeOffline && $financeOffline->isNotEmpty()) {
                $totalPaymentAmount = $financeOffline->sum(function ($invoice) {
                    return $invoice->payments ? $invoice->payments->sum('amount') : 0;
                });
                $paymentDate = $financeOffline->first()?->payments?->first()?->payment_date ?? $sale->sale_date;
            }

            return $sale->items->map(function ($item) use ($sale, $totalPaymentAmount, $paymentDate, $financeOffline) {
                $costPrice = 0;
                if ($item->warehouseStock && $item->warehouseStock->penerimaanDetail) {
                    $penerimaanDetail = $item->warehouseStock->penerimaanDetail;
                    $subtotal = $penerimaanDetail->subtotal ?? 0;
                    $diskon = $penerimaanDetail->diskon ?? 0;
                    $qty = $penerimaanDetail->qty ?? 1;

                    if ($qty > 0) {
                        $costPrice = ($subtotal - $diskon) / $qty;
                    }
                }

                $isPKP = $item->warehouseStock && $item->warehouseStock->tax_id == 3;

                $sellingPriceAfterDiscount = $this->calculatePriceAfterDiscountsPerUnit($item);
                $sellingPrice = $item->unit_price;
                $profitPerUnit = $sellingPriceAfterDiscount - $costPrice;
                $totalCostPrice = $costPrice * $item->quantity;

                $itemValue = $item->subtotal;
                $totalSaleValue = $sale->total_amount;
                $proportionalPayment = $totalSaleValue > 0 ? ($itemValue / $totalSaleValue) * $totalPaymentAmount : 0;
                $profitPerInvoice = $proportionalPayment - $totalCostPrice;
                $paymentPerProduct = $proportionalPayment;

                $paymentPerInvoiceWithoutPPN = $isPKP ? ($totalPaymentAmount / 1.11) : $totalPaymentAmount;
                $paymentPerInvoiceWithoutPPN = NumberFormatter::roundToWholeNumber($paymentPerInvoiceWithoutPPN);

                $paymentPerProductWithoutPPN = $isPKP ? ($proportionalPayment / 1.11) : $proportionalPayment;
                $paymentPerProductWithoutPPN = NumberFormatter::roundToTwoDecimals($paymentPerProductWithoutPPN);

                $paymentPerPCSWithoutPPN = $item->quantity > 0 ? ($paymentPerProductWithoutPPN / $item->quantity) : 0;
                $paymentPerPCSWithoutPPN = NumberFormatter::roundToTwoDecimals($paymentPerPCSWithoutPPN);

                $profitPerProduct = $paymentPerProductWithoutPPN - $totalCostPrice;

                $marginPerUnit = $sellingPrice > 0 ? (($profitPerUnit / $sellingPrice) * 100) : 0;
                $marginPerProduct = $paymentPerProductWithoutPPN > 0 ? (($profitPerProduct / $paymentPerProductWithoutPPN) * 100) : 0;
                $marginPerInvoice = $paymentPerProduct > 0 ? (($profitPerInvoice / $paymentPerProduct) * 100) : 0;

                return [
                    'payment_date' => $paymentDate,
                    'sale_date' => $sale->sale_date,
                    'customer_name' => $sale->customerInfo ? $sale->customerInfo->name : ($sale->customer_name ?? '-'),
                    'po_number' => $sale->surat_jalan_number,
                    'invoice_number' => $financeOffline && $financeOffline->isNotEmpty() ? $financeOffline->first()->invoice_number : '-',
                    'product_name' => $item->product ? $item->product->name : 'Unknown Product',
                    'quantity' => $item->quantity,
                    'sku' => $item->product ? $item->product->sku : '-',
                    'payment_per_invoice' => $totalPaymentAmount,
                    'payment_per_invoice_without_ppn' => $paymentPerInvoiceWithoutPPN,
                    'payment_per_product' => $paymentPerProduct,
                    'payment_per_product_without_ppn' => $paymentPerProductWithoutPPN,
                    'payment_per_pcs_without_ppn' => $paymentPerPCSWithoutPPN,
                    'is_pkp' => $isPKP,
                    'cost_price' => $costPrice,
                    'total_cost_price' => $totalCostPrice,
                    'profit_per_unit' => $profitPerUnit,
                    'profit_per_product' => $profitPerProduct,
                    'profit_per_invoice' => $profitPerInvoice,
                    'margin_per_unit' => $marginPerUnit,
                    'margin_per_product' => $marginPerProduct,
                    'margin_per_invoice' => $marginPerInvoice,
                ];
            });
        })->flatten(1);

        // Sort by sale_date ascending
        $profitData = $profitData->sortBy(function ($item) {
            return $item['sale_date'] ? \Carbon\Carbon::parse($item['sale_date'])->timestamp : 0;
        })->values();

        $filename = 'Gross_Profit_Offline_' . $startDate . '_to_' . $endDate . '.xlsx';

        return [
            'export' => new GrossProfitOfflineExport($profitData),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }

    private function calculatePriceAfterDiscountsPerUnit($item): float
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);

        if ($qty == 0) {
            return $basePrice;
        }

        $currentTotal = $basePrice * $qty;

        for ($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if ($discountPercent > 0) {
                $currentTotal = NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if ($discountAmount > 0) {
                $currentTotal = NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }

        return NumberFormatter::roundToTwoDecimals($currentTotal / $qty);
    }
}

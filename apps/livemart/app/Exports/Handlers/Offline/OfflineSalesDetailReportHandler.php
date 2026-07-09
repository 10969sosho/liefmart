<?php

namespace App\Exports\Handlers\Offline;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\OfflineSalesDetailReportExport;
use App\Models\OfflineSale;
use App\Models\ReturOfflineSaleDetail;

class OfflineSalesDetailReportHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'offline_sales_detail_report';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $selectedCustomer = $filters['customer_id'] ?? null;
        $selectedProduct = $filters['product_id'] ?? null;

        $query = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'items.product', 'customerInfo']);

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }

        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }

        $sales = $query->get();

        $sortBy = $filters['sort'] ?? 'date_newest';

        switch ($sortBy) {
            case 'value_highest':
                $sales = $sales->sortByDesc('total_amount');
                break;
            case 'value_lowest':
                $sales = $sales->sortBy('total_amount');
                break;
            case 'date_newest':
                $sales = $sales->sortByDesc('sale_date');
                break;
            case 'date_oldest':
                $sales = $sales->sortBy('sale_date');
                break;
            default:
                $sales = $sales->sortByDesc('sale_date');
        }

        // Calculate total volume and value after returns
        $sales = $sales->map(function ($sale) {
            $sale->total_volume = $sale->items->sum('quantity');

            $qtyRetur = 0;
            $valueAfterReturns = 0;

            foreach ($sale->items as $item) {
                $itemQtyRetur = ReturOfflineSaleDetail::where('offline_sale_item_id', $item->id)
                    ->whereHas('returOfflineSale', function ($q) {
                        $q->where('status', 'selesai');
                    })
                    ->sum('qty');

                $itemQtyRetur = (float) $itemQtyRetur;
                $qtyRetur += $itemQtyRetur;

                $itemTotalAfterDiscounts = $this->calculateTotalAfterDiscounts($item);
                $valueAfterReturns += $itemTotalAfterDiscounts;
            }

            $sale->total_retur_qty = $qtyRetur;
            $sale->total_volume_after_returns = $sale->total_volume;
            $sale->value_after_returns = $valueAfterReturns;

            return $sale;
        });

        // Calculate overall summary
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('value_after_returns'),
            'total_volume' => $sales->sum('total_volume_after_returns'),
        ];

        $summary['avg_order_value'] = $summary['total_orders'] > 0
            ? $summary['total_value'] / $summary['total_orders']
            : 0;

        $summary['avg_order_volume'] = $summary['total_orders'] > 0
            ? $summary['total_volume'] / $summary['total_orders']
            : 0;

        $filename = 'laporan-detail-penjualan-offline-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new OfflineSalesDetailReportExport($sales, $summary, $startDate, $endDate, $selectedCustomer, $selectedProduct),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }

    /**
     * Calculate total value after all cascading discounts
     */
    private function calculateTotalAfterDiscounts($item)
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

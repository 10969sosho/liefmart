<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\GenericCollectionExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Queries\Analytics\Sales\DiscountAnalysisQuery;
use Illuminate\Support\Facades\DB;

class DiscountAnalysisReportHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'discount_analysis_report';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        $queryFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'min_discount' => $filters['min_discount'] ?? null,
            'max_discount' => $filters['max_discount'] ?? null,
            'sort' => $filters['sort'] ?? 'discount_highest',
        ];

        // Get all data (no pagination for export)
        $page = 1;
        $perPage = 1000000;
        $query = DiscountAnalysisQuery::build($queryFilters, $perPage, $page);
        $results = DB::select($query);

        // Get summary
        $summaryQuery = DiscountAnalysisQuery::buildSummary($queryFilters);
        $summaryResult = DB::selectOne($summaryQuery);

        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_items' => (int)($summaryResult->total_items ?? 0),
            'total_before_discount' => (float)($summaryResult->total_before_discount ?? 0),
            'total_after_discount' => (float)($summaryResult->total_after_discount ?? 0),
            'total_discount' => (float)($summaryResult->total_discount ?? 0),
            'avg_discount_percentage' => (float)($summaryResult->avg_discount_percentage ?? 0),
            'avg_item_discount_percentage' => (float)($summaryResult->avg_item_discount_percentage ?? 0),
        ];

        $items = collect($results)->map(function ($row) {
            return [
                'No' => '',
                'Order Number' => $row->order_number,
                'Tanggal' => $row->tanggal,
                'Platform' => $row->platform_name ?? 'N/A',
                'Product' => $row->platform_product_name ?? '-',
                'Original Price' => (float)$row->original_price,
                'Price After Discount' => (float)$row->price_after_discount,
                'Qty' => (int)$row->quantity,
                'Total Before Disc' => (float)$row->total_before_discount,
                'Total After Disc' => (float)$row->total_after_discount,
                'Discount Amount' => (float)$row->item_discount,
                'Disc %' => round((float)$row->discount_percentage, 1) . '%',
            ];
        });

        $headings = [
            'No', 'Order Number', 'Tanggal', 'Platform', 'Product',
            'Original Price', 'Price After Discount', 'Qty',
            'Total Before Disc', 'Total After Disc', 'Discount Amount', 'Disc %',
        ];

        $filename = 'analisis-diskon-' . date('Y-m-d') . '.xlsx';

        $index = 1;
        $exportData = $items->map(function ($item) use (&$index) {
            $item['No'] = $index++;
            return $item;
        });

        return [
            'export' => new GenericCollectionExport($exportData, $headings),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

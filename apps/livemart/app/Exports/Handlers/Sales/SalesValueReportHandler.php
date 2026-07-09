<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\GenericCollectionExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Queries\Analytics\Sales\SalesValueQuery;
use Illuminate\Support\Facades\DB;

class SalesValueReportHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_value_report';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        $queryFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
        ];

        // Execute query using SalesValueQuery
        $query = SalesValueQuery::build($queryFilters);
        $results = DB::select($query);

        // Get summary
        $summaryQuery = SalesValueQuery::buildSummary($queryFilters);
        $summaryResult = DB::selectOne($summaryQuery);

        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];

        $orders = collect($results)->map(function ($row) {
            return [
                'No' => '',
                'Order Number' => $row->order_number,
                'Tanggal' => $row->tanggal,
                'Platform' => $row->platform_name ?? 'N/A',
                'Total Value (Rp)' => (float)$row->total_value,
                'Total Volume (pcs)' => (float)$row->total_volume,
            ];
        });

        $filename = 'laporan-nilai-penjualan-' . date('Y-m-d') . '.xlsx';

        $headings = ['No', 'Order Number', 'Tanggal', 'Platform', 'Total Value (Rp)', 'Total Volume (pcs)'];

        // Add numbering
        $index = 1;
        $exportData = $orders->map(function ($item) use (&$index) {
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

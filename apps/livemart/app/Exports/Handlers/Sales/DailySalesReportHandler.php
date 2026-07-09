<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\GenericCollectionExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Queries\Analytics\Sales\DailySalesQuery;
use Illuminate\Support\Facades\DB;

class DailySalesReportHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'daily_sales_report';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        // Apply quick date range if set
        if (isset($filters['quick_range'])) {
            $range = $filters['quick_range'];
            $endDate = now()->format('Y-m-d');
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }

        $queryFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
        ];

        // Execute query using DailySalesQuery
        $query = DailySalesQuery::build($queryFilters);
        $results = DB::select($query);

        // Get summary
        $summaryQuery = DailySalesQuery::buildSummary($queryFilters);
        $summaryResult = DB::selectOne($summaryQuery);

        $summary = [
            'total_days' => (int)($summaryResult->total_days ?? 0),
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_orders_per_day' => (float)($summaryResult->avg_orders_per_day ?? 0),
            'avg_value_per_day' => (float)($summaryResult->avg_value_per_day ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];

        $dailyData = collect($results)->map(function ($row) {
            return [
                'No' => '',
                'Tanggal' => $row->sale_date,
                'Jumlah Order' => (int)$row->total_orders,
                'Total Value (Rp)' => (float)$row->total_value,
                'Total Volume (pcs)' => (float)$row->total_volume,
                'Rata-rata/Order (Rp)' => (float)$row->avg_order_value,
            ];
        });

        $filename = 'laporan-penjualan-harian-' . date('Y-m-d') . '.xlsx';

        $headings = ['No', 'Tanggal', 'Jumlah Order', 'Total Value (Rp)', 'Total Volume (pcs)', 'Rata-rata/Order (Rp)'];

        // Add numbering
        $index = 1;
        $exportData = $dailyData->map(function ($item) use (&$index) {
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

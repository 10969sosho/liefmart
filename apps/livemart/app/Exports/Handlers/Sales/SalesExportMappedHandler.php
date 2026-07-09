<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesExportMappedExport;

class SalesExportMappedHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_export_mapped';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $sortBy = $filters['sort'] ?? 'date_newest';

        // Build filters array for SQL query
        $queryFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'min_qty' => $filters['min_qty'] ?? null,
            'max_qty' => $filters['max_qty'] ?? null,
            'sort' => $sortBy,
        ];

        $filename = 'sales-detail-mapped-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesExportMappedExport($queryFilters),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

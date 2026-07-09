<?php

namespace App\Exports\Handlers\GrossProfit;

use App\Exports\SalesByPlatformProductExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Queries\SalesByPlatformProductQuery;

class SalesByPlatformProductHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_platform_product';
    }

    public function handle(array $filters): array
    {
        $request = new \Illuminate\Http\Request($filters);
        $query = new SalesByPlatformProductQuery($request);

        $platformProductRows = $query->get();
        $summary = $query->getSummary();

        $startDate = $filters['start_date'] ?? date('Y-m-d');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $selectedPlatform = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'revenue_highest';

        $filename = 'laporan-penjualan-platform-produk-' . date('Y-m-d') . '.xlsx';

        $exportFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $selectedPlatform,
            'sort' => $sortBy,
        ];

        return [
            'export' => new SalesByPlatformProductExport($platformProductRows, $summary, $exportFilters),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

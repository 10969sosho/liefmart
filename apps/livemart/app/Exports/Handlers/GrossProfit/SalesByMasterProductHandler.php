<?php

namespace App\Exports\Handlers\GrossProfit;

use App\Exports\SalesByMasterProductExport;
use App\Exports\Handlers\ExportHandlerInterface;
use App\Queries\SalesByMasterProductQuery;

class SalesByMasterProductHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_master_product';
    }

    public function handle(array $filters): array
    {
        $request = new \Illuminate\Http\Request($filters);
        $query = new SalesByMasterProductQuery($request);

        $productRows = $query->get();
        $summary = $query->getSummary();
        $queryFilters = $query->getFilters();
        $queryFilters['sort'] = $filters['sort'] ?? 'revenue_highest';

        $filename = 'laporan-penjualan-master-produk-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesByMasterProductExport($productRows, $summary, $queryFilters),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

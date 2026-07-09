<?php

namespace App\Exports\Handlers\GrossProfit;

use App\Exports\Handlers\ExportHandlerInterface;

class SalesByMasterProductSpecialHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_master_product_special';
    }

    public function handle(array $filters): array
    {
        $request = new \Illuminate\Http\Request($filters);

        // Check if special query class exists
        $queryClass = 'App\Queries\SalesByMasterProductSpecialQuery';
        if (!class_exists($queryClass)) {
            throw new \RuntimeException('SalesByMasterProductSpecialQuery class not found');
        }

        $query = new $queryClass($request);
        $productRows = $query->get();
        $summary = $query->getSummary();

        $filename = 'laporan-penjualan-master-produk-special-' . date('Y-m-d') . '.xlsx';

        // Check if export class exists
        $exportClass = 'App\Exports\SalesByMasterProductSpecialExport';
        if (!class_exists($exportClass)) {
            throw new \RuntimeException('SalesByMasterProductSpecialExport class not found');
        }

        return [
            'export' => new $exportClass($productRows, $summary, $filters),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

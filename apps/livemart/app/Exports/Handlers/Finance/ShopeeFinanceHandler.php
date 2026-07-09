<?php

namespace App\Exports\Handlers\Finance;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\ShopeeFinanceAnalyticsExport;

class ShopeeFinanceHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'finance_shopee';
    }

    public function handle(array $filters): array
    {
        $export = new ShopeeFinanceAnalyticsExport($filters);

        $filename = 'shopee_finance_analytics_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return [
            'export' => $export,
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

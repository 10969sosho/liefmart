<?php

namespace App\Exports\Handlers\Finance;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\Shopee2FinanceAnalyticsExport;
use Illuminate\Http\Request;

class Shopee2FinanceHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'finance_shopee2';
    }

    public function handle(array $filters): array
    {
        $export = new Shopee2FinanceAnalyticsExport(new Request($filters));

        $filename = 'shopee2_finance_analytics_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return [
            'export' => $export,
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

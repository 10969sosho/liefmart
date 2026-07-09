<?php

namespace App\Exports\Handlers\Finance;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\Tiktok2FinanceAnalyticsExport;
use Illuminate\Http\Request;

class Tiktok2FinanceHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'finance_tiktok2';
    }

    public function handle(array $filters): array
    {
        $export = new Tiktok2FinanceAnalyticsExport(new Request($filters));

        $filename = 'tiktok2_finance_analytics_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return [
            'export' => $export,
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

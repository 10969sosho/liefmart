<?php

namespace App\Exports\Handlers\Finance;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\TiktokFinanceAnalyticsExport;

class TiktokFinanceHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'finance_tiktok';
    }

    public function handle(array $filters): array
    {
        $export = new TiktokFinanceAnalyticsExport($filters);

        $filename = 'tiktok_finance_analytics_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return [
            'export' => $export,
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Export Handlers
    |--------------------------------------------------------------------------
    |
    | Maps export types to their handler classes.
    | Each handler must implement App\Exports\Handlers\ExportHandlerInterface.
    |
    */

    // Gross Profit
    'gross_profit_offline' => App\Exports\Handlers\GrossProfit\GrossProfitOfflineHandler::class,
    'sales_by_master_product' => App\Exports\Handlers\GrossProfit\SalesByMasterProductHandler::class,
    'sales_by_master_product_special' => App\Exports\Handlers\GrossProfit\SalesByMasterProductSpecialHandler::class,
    'sales_by_platform_product' => App\Exports\Handlers\GrossProfit\SalesByPlatformProductHandler::class,

    // Online Sales
    'sales_value_report' => App\Exports\Handlers\Sales\SalesValueReportHandler::class,
    'sales_volume_report' => App\Exports\Handlers\Sales\SalesVolumeReportHandler::class,
    'sales_by_platform' => App\Exports\Handlers\Sales\SalesByPlatformHandler::class,
    'sales_by_status_day' => App\Exports\Handlers\Sales\SalesByStatusDayHandler::class,
    'sales_by_day_of_week' => App\Exports\Handlers\Sales\SalesByDayOfWeekHandler::class,
    'sales_by_date_number' => App\Exports\Handlers\Sales\SalesByDateNumberHandler::class,
    'sales_detail_report' => App\Exports\Handlers\Sales\SalesDetailReportHandler::class,
    'monthly_sales_summary' => App\Exports\Handlers\Sales\MonthlySalesSummaryHandler::class,
    'daily_sales_report' => App\Exports\Handlers\Sales\DailySalesReportHandler::class,
    'discount_analysis_report' => App\Exports\Handlers\Sales\DiscountAnalysisReportHandler::class,
    'single_item_report' => App\Exports\Handlers\Sales\SingleItemReportHandler::class,
    'multiple_item_report' => App\Exports\Handlers\Sales\MultipleItemReportHandler::class,
    'internal_product_sales' => App\Exports\Handlers\Sales\InternalProductSalesHandler::class,
    'sales_export_mapped' => App\Exports\Handlers\Sales\SalesExportMappedHandler::class,
    'sales_by_master_product' => App\Exports\Handlers\GrossProfit\SalesByMasterProductHandler::class,

    // Products
    'produk_platform_terlaris' => App\Exports\Handlers\Product\ProdukPlatformTerlarisHandler::class,
    'produk_internal_terlaris' => App\Exports\Handlers\Product\ProdukInternalTerlarisHandler::class,

    // Offline
    'offline_monthly_sales_summary' => App\Exports\Handlers\Offline\OfflineMonthlySalesSummaryHandler::class,
    'offline_sales_by_customer' => App\Exports\Handlers\Offline\OfflineSalesByCustomerHandler::class,
    'offline_sales_by_product' => App\Exports\Handlers\Offline\OfflineSalesByProductHandler::class,
    'offline_sales_detail_report' => App\Exports\Handlers\Offline\OfflineSalesDetailReportHandler::class,

    // Finance
    'finance_shopee' => App\Exports\Handlers\Finance\ShopeeFinanceHandler::class,
    'finance_shopee2' => App\Exports\Handlers\Finance\Shopee2FinanceHandler::class,
    'finance_tiktok' => App\Exports\Handlers\Finance\TiktokFinanceHandler::class,
    'finance_tiktok2' => App\Exports\Handlers\Finance\Tiktok2FinanceHandler::class,
];

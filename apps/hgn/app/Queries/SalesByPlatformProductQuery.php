<?php

namespace App\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesByPlatformProductQuery
{
    protected $request;
    protected $perPage = 20;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function paginate($perPage = null)
    {
        if ($perPage) {
            $this->perPage = $perPage;
        }

        $page = $this->request->input('page', 1);
        $offset = ($page - 1) * $this->perPage;

        // Get total count
        $totalCount = $this->getTotalCount();

        // Get paginated data
        $data = $this->getPaginatedData($offset, $this->perPage);

        // Create paginator manually
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $data,
            $totalCount,
            $this->perPage,
            $page,
            ['path' => $this->request->url(), 'query' => $this->request->query()]
        );
    }

    public function get()
    {
        // Get all data without pagination (for export)
        $sql = $this->buildQuery(false);
        $results = DB::select($sql);
        
        // Convert objects to arrays for compatibility
        return array_map(function($row) {
            return (array) $row;
        }, $results);
    }

    public function getSummary()
    {
        $filters = $this->getFilters();
        
        // Build summary query using aggregates - separate from table query
        // This ensures consistency with SalesByMasterProductQuery
        $sql = $this->buildSummaryQuery($filters);
        $result = DB::selectOne($sql);
        
        $totalRevenue = (float) ($result->total_revenue ?? 0);
        $totalCapital = (float) ($result->total_capital ?? 0);
        $totalQuantity = (float) ($result->total_quantity ?? 0);
        $totalPlatformProducts = (int) ($result->total_platform_products ?? 0);
        
        // Calculate revenue without PPN from total revenue (consistent with SalesByMasterProductQuery)
        $totalRevenueWithoutPPN = $totalRevenue / 1.11;
        $totalGrossProfit = $totalRevenueWithoutPPN - $totalCapital;
        
        return [
            'total_platform_products' => $totalPlatformProducts,
            'total_platform_products_after_returns' => $totalPlatformProducts, // For now, same value
            'total_rows' => $totalPlatformProducts,
            'total_revenue' => $totalRevenue,
            'total_revenue_without_ppn' => $totalRevenueWithoutPPN,
            'total_capital' => $totalCapital,
            'total_gross_profit' => $totalGrossProfit,
            'total_quantity' => $totalQuantity,
            'profit_margin' => $totalRevenueWithoutPPN > 0 ? ($totalGrossProfit / $totalRevenueWithoutPPN) * 100 : 0,
        ];
    }

    protected function getTotalCount()
    {
        $sql = $this->buildQuery(true);
        // buildCountQuery already returns "SELECT COUNT(*) as total", so just execute it directly
        $result = DB::selectOne($sql);
        return $result->total ?? 0;
    }

    protected function getPaginatedData($offset, $limit)
    {
        $sql = $this->buildQuery(false);
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        $results = DB::select($sql);
        
        // Convert objects to arrays for compatibility with Blade views
        return array_map(function($row) {
            return (array) $row;
        }, $results);
    }

    protected function buildQuery($countOnly = false)
    {
        $filters = $this->getFilters();

        if ($countOnly) {
            return $this->buildCountQuery($filters);
        }

        return $this->buildFullQuery($filters);
    }

    protected function buildCountQuery($filters)
    {
        $baseCTE = $this->buildBaseCTE($filters);
        $calcCTE = $this->buildCalcCTE();
        
        return "
            WITH base_data AS ({$baseCTE}),
            calculated_data AS ({$calcCTE})
            SELECT COUNT(*) as total
            FROM calculated_data
        ";
    }

    protected function buildFullQuery($filters)
    {
        $baseCTE = $this->buildBaseCTE($filters);
        $calcCTE = $this->buildCalcCTE();
        $finalSelect = $this->finalSelect();

        return "
            WITH base_data AS ({$baseCTE}),
            calculated_data AS ({$calcCTE})
            {$finalSelect}
        ";
    }

    /**
     * Build summary query using aggregates (separate from table query)
     * IMPORTANT: total_revenue uses total_saldo_masuk directly from financial_summary
     * to match the actual payment amount, not the proportional revenue allocation
     * This ensures consistency with SalesByMasterProductQuery
     */
    protected function buildSummaryQuery($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $platformId = $filters['platform_id'];
        $orderNumber = $filters['order_number'];
        $search = $filters['search'];

        // Build financial_summary CTE (same as SalesByMasterProductQuery)
        $platformCondition = '';
        if ($platformId) {
            $platformCondition = "AND o.platform_id = {$platformId}";
        }

        $financialCTE = "
            financial_summary AS (
                SELECT 
                    af.no_order,
                    SUM(af.saldo_masuk) as total_saldo_masuk,
                    SUM(COALESCE(af.nominal_fix, 0)) as total_nominal_fix,
                    SUM(COALESCE(af.nominal_fix, 0)) - SUM(af.saldo_masuk) as outstanding,
                    MIN(af.first_invoice) as invoice_number
                FROM (
                    -- Shopee
                    SELECT 
                        s.no_order,
                        s.saldo_masuk,
                        COALESCE(s.nominal_fix, 0) as nominal_fix,
                        CASE WHEN s.saldo_masuk > 0 AND s.no_invoice IS NOT NULL AND s.no_invoice != '' THEN s.no_invoice END as first_invoice
                    FROM shopee_financial_transactions s
                    INNER JOIN orders o ON o.order_number = s.no_order
                    WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                        {$platformCondition}
                    
                    UNION ALL
                    
                    -- TikTok
                    SELECT 
                        t.no_order,
                        t.saldo_masuk,
                        COALESCE(t.nominal_fix, 0) as nominal_fix,
                        CASE WHEN t.saldo_masuk > 0 AND t.no_invoice IS NOT NULL AND t.no_invoice != '' THEN t.no_invoice END as first_invoice
                    FROM tiktok_financial_transactions t
                    INNER JOIN orders o ON o.order_number = t.no_order
                    WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                        {$platformCondition}
                    
                    UNION ALL
                    
                    -- Tokopedia
                    SELECT 
                        tp.no_order,
                        tp.saldo_masuk,
                        COALESCE(tp.nominal_fix, 0) as nominal_fix,
                        CASE WHEN tp.saldo_masuk > 0 AND tp.no_invoice IS NOT NULL AND tp.no_invoice != '' THEN tp.no_invoice END as first_invoice
                    FROM tokopedia_financial_transactions tp
                    INNER JOIN orders o ON o.order_number = tp.no_order
                    WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                        {$platformCondition}
                    
                    UNION ALL
                    
                    -- Blibli
                    SELECT 
                        b.no_order,
                        b.saldo_masuk,
                        COALESCE(b.nominal_fix, 0) as nominal_fix,
                        CASE WHEN b.saldo_masuk > 0 AND b.no_invoice IS NOT NULL AND b.no_invoice != '' THEN b.no_invoice END as first_invoice
                    FROM blibli_financial_transactions b
                    INNER JOIN orders o ON o.order_number = b.no_order
                    WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                        {$platformCondition}
                ) AS af
                WHERE af.saldo_masuk > 0
                GROUP BY af.no_order
            )
        ";

        // Build where conditions for base query
        $whereConditions = [];
        $whereConditions[] = "o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'";

        if ($platformId) {
            $whereConditions[] = "o.platform_id = {$platformId}";
        }

        if ($orderNumber) {
            $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumber}%");
            $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
        }

        if ($search) {
            $searchEscaped = DB::getPdo()->quote("%{$search}%");
            $whereConditions[] = "pp.platform_product_name LIKE {$searchEscaped}";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Build summary query
        return "
            WITH {$financialCTE},
            -- Get distinct orders from filtered data
            filtered_orders AS (
                SELECT DISTINCT o.order_number
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                INNER JOIN financial_summary fs ON fs.no_order = o.order_number
                {$whereClause}
            ),
            -- Calculate total saldo masuk per order (avoid double counting)
            order_totals AS (
                SELECT 
                    fs.no_order,
                    fs.total_saldo_masuk
                FROM financial_summary fs
                INNER JOIN filtered_orders fo ON fo.order_number = fs.no_order
            ),
            -- Get all order items with COGS for summary
            order_items_summary AS (
                SELECT 
                    oi.id as order_item_id,
                    oi.quantity as platform_quantity,
                    COALESCE((
                        SELECT SUM(
                            GREATEST(0,
                                COALESCE(pd.harga_hpp, 0)
                                * (1 - COALESCE(pd.diskon_persen_1, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_2, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_3, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_4, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_5, 0) / 100.0)
                                - COALESCE(pd.diskon_nominal_1, 0)
                                - COALESCE(pd.diskon_nominal_2, 0)
                                - COALESCE(pd.diskon_nominal_3, 0)
                                - COALESCE(pd.diskon_nominal_4, 0)
                                - COALESCE(pd.diskon_nominal_5, 0)
                            ) * bk.qty
                        )
                        FROM barang_keluar bk
                        INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
                        LEFT JOIN penerimaan_detail pd ON pd.id = ws.penerimaan_detail_id
                        WHERE bk.order_item_id = oi.id
                    ), 0) as total_cogs_for_order_item
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                INNER JOIN financial_summary fs ON fs.no_order = o.order_number
                {$whereClause}
            )
            SELECT 
                COUNT(DISTINCT ois.order_item_id) as total_platform_products,
                -- Use total_saldo_masuk directly from financial_summary (not proportional revenue)
                -- This ensures summary matches the actual payment amount from financial transactions
                COALESCE((SELECT SUM(total_saldo_masuk) FROM order_totals), 0) as total_revenue,
                COALESCE(SUM(ois.total_cogs_for_order_item), 0) as total_capital,
                COALESCE(SUM(ois.platform_quantity), 0) as total_quantity
            FROM order_items_summary ois
        ";
    }

    protected function buildBaseCTE($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $platformId = $filters['platform_id'];
        $orderNumber = $filters['order_number'];
        $search = $filters['search'];

        $whereConditions = [];

        // Date filter
        $whereConditions[] = "o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'";

        // Platform filter
        if ($platformId) {
            $whereConditions[] = "o.platform_id = {$platformId}";
        }

        // Order number filter
        if ($orderNumber) {
            $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumber}%");
            $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
        }

        // Search filter for platform product name
        if ($search) {
            $searchEscaped = DB::getPdo()->quote("%{$search}%");
            $whereConditions[] = "pp.platform_product_name LIKE {$searchEscaped}";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        return "
            SELECT 
                o.id as order_id,
                o.order_number,
                o.tanggal as order_date,
                o.platform_id,
                pl.name as platform_name,
                oi.id as order_item_id,
                oi.quantity as platform_quantity,
                oi.price_after_discount as product_price,
                pp.id as platform_product_id,
                pp.platform_product_name,
                COALESCE(pp.variant, 'N/A') as platform_product_variant,
                -- Calculate total saldo masuk per order
                COALESCE((
                    SELECT SUM(saldo_masuk) 
                    FROM shopee_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0
                ), 0) + 
                COALESCE((
                    SELECT SUM(saldo_masuk) 
                    FROM tiktok_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0
                ), 0) + 
                COALESCE((
                    SELECT SUM(saldo_masuk) 
                    FROM tokopedia_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0
                ), 0) + 
                COALESCE((
                    SELECT SUM(saldo_masuk) 
                    FROM blibli_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0
                ), 0) as total_saldo_masuk,
                -- Get invoice number from financial transactions
                COALESCE((
                    SELECT no_invoice 
                    FROM shopee_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0 
                    ORDER BY tanggal_masuk_pembayaran ASC 
                    LIMIT 1
                ), (
                    SELECT no_invoice 
                    FROM tiktok_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0 
                    ORDER BY tanggal_masuk_pembayaran ASC 
                    LIMIT 1
                ), (
                    SELECT no_invoice 
                    FROM tokopedia_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0 
                    ORDER BY tanggal_masuk_pembayaran ASC 
                    LIMIT 1
                ), (
                    SELECT no_invoice 
                    FROM blibli_financial_transactions 
                    WHERE no_order = o.order_number AND saldo_masuk > 0 
                    ORDER BY tanggal_masuk_pembayaran ASC 
                    LIMIT 1
                ), '-') as invoice_number,
                -- Calculate total COGS for this order_item (sum of all barang_keluar)
                COALESCE((
                    SELECT SUM(
                        GREATEST(0,
                            COALESCE(pd.harga_hpp, 0)
                            * (1 - COALESCE(pd.diskon_persen_1, 0) / 100.0)
                            * (1 - COALESCE(pd.diskon_persen_2, 0) / 100.0)
                            * (1 - COALESCE(pd.diskon_persen_3, 0) / 100.0)
                            * (1 - COALESCE(pd.diskon_persen_4, 0) / 100.0)
                            * (1 - COALESCE(pd.diskon_persen_5, 0) / 100.0)
                            - COALESCE(pd.diskon_nominal_1, 0)
                            - COALESCE(pd.diskon_nominal_2, 0)
                            - COALESCE(pd.diskon_nominal_3, 0)
                            - COALESCE(pd.diskon_nominal_4, 0)
                            - COALESCE(pd.diskon_nominal_5, 0)
                        ) * bk.qty
                    )
                    FROM barang_keluar bk
                    INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
                    LEFT JOIN penerimaan_detail pd ON pd.id = ws.penerimaan_detail_id
                    WHERE bk.order_item_id = oi.id
                ), 0) as total_cogs_for_order_item,
                -- Calculate total order value (sum of all product prices in the order)
                COALESCE((
                    SELECT SUM(oi2.price_after_discount * oi2.quantity)
                    FROM order_items oi2
                    WHERE oi2.order_id = o.id
                ), 0) as total_order_value,
                -- Check if order has multiple items
                (SELECT COUNT(DISTINCT oi2.id) FROM order_items oi2 WHERE oi2.order_id = o.id) > 1 as has_multiple_items
            FROM orders o
            INNER JOIN platforms pl ON pl.id = o.platform_id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
            {$whereClause}
        ";
    }

    protected function buildCalcCTE()
    {
        // Calculate proportional revenue per product and other metrics
        return "
            SELECT 
                *,
                -- Calculate product value (price * quantity)
                (product_price * platform_quantity) as product_value,
                -- Calculate proportion percentage
                CASE 
                    WHEN total_order_value > 0 THEN
                        ((product_price * platform_quantity) / total_order_value) * 100
                    ELSE 0
                END as proportion_percent,
                -- Calculate revenue per product (proportional to product value)
                CASE 
                    WHEN total_order_value > 0 THEN
                        total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)
                    ELSE 0
                END as revenue_per_product,
                -- Calculate revenue per product without PPN
                CASE 
                    WHEN total_order_value > 0 THEN
                        (total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11
                    ELSE 0
                END as revenue_per_product_without_ppn,
                -- Calculate gross profit per product (revenue without PPN - capital)
                CASE 
                    WHEN total_order_value > 0 THEN
                        ((total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11) - total_cogs_for_order_item
                    ELSE (0 - total_cogs_for_order_item)
                END as gross_profit_per_product,
                -- Calculate margin per product (Rp) - same as gross profit per product
                CASE 
                    WHEN total_order_value > 0 THEN
                        ((total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11) - total_cogs_for_order_item
                    ELSE (0 - total_cogs_for_order_item)
                END as margin_per_product_rp,
                -- Calculate margin per product (%)
                CASE 
                    WHEN total_order_value > 0 AND ((total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11) > 0 THEN
                        ((((total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11) - total_cogs_for_order_item) / ((total_saldo_masuk * ((product_price * platform_quantity) / total_order_value)) / 1.11)) * 100
                    ELSE 0
                END as margin_per_product_percent
            FROM base_data
        ";
    }

    protected function finalSelect()
    {
        $sortBy = $this->request->input('sort', 'revenue_highest');
        $sortColumn = $this->getSortColumn($sortBy);
        $sortDirection = strpos($sortBy, 'lowest') !== false ? 'ASC' : 'DESC';

        return "
            SELECT 
                order_id,
                order_number,
                invoice_number,
                order_date,
                platform_name as platform,
                platform_product_name,
                platform_product_variant as product_variant,
                platform_quantity as quantity,
                -- Revenue per product (proportional)
                revenue_per_product as revenue,
                revenue_per_product_without_ppn as revenue_without_ppn,
                total_cogs_for_order_item as capital,
                -- Gross profit per product (Rp)
                gross_profit_per_product as gross_profit_per_product_rp,
                -- Gross profit per product (%)
                CASE 
                    WHEN revenue_per_product_without_ppn > 0 THEN
                        (gross_profit_per_product / revenue_per_product_without_ppn) * 100
                    ELSE 0
                END as gross_profit_per_product_percent,
                -- Margin per product (Rp) - same as gross profit per product
                margin_per_product_rp,
                -- Margin per product (%)
                margin_per_product_percent,
                -- Gross profit per order (sum of all products in order) - calculated using window function
                SUM(gross_profit_per_product) OVER (PARTITION BY order_id) as gross_profit_per_order_rp,
                -- Margin per order (Rp) - sum of all margins in order
                SUM(margin_per_product_rp) OVER (PARTITION BY order_id) as margin_per_order_rp,
                -- Margin per order (%)
                CASE 
                    WHEN SUM(revenue_per_product_without_ppn) OVER (PARTITION BY order_id) > 0 THEN
                        (SUM(margin_per_product_rp) OVER (PARTITION BY order_id) / SUM(revenue_per_product_without_ppn) OVER (PARTITION BY order_id)) * 100
                    ELSE 0
                END as margin_per_order_percent,
                -- Total saldo masuk per order (for reference)
                total_saldo_masuk as total_order_payment,
                -- Total order value (for reference)
                total_order_value,
                -- Proportion percent (for reference)
                proportion_percent,
                has_multiple_items
            FROM calculated_data
            ORDER BY {$sortColumn} {$sortDirection}
        ";
    }

    protected function getSortColumn($sortBy)
    {
        $sortMap = [
            'revenue_highest' => 'revenue',
            'revenue_lowest' => 'revenue',
            'profit_highest' => 'gross_profit',
            'profit_lowest' => 'gross_profit',
            'quantity_highest' => 'quantity',
            'quantity_lowest' => 'quantity',
        ];

        return $sortMap[$sortBy] ?? 'revenue';
    }

    protected function getFilters()
    {
        $startDate = $this->request->filled('start_date') 
            ? $this->request->input('start_date') 
            : now()->format('Y-m-d');
        
        $endDate = $this->request->filled('end_date') 
            ? $this->request->input('end_date') 
            : now()->format('Y-m-d');

        // Handle quick range
        if ($this->request->filled('quick_range')) {
            $range = $this->request->input('quick_range');
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

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $this->request->input('platform_id'),
            'order_number' => $this->request->input('order_number'),
            'search' => $this->request->input('search'),
        ];
    }
}


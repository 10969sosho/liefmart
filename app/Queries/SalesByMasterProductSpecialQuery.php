<?php

namespace App\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesByMasterProductSpecialQuery
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
        $sql = $this->buildFullQuery();
        $results = DB::select($sql);
        
        // Convert objects to arrays for compatibility
        return array_map(function($row) {
            return (array) $row;
        }, $results);
    }

    public function getSummary()
    {
        // Build summary query using aggregates - separate from table query
        $filters = $this->getFilters();
        $sql = $this->buildSummaryQuery($filters);
        $result = DB::selectOne($sql);
        
        $totalRevenue = (float) ($result->total_revenue ?? 0);
        $totalCapital = (float) ($result->total_capital ?? 0);
        $totalQuantity = (float) ($result->total_quantity ?? 0);
        $totalBarangKeluar = (int) ($result->total_products ?? 0);
        
        $totalRevenueWithoutPPN = $totalRevenue / 1.11; // Revenue without PPN
        $totalGrossProfit = $totalRevenueWithoutPPN - $totalCapital; // Revenue without PPN - capital
        
        return [
            'total_products' => $totalBarangKeluar,
            'total_rows' => $totalBarangKeluar,
            'total_revenue' => $totalRevenue,
            'total_revenue_without_ppn' => $totalRevenueWithoutPPN,
            'total_capital' => $totalCapital,
            'total_gross_profit' => $totalGrossProfit,
            'total_quantity' => $totalQuantity,
            'profit_margin' => $totalRevenueWithoutPPN > 0 ? ($totalGrossProfit / $totalRevenueWithoutPPN) * 100 : 0,
            // Pre-calculated values for Blade (no calculations in view)
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.'),
            'total_revenue_without_ppn_formatted' => number_format($totalRevenueWithoutPPN, 0, ',', '.'),
            'total_capital_formatted' => number_format($totalCapital, 0, ',', '.'),
            'total_gross_profit_formatted' => number_format($totalGrossProfit, 0, ',', '.'),
            'profit_margin_formatted' => number_format($totalRevenueWithoutPPN > 0 ? ($totalGrossProfit / $totalRevenueWithoutPPN) * 100 : 0, 2),
        ];
    }

    /**
     * Build summary query using aggregates (separate from table query)
     */
    protected function buildSummaryQuery($filters)
    {
        $financialCTE = $this->buildFinancialSummaryCTE($filters);
        $orderValueCTE = $this->buildOrderValueCTE($filters);
        $baseCTE = $this->buildBaseCTE($filters);
        $calcCTE = $this->buildCalcCTE();

        return "
            WITH {$financialCTE},
            {$orderValueCTE},
            base_data AS ({$baseCTE}),
            calculated_data AS ({$calcCTE}),
            -- Get distinct orders from calculated_data to calculate total saldo masuk
            distinct_orders AS (
                SELECT DISTINCT order_number
                FROM calculated_data
            ),
            -- Calculate total saldo masuk per order (avoid double counting)
            order_totals AS (
                SELECT 
                    fs.no_order,
                    fs.total_saldo_masuk
                FROM financial_summary fs
                INNER JOIN distinct_orders do ON do.order_number = fs.no_order
            )
            SELECT 
                COUNT(*) as total_rows,
                COUNT(*) as total_products,
                -- Use total_saldo_masuk directly from financial_summary (not proportional revenue)
                -- This ensures summary matches the actual payment amount from financial transactions
                COALESCE((SELECT SUM(total_saldo_masuk) FROM order_totals), 0) as total_revenue,
                COALESCE(SUM(capital), 0) as total_capital,
                COALESCE(SUM(quantity), 0) as total_quantity
            FROM calculated_data
        ";
    }

    /**
     * Fast count query - uses financial_summary CTE instead of EXISTS
     * COUNT(DISTINCT order_items.id) with all filters matching final_data
     */
    protected function getTotalCount()
    {
        $filters = $this->getFilters();
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $platformId = $filters['platform_id'];
        $orderNumber = $filters['order_number'];
        $search = $filters['search'];
        $selectedBrands = $filters['brands'];
        $selectedSubBrands = $filters['sub_brands'];
        $selectedProductCategories = $filters['product_categories'];
        $selectedProductTypes = $filters['product_types'];
        $selectedProductSizes = $filters['product_sizes'];
        $selectedProductVariants = $filters['product_variants'];

        // Build financial_summary CTE (same as main query)
        $financialCTE = $this->buildFinancialSummaryCTE($filters);

        $whereConditions = [];

        // Date filter
        $whereConditions[] = "o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'";

        // Platform filter
        if ($platformId) {
            $whereConditions[] = "o.platform_id = {$platformId}";
        }

        // Order number filter - support exact match or partial match
        if ($orderNumber) {
            $orderNumberClean = trim($orderNumber);
            if (!empty($orderNumberClean)) {
                // If it looks like an exact number, try exact match first, then partial
                if (is_numeric($orderNumberClean) && strlen($orderNumberClean) > 10) { // Heuristic for long order numbers
                    $whereConditions[] = "o.order_number = " . DB::getPdo()->quote($orderNumberClean);
                } else {
                    $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumberClean}%");
                    $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
                }
            }
        }

        // Product filters
        if (!empty($selectedBrands)) {
            $brandIds = implode(',', array_map('intval', $selectedBrands));
            $whereConditions[] = "p.brand_id IN ({$brandIds})";
        }

        if (!empty($selectedSubBrands)) {
            $subBrandIds = implode(',', array_map('intval', $selectedSubBrands));
            $whereConditions[] = "p.sub_brand_id IN ({$subBrandIds})";
        }

        if (!empty($selectedProductCategories)) {
            $categoryIds = implode(',', array_map('intval', $selectedProductCategories));
            $whereConditions[] = "p.product_category_id IN ({$categoryIds})";
        }

        if (!empty($selectedProductTypes)) {
            $typeIds = implode(',', array_map('intval', $selectedProductTypes));
            $whereConditions[] = "p.product_type_id IN ({$typeIds})";
        }

        if (!empty($selectedProductSizes)) {
            $sizeIds = implode(',', array_map('intval', $selectedProductSizes));
            $whereConditions[] = "p.product_size_id IN ({$sizeIds})";
        }

        if (!empty($selectedProductVariants)) {
            $variantIds = implode(',', array_map('intval', $selectedProductVariants));
            $whereConditions[] = "p.product_variant_id IN ({$variantIds})";
        }

        // Search filter
        if ($search) {
            $searchEscaped = DB::getPdo()->quote("%{$search}%");
            $whereConditions[] = "(
                pp.platform_product_name LIKE {$searchEscaped}
                OR pp.variant LIKE {$searchEscaped}
                OR p.name LIKE {$searchEscaped}
                OR p.sku LIKE {$searchEscaped}
            )";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Fast count query - uses financial_summary CTE instead of EXISTS
        // This ensures count matches actual data (only orders with payment)
        // Mapping selection: timestamp-based versioning (select mapping valid at order creation time)
        // For package products, count each barang_keluar, not order_item
        $countSQL = "
            WITH {$financialCTE}
            SELECT COUNT(DISTINCT bk.id) as total
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
            INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
            INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
            INNER JOIN products p ON p.id = ws.product_id
            LEFT JOIN mapping_barangs mb ON mb.id = (
                SELECT mb2.id
                FROM mapping_barangs mb2
                WHERE mb2.platform_product_id = pp.id
                  AND mb2.product_id = p.id
                  AND COALESCE(mb2.valid_from, mb2.created_at) <= o.created_at
                  AND (mb2.valid_until IS NULL OR mb2.valid_until >= o.created_at)
                ORDER BY COALESCE(mb2.valid_from, mb2.created_at) DESC
                LIMIT 1
            )
            INNER JOIN financial_summary fs ON fs.no_order = o.order_number
            {$whereClause}
        ";

        $result = DB::selectOne($countSQL);
        return $result->total ?? 0;
    }

    protected function getPaginatedData($offset, $limit)
    {
        $sortBy = $this->request->input('sort', 'revenue_highest');
        $sortColumn = $this->getSortColumn($sortBy);
        $sortDirection = strpos($sortBy, 'lowest') !== false ? 'ASC' : 'DESC';

        // Build full query and wrap in derived table for pagination
        $innerQuery = $this->buildFullQuery();
        
        $sql = "
            SELECT * FROM (
                {$innerQuery}
            ) AS paginated_data
            ORDER BY {$sortColumn} {$sortDirection}
            LIMIT {$limit} OFFSET {$offset}
        ";

        $results = DB::select($sql);
        
        // Convert objects to arrays for compatibility with Blade views
        return array_map(function($row) {
            return (array) $row;
        }, $results);
    }

    protected function buildFullQuery()
    {
        $filters = $this->getFilters();
        $financialCTE = $this->buildFinancialSummaryCTE($filters);
        $orderValueCTE = $this->buildOrderValueCTE($filters);
        $baseCTE = $this->buildBaseCTE($filters);
        $calcCTE = $this->buildCalcCTE();
        $finalSelect = $this->buildFinalSelect();

        return "
            WITH {$financialCTE},
            {$orderValueCTE},
            base_data AS ({$baseCTE}),
            calculated_data AS ({$calcCTE})
            {$finalSelect}
        ";
    }

    /**
     * Build financial summary CTE - Optimized: filter date and platform BEFORE UNION ALL
     * Each SELECT joins with orders and filters before UNION, reducing data processed
     */
    protected function buildFinancialSummaryCTE($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $platformId = $filters['platform_id'];
        
        $platformCondition = '';
        if ($platformId) {
            $platformCondition = "AND o.platform_id = {$platformId}";
        } else {
            $platformCondition = "";
        }
        
        return "
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
                ) AS af
                WHERE af.saldo_masuk > 0
                GROUP BY af.no_order
            )
        ";
    }

    /**
     * Build order value CTE - Pre-calculate total order value per order
     */
    protected function buildOrderValueCTE($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        
        return "
            order_value AS (
                SELECT 
                    o.id as order_id,
                    COALESCE(SUM(COALESCE(pipv.initial_price, 0) * bk.qty), 0) as total_order_value
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
                INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
                INNER JOIN products p ON p.id = ws.product_id
                LEFT JOIN product_initial_price_versions pipv ON pipv.product_id = p.id
                    AND pipv.valid_from <= o.created_at
                    AND (pipv.valid_until IS NULL OR pipv.valid_until > o.created_at)
                WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                GROUP BY o.id
            )
        ";
    }

    protected function buildBaseCTE($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $platformId = $filters['platform_id'];
        $orderNumber = $filters['order_number'];
        $search = $filters['search'];
        $selectedBrands = $filters['brands'];
        $selectedSubBrands = $filters['sub_brands'];
        $selectedProductCategories = $filters['product_categories'];
        $selectedProductTypes = $filters['product_types'];
        $selectedProductSizes = $filters['product_sizes'];
        $selectedProductVariants = $filters['product_variants'];

        $whereConditions = [];

        // Date filter
        $whereConditions[] = "o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'";

        // Platform filter
        if ($platformId) {
            $whereConditions[] = "o.platform_id = {$platformId}";
        }

        // Order number filter - support exact match or partial match
        if ($orderNumber) {
            $orderNumberClean = trim($orderNumber);
            if (!empty($orderNumberClean)) {
                // If it looks like an exact number, try exact match first, then partial
                if (is_numeric($orderNumberClean) && strlen($orderNumberClean) > 10) { // Heuristic for long order numbers
                    $whereConditions[] = "o.order_number = " . DB::getPdo()->quote($orderNumberClean);
                } else {
                    $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumberClean}%");
                    $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
                }
            }
        }

        // Product filters
        if (!empty($selectedBrands)) {
            $brandIds = implode(',', array_map('intval', $selectedBrands));
            $whereConditions[] = "p.brand_id IN ({$brandIds})";
        }

        if (!empty($selectedSubBrands)) {
            $subBrandIds = implode(',', array_map('intval', $selectedSubBrands));
            $whereConditions[] = "p.sub_brand_id IN ({$subBrandIds})";
        }

        if (!empty($selectedProductCategories)) {
            $categoryIds = implode(',', array_map('intval', $selectedProductCategories));
            $whereConditions[] = "p.product_category_id IN ({$categoryIds})";
        }

        if (!empty($selectedProductTypes)) {
            $typeIds = implode(',', array_map('intval', $selectedProductTypes));
            $whereConditions[] = "p.product_type_id IN ({$typeIds})";
        }

        if (!empty($selectedProductSizes)) {
            $sizeIds = implode(',', array_map('intval', $selectedProductSizes));
            $whereConditions[] = "p.product_size_id IN ({$sizeIds})";
        }

        if (!empty($selectedProductVariants)) {
            $variantIds = implode(',', array_map('intval', $selectedProductVariants));
            $whereConditions[] = "p.product_variant_id IN ({$variantIds})";
        }

        // Search filter
        if ($search) {
            $searchEscaped = DB::getPdo()->quote("%{$search}%");
            $whereConditions[] = "(
                pp.platform_product_name LIKE {$searchEscaped}
                OR pp.variant LIKE {$searchEscaped}
                OR p.name LIKE {$searchEscaped}
                OR p.sku LIKE {$searchEscaped}
            )";
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
                pp.id as platform_product_id,
                pp.platform_product_name,
                COALESCE(pp.variant, 'N/A') as platform_product_variant,
                bk.id as barang_keluar_id,
                bk.qty as master_qty,
                p.id as product_id,
                p.name as product_name,
                COALESCE(p.sku, 'N/A') as sku,
                COALESCE(pipv.initial_price, 0) as price,
                COALESCE(fs.total_saldo_masuk, 0) as total_saldo_masuk,
                COALESCE(fs.invoice_number, '-') as invoice_number,
                COALESCE(ov.total_order_value, 0) as total_order_value_from_products
            FROM orders o
            INNER JOIN platforms pl ON pl.id = o.platform_id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
            -- Mapping selection: timestamp-based versioning (select mapping valid at order creation time)
            -- For package products, we need to join mapping based on both platform_product_id AND product_id
            -- This ensures we get the correct mapping for each product in the package
            INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
            INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
            INNER JOIN products p ON p.id = ws.product_id
            LEFT JOIN product_initial_price_versions pipv ON pipv.product_id = p.id
                AND pipv.valid_from <= o.created_at
                AND (pipv.valid_until IS NULL OR pipv.valid_until > o.created_at)
            LEFT JOIN mapping_barangs mb ON mb.id = (
                SELECT mb2.id
                FROM mapping_barangs mb2
                WHERE mb2.platform_product_id = pp.id
                  AND mb2.product_id = p.id
                  AND COALESCE(mb2.valid_from, mb2.created_at) <= o.created_at
                  AND (mb2.valid_until IS NULL OR mb2.valid_until >= o.created_at)
                ORDER BY COALESCE(mb2.valid_from, mb2.created_at) DESC
                LIMIT 1
            )
            INNER JOIN financial_summary fs ON fs.no_order = o.order_number
            LEFT JOIN order_value ov ON ov.order_id = o.id
            {$whereClause}
        ";
    }

    protected function buildCalcCTE()
    {
        // Calculate COGS: sequential percentage discounts (each applied to previous result) then subtract nominal discounts
        // Formula: hpp * (1-d1/100) * (1-d2/100) * (1-d3/100) * (1-d4/100) * (1-d5/100) - sum(nominal_discounts)
        // COGS formula - calculate once, reuse
        $cogsFormula = "
            COALESCE((
                SELECT 
                    SUM(
                        GREATEST(0,
                            COALESCE(pd2.harga_hpp, 0)
                            * (1 - COALESCE(pd2.diskon_persen_1, 0) / 100.0)
                            * (1 - COALESCE(pd2.diskon_persen_2, 0) / 100.0)
                            * (1 - COALESCE(pd2.diskon_persen_3, 0) / 100.0)
                            * (1 - COALESCE(pd2.diskon_persen_4, 0) / 100.0)
                            * (1 - COALESCE(pd2.diskon_persen_5, 0) / 100.0)
                            - COALESCE(pd2.diskon_nominal_1, 0)
                            - COALESCE(pd2.diskon_nominal_2, 0)
                            - COALESCE(pd2.diskon_nominal_3, 0)
                            - COALESCE(pd2.diskon_nominal_4, 0)
                            - COALESCE(pd2.diskon_nominal_5, 0)
                        ) * pd2.qty
                    ) / NULLIF(SUM(pd2.qty), 0)
                FROM penerimaan_detail pd2
                WHERE pd2.product_id = base_data.product_id
                AND pd2.qty > 0
            ), 0)
        ";
        
        return "
            SELECT 
                *,
                -- Calculate AVERAGE COST per unit (SPECIAL REPORT)
                {$cogsFormula} as cogs_per_unit,
                -- Calculate pricelist total (price * qty)
                COALESCE(price, 0) * COALESCE(master_qty, 0) as pricelist_total,
                -- Calculate proportion percent
                CASE 
                    WHEN total_order_value_from_products > 0 THEN
                        (COALESCE(price, 0) * COALESCE(master_qty, 0) / total_order_value_from_products) * 100
                    ELSE 0
                END as proportion_percent,
                -- Pre-calculate revenue, capital, quantity for summary
                (total_saldo_masuk * 
                    CASE 
                        WHEN total_order_value_from_products > 0 THEN
                            (COALESCE(price, 0) * COALESCE(master_qty, 0) / total_order_value_from_products) * 100
                        ELSE 0
                    END / 100) as revenue,
                ({$cogsFormula} * COALESCE(master_qty, 0)) as capital,
                COALESCE(master_qty, 0) as quantity
            FROM base_data
        ";
    }

    /**
     * Build final select - just format and select pre-calculated columns
     */
    protected function buildFinalSelect()
    {
        return "
            SELECT 
                order_number,
                invoice_number,
                order_date,
                DATE_FORMAT(order_date, '%d/%m/%Y') as order_date_formatted,
                platform_name as platform,
                platform_product_name,
                platform_product_variant as platform_product_variant,
                platform_quantity,
                sku,
                product_name,
                master_qty as quantity,
                price,
                pricelist_total,
                proportion_percent,
                total_saldo_masuk as order_total_payment,
                total_order_value_from_products,
                -- Use pre-calculated revenue from calculated_data
                revenue,
                -- Use pre-calculated capital from calculated_data
                capital,
                cogs_per_unit as modal_per_pcs,
                -- Calculate payment per product per pcs (revenue / qty)
                (revenue / NULLIF(master_qty, 0)) as payment_per_product_per_pcs,
                -- Calculate payment per product per pcs without PPN
                ((revenue / NULLIF(master_qty, 0)) / 1.11) as payment_per_product_without_ppn,
                -- Calculate unit cost (capital / qty)
                (capital / NULLIF(master_qty, 0)) as unit_cost,
                -- Calculate profit per pcs (payment_per_product_without_ppn - unit_cost)
                (((revenue / NULLIF(master_qty, 0)) / 1.11) - (capital / NULLIF(master_qty, 0))) as profit_per_pcs,
                -- Calculate gross profit total (profit_per_pcs * qty)
                ((((revenue / NULLIF(master_qty, 0)) / 1.11) - (capital / NULLIF(master_qty, 0))) * master_qty) as gross_profit_total,
                -- Calculate margin per pcs (%)
                CASE 
                    WHEN ((revenue / NULLIF(master_qty, 0)) / 1.11) > 0 THEN
                        ((((revenue / NULLIF(master_qty, 0)) / 1.11) - (capital / NULLIF(master_qty, 0))) / ((revenue / NULLIF(master_qty, 0)) / 1.11)) * 100
                    ELSE 0
                END as margin_per_pcs,
                -- Calculate margin per item (%)
                CASE 
                    WHEN (((revenue / NULLIF(master_qty, 0)) / 1.11) * master_qty) > 0 THEN
                        (((((revenue / NULLIF(master_qty, 0)) / 1.11) - (capital / NULLIF(master_qty, 0))) * master_qty) / (((revenue / NULLIF(master_qty, 0)) / 1.11) * master_qty)) * 100
                    ELSE 0
                END as margin_per_item
            FROM calculated_data
        ";
    }

    protected function getSortColumn($sortBy)
    {
        $sortMap = [
            'revenue_highest' => 'revenue',
            'revenue_lowest' => 'revenue',
            'profit_highest' => 'gross_profit_total',
            'profit_lowest' => 'gross_profit_total',
            'quantity_highest' => 'quantity',
            'quantity_lowest' => 'quantity',
        ];

        return $sortMap[$sortBy] ?? 'revenue';
    }

    public function getFilters()
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

        $orderNumber = trim($this->request->input('order_number', ''));
        $search = trim($this->request->input('search', ''));

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $this->request->filled('platform_id') ? (int) $this->request->input('platform_id') : null,
            'order_number' => !empty($orderNumber) ? $orderNumber : null,
            'search' => !empty($search) ? $search : null,
            'brands' => array_filter((array) $this->request->input('brands', [])),
            'sub_brands' => array_filter((array) $this->request->input('sub_brands', [])),
            'product_categories' => array_filter((array) $this->request->input('product_categories', [])),
            'product_types' => array_filter((array) $this->request->input('product_types', [])),
            'product_sizes' => array_filter((array) $this->request->input('product_sizes', [])),
            'product_variants' => array_filter((array) $this->request->input('product_variants', [])),
        ];
    }
}

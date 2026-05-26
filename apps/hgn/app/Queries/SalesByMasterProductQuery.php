<?php

namespace App\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesByMasterProductQuery
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

        // Get total count using fast count method (no CTE)
        $totalCount = $this->getTotalCount();

        // Get paginated data using derived table (no temp table)
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
        $outstandingStatus = $filters['outstanding_status'] ?? null;

        // Build financial_summary CTE (same as main query)
        $financialCTE = $this->buildFinancialSummaryCTE($filters);

        $whereConditions = [];

        // Date filter
        $whereConditions[] = "o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'";

        // Platform filter
        if ($platformId) {
            $whereConditions[] = "o.platform_id = {$platformId}";
        }

        // Order number filter - support partial match (remove spaces and search)
        if ($orderNumber) {
            $orderNumberClean = trim($orderNumber);
            if (!empty($orderNumberClean)) {
                $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumberClean}%");
                $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
            }
        }

        // ✅ FIX: HIERARCHICAL FILTER - Use deepest level only, parent filters are automatically locked
        // This ensures cascading filter works correctly (no data mismatch)
        if (!empty($selectedProductVariants)) {
            // Level 6 (deepest) - Variant
            $variantIds = implode(',', array_map('intval', $selectedProductVariants));
            $whereConditions[] = "p.product_variant_id IN ({$variantIds})";
        } elseif (!empty($selectedProductSizes)) {
            // Level 5 - Size
            $sizeIds = implode(',', array_map('intval', $selectedProductSizes));
            $whereConditions[] = "p.product_size_id IN ({$sizeIds})";
        } elseif (!empty($selectedProductTypes)) {
            // Level 4 - Type
            $typeIds = implode(',', array_map('intval', $selectedProductTypes));
            $whereConditions[] = "p.product_type_id IN ({$typeIds})";
        } elseif (!empty($selectedProductCategories)) {
            // Level 3 - Category
            $categoryIds = implode(',', array_map('intval', $selectedProductCategories));
            $whereConditions[] = "p.product_category_id IN ({$categoryIds})";
        } elseif (!empty($selectedSubBrands)) {
            // Level 2 - Sub Brand
            $subBrandIds = implode(',', array_map('intval', $selectedSubBrands));
            $whereConditions[] = "p.sub_brand_id IN ({$subBrandIds})";
        } elseif (!empty($selectedBrands)) {
            // Level 1 (shallowest) - Brand
            $brandIds = implode(',', array_map('intval', $selectedBrands));
            $whereConditions[] = "p.brand_id IN ({$brandIds})";
        }

        // Search filter - search in platform product name, master product name, and SKU
        if ($search) {
            $searchClean = trim($search);
            if (!empty($searchClean)) {
                $searchEscaped = DB::getPdo()->quote("%{$searchClean}%");
                $whereConditions[] = "(
                    pp.platform_product_name LIKE {$searchEscaped}
                    OR p.name LIKE {$searchEscaped}
                    OR p.sku LIKE {$searchEscaped}
                    OR pp.platform_product_variant LIKE {$searchEscaped}
                )";
            }
        }

        // Outstanding filter (same as final_data)
        if ($outstandingStatus !== null) {
            if ($outstandingStatus === '0') {
                $whereConditions[] = "(
                    COALESCE(fs.outstanding, 0) = 0 
                    AND NOT (
                        EXISTS (
                            SELECT 1 
                            FROM retur_penjualans rp 
                            WHERE rp.order_id = o.id 
                            AND rp.status IN ('draft', 'selesai')
                        )
                        AND COALESCE(fs.total_saldo_masuk, 0) = 0
                    )
                )";
            } elseif ($outstandingStatus === '1') {
                $whereConditions[] = "(
                    (COALESCE(fs.outstanding, 0) > 0 OR COALESCE(fs.outstanding, 0) < 0)
                    AND NOT (
                        EXISTS (
                            SELECT 1 
                            FROM retur_penjualans rp 
                            WHERE rp.order_id = o.id 
                            AND rp.status IN ('draft', 'selesai')
                        )
                        AND COALESCE(fs.total_saldo_masuk, 0) = 0
                    )
                )";
            }
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

    /**
     * Get paginated data using derived table (no temp table)
     */
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

    /**
     * Build full query with all CTEs (max 3 CTEs)
     * CTE 1: financial_summary (optimized - group by once per order)
     * CTE 2: order_value (pre-calculate order value)
     * CTE 3: final_data (base + all calculations in one place)
     */
    protected function buildFullQuery()
    {
        $filters = $this->getFilters();
        $financialCTE = $this->buildFinancialSummaryCTE($filters);
        $orderValueCTE = $this->buildOrderValueCTE($filters);
        $finalDataCTE = $this->buildFinalDataCTE($filters);
        $finalSelect = $this->buildFinalSelect();

        return "
            WITH {$financialCTE},
            {$orderValueCTE},
            final_data AS ({$finalDataCTE})
            {$finalSelect}
        ";
    }

    /**
     * Build summary query using aggregates (separate from table query)
     * IMPORTANT: total_revenue uses total_saldo_masuk directly from financial_summary
     * to match the actual payment amount, not the proportional revenue allocation
     */
    protected function buildSummaryQuery($filters)
    {
        $financialCTE = $this->buildFinancialSummaryCTE($filters);
        $orderValueCTE = $this->buildOrderValueCTE($filters);
        $finalDataCTE = $this->buildFinalDataCTE($filters);

        return "
            WITH {$financialCTE},
            {$orderValueCTE},
            final_data AS ({$finalDataCTE}),
            -- Get distinct orders from final_data to calculate total saldo masuk
            distinct_orders AS (
                SELECT DISTINCT order_number
                FROM final_data
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
                COALESCE(SUM(fd.capital), 0) as total_capital,
                COALESCE(SUM(fd.quantity), 0) as total_quantity
            FROM final_data fd
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
                    COALESCE(SUM(COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty), 0) as total_order_value
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
                INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
                INNER JOIN products p ON p.id = ws.product_id
                LEFT JOIN product_initial_price_versions pipv ON pipv.id = (
                    SELECT pipv2.id
                    FROM product_initial_price_versions pipv2
                    WHERE pipv2.product_id = p.id
                      AND COALESCE(pipv2.valid_from, pipv2.created_at) <= o.created_at
                      AND (pipv2.valid_until IS NULL OR pipv2.valid_until >= o.created_at)
                    ORDER BY COALESCE(pipv2.valid_from, pipv2.created_at) DESC
                    LIMIT 1
                )
                WHERE o.tanggal BETWEEN '{$startDate}' AND '{$endDate}'
                GROUP BY o.id
            )
        ";
    }

    /**
     * Build final data CTE - Base data + all calculations in one place
     * COGS calculated once, then reused for all derived metrics
     */
    protected function buildFinalDataCTE($filters)
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

        // Order number filter - support partial match (remove spaces and search)
        if ($orderNumber) {
            $orderNumberClean = trim($orderNumber);
            if (!empty($orderNumberClean)) {
                $orderNumberEscaped = DB::getPdo()->quote("%{$orderNumberClean}%");
                $whereConditions[] = "o.order_number LIKE {$orderNumberEscaped}";
            }
        }

        // ✅ FIX: HIERARCHICAL FILTER - Use deepest level only, parent filters are automatically locked
        // This ensures cascading filter works correctly (no data mismatch)
        if (!empty($selectedProductVariants)) {
            // Level 6 (deepest) - Variant
            $variantIds = implode(',', array_map('intval', $selectedProductVariants));
            $whereConditions[] = "p.product_variant_id IN ({$variantIds})";
        } elseif (!empty($selectedProductSizes)) {
            // Level 5 - Size
            $sizeIds = implode(',', array_map('intval', $selectedProductSizes));
            $whereConditions[] = "p.product_size_id IN ({$sizeIds})";
        } elseif (!empty($selectedProductTypes)) {
            // Level 4 - Type
            $typeIds = implode(',', array_map('intval', $selectedProductTypes));
            $whereConditions[] = "p.product_type_id IN ({$typeIds})";
        } elseif (!empty($selectedProductCategories)) {
            // Level 3 - Category
            $categoryIds = implode(',', array_map('intval', $selectedProductCategories));
            $whereConditions[] = "p.product_category_id IN ({$categoryIds})";
        } elseif (!empty($selectedSubBrands)) {
            // Level 2 - Sub Brand
            $subBrandIds = implode(',', array_map('intval', $selectedSubBrands));
            $whereConditions[] = "p.sub_brand_id IN ({$subBrandIds})";
        } elseif (!empty($selectedBrands)) {
            // Level 1 (shallowest) - Brand
            $brandIds = implode(',', array_map('intval', $selectedBrands));
            $whereConditions[] = "p.brand_id IN ({$brandIds})";
        }

        // Search filter - search in platform product name, master product name, and SKU
        if ($search) {
            $searchClean = trim($search);
            if (!empty($searchClean)) {
                $searchEscaped = DB::getPdo()->quote("%{$searchClean}%");
                $whereConditions[] = "(
                    pp.platform_product_name LIKE {$searchEscaped}
                    OR p.name LIKE {$searchEscaped}
                    OR p.sku LIKE {$searchEscaped}
                    OR pp.platform_product_variant LIKE {$searchEscaped}
                )";
            }
        }

        // Outstanding filter
        $outstandingStatus = $filters['outstanding_status'] ?? null;
        if ($outstandingStatus !== null) {
            if ($outstandingStatus === '0') {
                $whereConditions[] = "(
                    COALESCE(fs.outstanding, 0) = 0 
                    AND NOT (
                        EXISTS (
                            SELECT 1 
                            FROM retur_penjualans rp 
                            WHERE rp.order_id = o.id 
                            AND rp.status IN ('draft', 'selesai')
                        )
                        AND COALESCE(fs.total_saldo_masuk, 0) = 0
                    )
                )";
            } elseif ($outstandingStatus === '1') {
                $whereConditions[] = "(
                    (COALESCE(fs.outstanding, 0) > 0 OR COALESCE(fs.outstanding, 0) < 0)
                    AND NOT (
                        EXISTS (
                            SELECT 1 
                            FROM retur_penjualans rp 
                            WHERE rp.order_id = o.id 
                            AND rp.status IN ('draft', 'selesai')
                        )
                        AND COALESCE(fs.total_saldo_masuk, 0) = 0
                    )
                )";
            }
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // COGS formula - calculate once, reuse
        $cogsFormula = "
            CASE 
                WHEN pd.id IS NOT NULL THEN
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
                    )
                ELSE 0
            END
        ";

        return "
            SELECT 
                o.order_number,
                COALESCE(fs.invoice_number, '-') as invoice_number,
                o.tanggal as order_date,
                pl.name as platform_name,
                pp.platform_product_name,
                COALESCE(pp.variant, 'N/A') as platform_product_variant,
                oi.quantity as platform_quantity,
                COALESCE(p.sku, 'N/A') as sku,
                p.name as product_name,
                bk.qty as quantity,
                COALESCE(pipv.initial_price, p.initial_price, 0) as price,
                COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty as pricelist_total,
                CASE 
                    WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                        (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                    ELSE 0
                END as proportion_percent,
                COALESCE(fs.total_saldo_masuk, 0) as order_total_payment,
                COALESCE(fs.total_saldo_masuk, 0) / 1.11 as order_total_payment_without_ppn,
                COALESCE(ov.total_order_value, 0) as total_order_value_from_products,
                -- COGS calculated once
                {$cogsFormula} as cogs_per_unit,
                -- All derived metrics using pre-calculated values
                (COALESCE(fs.total_saldo_masuk, 0) * 
                    CASE 
                        WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                            (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                        ELSE 0
                    END / 100) as revenue,
                ({$cogsFormula} * bk.qty) as capital,
                {$cogsFormula} as modal_per_pcs,
                ((COALESCE(fs.total_saldo_masuk, 0) * 
                    CASE 
                        WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                            (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                        ELSE 0
                    END / 100) / NULLIF(bk.qty, 0)) as payment_per_product_per_pcs,
                (((COALESCE(fs.total_saldo_masuk, 0) * 
                    CASE 
                        WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                            (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                        ELSE 0
                    END / 100) / NULLIF(bk.qty, 0)) / 1.11) as payment_per_product_without_ppn,
                {$cogsFormula} as unit_cost,
                ((((COALESCE(fs.total_saldo_masuk, 0) * 
                    CASE 
                        WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                            (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                        ELSE 0
                    END / 100) / NULLIF(bk.qty, 0)) / 1.11) - {$cogsFormula}) as profit_per_pcs,
                (((((COALESCE(fs.total_saldo_masuk, 0) * 
                    CASE 
                        WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                            (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                        ELSE 0
                    END / 100) / NULLIF(bk.qty, 0)) / 1.11) - {$cogsFormula}) * bk.qty) as gross_profit_total,
                CASE 
                    WHEN (((COALESCE(fs.total_saldo_masuk, 0) * 
                        CASE 
                            WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                            ELSE 0
                        END / 100) / NULLIF(bk.qty, 0)) / 1.11) > 0 THEN
                        (((((COALESCE(fs.total_saldo_masuk, 0) * 
                            CASE 
                                WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                    (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                                ELSE 0
                            END / 100) / NULLIF(bk.qty, 0)) / 1.11) - {$cogsFormula}) / 
                        (((COALESCE(fs.total_saldo_masuk, 0) * 
                            CASE 
                                WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                    (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                                ELSE 0
                            END / 100) / NULLIF(bk.qty, 0)) / 1.11)) * 100
                    ELSE 0
                END as margin_per_pcs,
                CASE 
                    WHEN ((((COALESCE(fs.total_saldo_masuk, 0) * 
                        CASE 
                            WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                            ELSE 0
                        END / 100) / NULLIF(bk.qty, 0)) / 1.11) * bk.qty) > 0 THEN
                        ((((((COALESCE(fs.total_saldo_masuk, 0) * 
                            CASE 
                                WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                    (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                                ELSE 0
                            END / 100) / NULLIF(bk.qty, 0)) / 1.11) - {$cogsFormula}) * bk.qty) / 
                        ((((COALESCE(fs.total_saldo_masuk, 0) * 
                            CASE 
                                WHEN COALESCE(ov.total_order_value, 0) > 0 THEN
                                    (COALESCE(pipv.initial_price, p.initial_price, 0) * bk.qty / ov.total_order_value) * 100
                                ELSE 0
                            END / 100) / NULLIF(bk.qty, 0)) / 1.11) * bk.qty)) * 100
                    ELSE 0
                END as margin_per_item
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
            LEFT JOIN product_initial_price_versions pipv ON pipv.id = (
                SELECT pipv2.id
                FROM product_initial_price_versions pipv2
                WHERE pipv2.product_id = p.id
                  AND COALESCE(pipv2.valid_from, pipv2.created_at) <= o.created_at
                  AND (pipv2.valid_until IS NULL OR pipv2.valid_until >= o.created_at)
                ORDER BY COALESCE(pipv2.valid_from, pipv2.created_at) DESC
                LIMIT 1
            )
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
            LEFT JOIN penerimaan_detail pd ON pd.id = ws.penerimaan_detail_id
            INNER JOIN financial_summary fs ON fs.no_order = o.order_number
            LEFT JOIN order_value ov ON ov.order_id = o.id
            {$whereClause}
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
                quantity,
                price,
                pricelist_total,
                proportion_percent,
                order_total_payment,
                order_total_payment_without_ppn,
                total_order_value_from_products,
                revenue,
                capital,
                modal_per_pcs,
                payment_per_product_per_pcs,
                payment_per_product_without_ppn,
                unit_cost,
                profit_per_pcs,
                gross_profit_total,
                margin_per_pcs,
                margin_per_item
            FROM final_data
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

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $this->request->filled('platform_id') ? (int) $this->request->input('platform_id') : null,
            'order_number' => $this->request->filled('order_number') ? trim($this->request->input('order_number')) : null,
            'search' => $this->request->filled('search') ? trim($this->request->input('search')) : null,
            'outstanding_status' => $this->request->filled('outstanding_status') ? $this->request->input('outstanding_status') : null,
            'brands' => array_filter((array) $this->request->input('brands', []), function($id) { return !empty($id); }),
            'sub_brands' => array_filter((array) $this->request->input('sub_brands', []), function($id) { return !empty($id); }),
            'product_categories' => array_filter((array) $this->request->input('product_categories', []), function($id) { return !empty($id); }),
            'product_types' => array_filter((array) $this->request->input('product_types', []), function($id) { return !empty($id); }),
            'product_sizes' => array_filter((array) $this->request->input('product_sizes', []), function($id) { return !empty($id); }),
            'product_variants' => array_filter((array) $this->request->input('product_variants', []), function($id) { return !empty($id); }),
        ];
    }
}

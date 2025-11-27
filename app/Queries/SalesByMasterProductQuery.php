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
        $sql = $this->buildQuery(false);
        $results = DB::select($sql);
        
        // Convert to arrays
        $results = array_map(function($row) {
            return (array) $row;
        }, $results);
        
        $totalRevenue = array_sum(array_column($results, 'revenue'));
        $totalCapital = array_sum(array_column($results, 'capital'));
        $totalQuantity = array_sum(array_column($results, 'quantity'));
        $totalRevenueWithoutPPN = $totalRevenue / 1.11; // Revenue without PPN
        $totalGrossProfit = $totalRevenueWithoutPPN - $totalCapital; // Revenue without PPN - capital
        
        // Count total barang keluar (same as total rows since each row is one barang_keluar)
        $totalBarangKeluar = count($results);
        
        return [
            'total_products' => $totalBarangKeluar,
            'total_rows' => $totalBarangKeluar,
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
        $joinConditions = [];

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

        // Payment filter - only orders with saldo_masuk > 0
        $paymentFilter = "
            (
                EXISTS (SELECT 1 FROM shopee_financial_transactions sft WHERE sft.no_order = o.order_number AND sft.saldo_masuk > 0)
                OR EXISTS (SELECT 1 FROM tiktok_financial_transactions tft WHERE tft.no_order = o.order_number AND tft.saldo_masuk > 0)
                OR EXISTS (SELECT 1 FROM tokopedia_financial_transactions toft WHERE toft.no_order = o.order_number AND toft.saldo_masuk > 0)
                OR EXISTS (SELECT 1 FROM blibli_financial_transactions bft WHERE bft.no_order = o.order_number AND bft.saldo_masuk > 0)
            )
        ";
        $whereConditions[] = $paymentFilter;

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
                COALESCE(p.initial_price, 0) as price,
                ws.id as warehouse_stock_id,
                pd.id as penerimaan_detail_id,
                pd.harga_hpp,
                pd.diskon_persen_1,
                pd.diskon_persen_2,
                pd.diskon_persen_3,
                pd.diskon_persen_4,
                pd.diskon_persen_5,
                pd.diskon_nominal_1,
                pd.diskon_nominal_2,
                pd.diskon_nominal_3,
                pd.diskon_nominal_4,
                pd.diskon_nominal_5,
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
                -- Calculate total order value from products (constant, not affected by filtering)
                (
                    SELECT COALESCE(SUM(COALESCE(p2.initial_price, 0) * bk2.qty), 0)
                    FROM barang_keluar bk2
                    INNER JOIN warehouse_stock ws2 ON ws2.id = bk2.warehouse_stock_id
                    INNER JOIN products p2 ON p2.id = ws2.product_id
                    INNER JOIN order_items oi2 ON oi2.id = bk2.order_item_id
                    WHERE oi2.order_id = o.id
                ) as total_order_value_from_products
            FROM orders o
            INNER JOIN platforms pl ON pl.id = o.platform_id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
            INNER JOIN mapping_barangs mb ON mb.platform_product_id = pp.id AND mb.is_active = 1
            INNER JOIN products p ON p.id = mb.product_id
            INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
            INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id AND ws.product_id = p.id
            LEFT JOIN penerimaan_detail pd ON pd.id = ws.penerimaan_detail_id
            {$whereClause}
        ";
    }

    protected function buildCalcCTE()
    {
        // Calculate COGS: sequential percentage discounts (each applied to previous result) then subtract nominal discounts
        // Formula: hpp * (1-d1/100) * (1-d2/100) * (1-d3/100) * (1-d4/100) * (1-d5/100) - sum(nominal_discounts)
        return "
            SELECT 
                *,
                -- Calculate COGS per unit from penerimaan detail
                CASE 
                    WHEN penerimaan_detail_id IS NOT NULL THEN
                        GREATEST(0,
                            COALESCE(harga_hpp, 0)
                            * (1 - COALESCE(diskon_persen_1, 0) / 100.0)
                            * (1 - COALESCE(diskon_persen_2, 0) / 100.0)
                            * (1 - COALESCE(diskon_persen_3, 0) / 100.0)
                            * (1 - COALESCE(diskon_persen_4, 0) / 100.0)
                            * (1 - COALESCE(diskon_persen_5, 0) / 100.0)
                            - COALESCE(diskon_nominal_1, 0)
                            - COALESCE(diskon_nominal_2, 0)
                            - COALESCE(diskon_nominal_3, 0)
                            - COALESCE(diskon_nominal_4, 0)
                            - COALESCE(diskon_nominal_5, 0)
                        )
                    ELSE 0
                END as cogs_per_unit,
                -- Calculate pricelist total (price * qty)
                COALESCE(price, 0) * COALESCE(master_qty, 0) as pricelist_total,
                -- Calculate proportion percent
                CASE 
                    WHEN total_order_value_from_products > 0 THEN
                        (COALESCE(price, 0) * COALESCE(master_qty, 0) / total_order_value_from_products) * 100
                    ELSE 0
                END as proportion_percent
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
                order_number,
                invoice_number,
                order_date,
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
                -- Calculate revenue allocation (proportional saldo masuk per line)
                (total_saldo_masuk * proportion_percent / 100) as revenue,
                -- Calculate capital (cogs_per_unit * qty)
                (cogs_per_unit * master_qty) as capital,
                cogs_per_unit as modal_per_pcs,
                -- Calculate payment per product per pcs (revenue / qty)
                ((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) as payment_per_product_per_pcs,
                -- Calculate payment per product per pcs without PPN
                (((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) as payment_per_product_without_ppn,
                -- Calculate unit cost (capital / qty)
                ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0)) as unit_cost,
                -- Calculate profit per pcs (payment_per_product_without_ppn - unit_cost)
                ((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) as profit_per_pcs,
                -- Calculate gross profit total (profit_per_pcs * qty)
                (((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) * master_qty) as gross_profit_total,
                -- Calculate margin per pcs (%)
                CASE 
                    WHEN (((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) > 0 THEN
                        (((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) / (((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11)) * 100
                    ELSE 0
                END as margin_per_pcs,
                -- Calculate margin per item (%)
                CASE 
                    WHEN ((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) * master_qty) > 0 THEN
                        ((((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) * master_qty) / ((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) * master_qty)) * 100
                    ELSE 0
                END as margin_per_item
            FROM calculated_data
            ORDER BY {$sortColumn} {$sortDirection}
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
            'brands' => (array) $this->request->input('brands', []),
            'sub_brands' => (array) $this->request->input('sub_brands', []),
            'product_categories' => (array) $this->request->input('product_categories', []),
            'product_types' => (array) $this->request->input('product_types', []),
            'product_sizes' => (array) $this->request->input('product_sizes', []),
            'product_variants' => (array) $this->request->input('product_variants', []),
        ];
    }
}


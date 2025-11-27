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
        
        // Count total platform products (same as total rows since each row is one order_item/platform_product)
        $totalPlatformProducts = count($results);
        
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
        // For Platform Product, we just pass through the data
        // COGS is already calculated in base_data as total_cogs_for_order_item
        return "
            SELECT 
                *
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
                platform_product_variant as product_variant,
                platform_quantity as quantity,
                total_saldo_masuk as revenue,
                total_cogs_for_order_item as capital,
                -- Calculate gross profit (revenue without PPN - capital)
                (total_saldo_masuk / 1.11) - total_cogs_for_order_item as gross_profit,
                -- Calculate price (for backward compatibility, though not used in platform product view)
                0 as price,
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


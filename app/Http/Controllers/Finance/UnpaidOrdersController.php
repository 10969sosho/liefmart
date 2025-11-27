<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UnpaidOrdersExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PathHelper;
use App\Helpers\SecurePathHelper;

class UnpaidOrdersController extends Controller
{
    /**
     * Display unpaid orders from all platforms
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get all platforms
        $platforms = Platform::all();
        
        // Simpan semua filter ke variabel
        $filters = [
            'platform' => $request->input('platform'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'order_number' => $request->input('order_number'),
            'customer_name' => $request->input('customer_name'),
            'min_value' => $request->input('min_value'),
            'max_value' => $request->input('max_value'),
            'min_age' => $request->input('min_age'),
            'max_age' => $request->input('max_age'),
            'sort_by' => $request->input('sort_by', 'tanggal'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        // Get Blibli platform ID
        $blibliPlatformId = Platform::whereRaw('LOWER(name) = ?', ['blibli'])->value('id');
        
        // Build base query with SQL calculations
        $buildQuery = function($isBlibli = false) use ($blibliPlatformId, $filters) {
            $query = $isBlibli 
                ? Order::withoutGlobalScope('mainCategory')
                : Order::query();
            
            // Base filters for unpaid orders
            if ($isBlibli) {
                $query->where('platform_id', $blibliPlatformId);
            } else {
                $query->where('platform_id', '!=', $blibliPlatformId);
            }
            
            $query->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions');
            
            // Platform filter
            if ($filters['platform']) {
                $isBlibliFilter = strtolower($filters['platform']) === 'blibli';
                if (($isBlibli && !$isBlibliFilter) || (!$isBlibli && $isBlibliFilter)) {
                    return null; // Skip this query
                }
                $query->whereHas('platform', function($q) use ($filters) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($filters['platform'])]);
                });
            }
            
            // Date filters
            if ($filters['from_date']) {
                $query->whereDate('tanggal', '>=', $filters['from_date']);
            }
            if ($filters['to_date']) {
                $query->whereDate('tanggal', '<=', $filters['to_date']);
            }
            
            // Order number filter
            if ($filters['order_number']) {
                $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
            }
            
            // Age filters
            if ($filters['min_age']) {
                $query->where('tanggal', '<=', now()->subDays($filters['min_age']));
            }
            if ($filters['max_age']) {
                $query->where('tanggal', '>=', now()->subDays($filters['max_age']));
            }
            
            // Add SQL calculations for order items
            $query->select([
                'orders.id',
                'orders.platform_id',
                'orders.order_number',
                'orders.tanggal',
                'orders.status',
                'orders.created_at',
                'orders.updated_at',
                DB::raw('COUNT(DISTINCT order_items.id) as total_items'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.price_after_discount * order_items.quantity), 0) as total_value'),
                DB::raw('COALESCE(DATEDIFF(NOW(), orders.tanggal), 0) as days_since_order'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as order_total_quantity'),
                DB::raw('COALESCE((
                    SELECT SUM(rpd.qty)
                    FROM retur_penjualan_details rpd
                    INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                    INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                    WHERE oi.order_id = orders.id
                    AND rp.status IN ("draft", "selesai")
                ), 0) as returned_quantity')
            ])
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.id', 'orders.platform_id', 'orders.order_number', 'orders.tanggal', 'orders.status', 'orders.created_at', 'orders.updated_at');
            
            // Value filters using having clause
            if ($filters['min_value']) {
                $query->havingRaw('total_value >= ?', [$filters['min_value']]);
            }
            if ($filters['max_value']) {
                $query->havingRaw('total_value <= ?', [$filters['max_value']]);
            }
            
            // Filter out fully returned orders (where returned_quantity >= order_total_quantity)
            $query->havingRaw('(returned_quantity < order_total_quantity OR returned_quantity = 0)');
            
            return $query;
        };
        
        // Build queries for both non-Blibli and Blibli
        $queryNonBlibli = $buildQuery(false);
        $queryBlibli = $buildQuery(true);
        
        // Get total count separately (without loading all data)
        $countNonBlibli = $queryNonBlibli ? $queryNonBlibli->count() : 0;
        $countBlibli = $queryBlibli ? $queryBlibli->count() : 0;
        $totalCount = $countNonBlibli + $countBlibli;
        
        // Get pagination parameters
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $sortBy = $filters['sort_by'] === 'tanggal' ? 'tanggal' : 'order_number';
        $sortOrder = $filters['sort_order'] === 'asc' ? 'asc' : 'desc';
        
        if ($totalCount == 0) {
            // No results
            $unpaidOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $summary = [
                'total_orders' => 0,
                'total_value' => 0,
                'platform_breakdown' => [],
                'age_breakdown' => [
                    '0-7_days' => 0,
                    '8-14_days' => 0,
                    '15-21_days' => 0,
                    '22-30_days' => 0,
                    '30+_days' => 0
                ]
            ];
            return view('financial.unpaid-orders.index', compact('unpaidOrders', 'platforms', 'summary'));
        }
        
        // Apply sorting to queries
        if ($queryNonBlibli) {
            $queryNonBlibli->orderBy($sortBy, $sortOrder);
        }
        if ($queryBlibli) {
            $queryBlibli->orderBy($sortBy, $sortOrder);
        }
        
        // Load limited data for pagination
        // We need to load enough data to cover the current page after merging
        // Strategy: Load data from both queries, merge, sort, then paginate
        // To minimize memory, we'll load enough to cover current page + buffer
        // Calculate based on page position: if page 1, load less; if later pages, load more
        $loadLimit = min($perPage * 3, ($page * $perPage) + ($perPage * 2));
        
        $resultsNonBlibli = $queryNonBlibli ? $queryNonBlibli->limit($loadLimit)->get() : collect();
        $resultsBlibli = $queryBlibli ? $queryBlibli->limit($loadLimit)->get() : collect();
        
        // Combine and sort results
        $allResults = $resultsNonBlibli->concat($resultsBlibli)
            ->sortBy([
                [$sortBy, $sortOrder]
            ])->values();
        
        // Get paginated slice
        $paginatedResults = $allResults->slice(($page - 1) * $perPage, $perPage)->values();
        
        // Convert to Order models
        $orderIds = $paginatedResults->pluck('id')->toArray();
        if (empty($orderIds)) {
            $unpaidOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                $totalCount,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            // Calculate summary using SQL queries (not from collection)
            $summary = $this->calculateSummaryFromQueries($queryNonBlibli, $queryBlibli);
            return view('financial.unpaid-orders.index', compact('unpaidOrders', 'platforms', 'summary'));
        }
        
        $orders = Order::with(['platform', 'mainCategory'])
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');
        
        // Map calculated fields to models
        $paginatedOrders = $paginatedResults->map(function($row) use ($orders) {
            $order = $orders->get($row->id);
            if ($order) {
                $order->total_items = (int)($row->total_items ?? 0);
                $order->total_quantity = (float)($row->total_quantity ?? 0);
                $order->total_value = (float)($row->total_value ?? 0);
                $order->days_since_order = (int)($row->days_since_order ?? 0);
            }
            return $order;
        })->filter();
        
        // Create paginator
        $unpaidOrders = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedOrders,
            $totalCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        // Calculate summary using SQL queries (not from collection to avoid loading all data)
        $summary = $this->calculateSummaryFromQueries($queryNonBlibli, $queryBlibli);

        return view('financial.unpaid-orders.index', compact(
            'unpaidOrders',
            'platforms',
            'summary'
        ));
    }

    /**
     * Export unpaid orders to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        // Use secure dynamic filename generation
        $filename = SecurePathHelper::getSafeFilename('unpaid_orders_' . date('Y-m-d_H-i-s') . '.xlsx');
        
        // Ensure secure temp directory exists
        SecurePathHelper::getSecureTempPath();
        
        // Clean up old temp files securely
        SecurePathHelper::cleanupSecureTempFiles();
        
        return Excel::download(new UnpaidOrdersExport($request->all()), $filename);
    }

    /**
     * Export unpaid orders to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        // Get Blibli platform ID
        $blibliPlatformId = Platform::whereRaw('LOWER(name) = ?', ['blibli'])->value('id');
        
        // Build base queries
        $platform = $request->input('platform');
        if (!$platform) {
            // No platform filter - get all platforms
            $queryNonBlibli = Order::with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->where('platform_id', '!=', $blibliPlatformId)
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions');
                
            $queryBlibli = Order::withoutGlobalScope('mainCategory')->with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->where('platform_id', $blibliPlatformId)
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions');
        } else {
            // Platform filter applied
            $isBlibli = strtolower($platform) === 'blibli';
            
            $queryNonBlibli = $isBlibli ? null : Order::with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions')
                ->whereHas('platform', function($q) use ($platform) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($platform)]);
                });
                
            $queryBlibli = $isBlibli ? Order::withoutGlobalScope('mainCategory')->with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions')
                ->whereHas('platform', function($q) use ($platform) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($platform)]);
                }) : null;
        }

        // Apply filters to queries
        $applyFilters = function($query) use ($request) {
            if (!$query) return $query;
            
            if ($request->filled('from_date')) {
                $query->whereDate('tanggal', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('tanggal', '<=', $request->to_date);
            }
            if ($request->filled('order_number')) {
                $query->where('order_number', 'like', '%' . $request->order_number . '%');
            }
            if ($request->filled('min_value')) {
                $query->whereHas('orderItems', function($q) use ($request) {
                    $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                      ->groupBy('order_id')
                      ->having('total_value', '>=', $request->min_value);
                });
            }
            if ($request->filled('max_value')) {
                $query->whereHas('orderItems', function($q) use ($request) {
                    $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                      ->groupBy('order_id')
                      ->having('total_value', '<=', $request->max_value);
                });
            }
            if ($request->filled('min_age')) {
                $query->where('tanggal', '<=', now()->subDays($request->min_age));
            }
            if ($request->filled('max_age')) {
                $query->where('tanggal', '>=', now()->subDays($request->max_age));
            }
            
            return $query;
        };

        // Apply filters to both queries
        $queryNonBlibli = $applyFilters($queryNonBlibli);
        $queryBlibli = $applyFilters($queryBlibli);

        // Get results and combine
        $ordersNonBlibli = $queryNonBlibli ? $queryNonBlibli->get() : collect();
        $ordersBlibli = $queryBlibli ? $queryBlibli->get() : collect();
        $allOrders = $ordersNonBlibli->concat($ordersBlibli)->unique('id');
        
        // Filter out fully returned orders
        $allOrders = $allOrders->filter(function($order) {
            return !$order->isFullyReturned();
        });

        // Sort collection
        $sortBy = $request->get('sort_by', 'tanggal') === 'tanggal' ? 'tanggal' : 'order_number';
        $sortOrder = $request->get('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        
        $allOrders = $allOrders->sortBy([
            [$sortBy, $sortOrder]
        ]);

        $summary = $this->calculateSummaryFromCollection($allOrders);

        // Use dynamic filename generation
        $filename = SecurePathHelper::getSafeFilename('unpaid_orders_' . date('Y-m-d_H-i-s') . '.pdf');
        
        // Ensure secure temp directory exists
        SecurePathHelper::getSecureTempPath();
        
        // Clean up old temp files securely
        SecurePathHelper::cleanupSecureTempFiles();
        
        $pdf = Pdf::loadView('exports.financial.unpaid-orders', compact('allOrders', 'summary'))
                  ->setPaper('a4', 'landscape');
        
        return $pdf->download($filename);
    }

    /**
     * Calculate summary statistics for unpaid orders
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function calculateSummary($query)
    {
        $orders = $query->with('orderItems')->get();
        
        $totalOrders = $orders->count();
        $totalValue = 0;
        $platformBreakdown = [];
        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15-21_days' => 0,
            '22-30_days' => 0,
            '30+_days' => 0
        ];

        foreach ($orders as $order) {
            // Calculate order value
            $orderValue = 0;
            foreach ($order->orderItems as $item) {
                $orderValue += $item->price_after_discount * $item->quantity;
            }
            $totalValue += $orderValue;

            // Platform breakdown
            $platformName = $order->platform->name ?? 'Unknown';
            if (!isset($platformBreakdown[$platformName])) {
                $platformBreakdown[$platformName] = [
                    'count' => 0,
                    'value' => 0
                ];
            }
            $platformBreakdown[$platformName]['count']++;
            $platformBreakdown[$platformName]['value'] += $orderValue;

            // Age breakdown
            $daysSinceOrder = $order->tanggal ? $order->tanggal->diffInDays(now()) : 0;
            if ($daysSinceOrder <= 7) {
                $ageBreakdown['0-7_days']++;
            } elseif ($daysSinceOrder <= 14) {
                $ageBreakdown['8-14_days']++;
            } elseif ($daysSinceOrder <= 21) {
                $ageBreakdown['15-21_days']++;
            } elseif ($daysSinceOrder <= 30) {
                $ageBreakdown['22-30_days']++;
            } else {
                $ageBreakdown['30+_days']++;
            }
        }

        return [
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'platform_breakdown' => $platformBreakdown,
            'age_breakdown' => $ageBreakdown
        ];
    }

    // Tambahkan fungsi baru untuk summary dari collection (menggunakan data yang sudah dihitung dari SQL)
    private function calculateSummaryFromCollection($orders)
    {
        $totalOrders = $orders->count();
        $totalValue = 0;
        $platformBreakdown = [];
        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15-21_days' => 0,
            '22-30_days' => 0,
            '30+_days' => 0
        ];
        
        foreach ($orders as $order) {
            // Use pre-calculated values from SQL if available, otherwise calculate
            $orderValue = isset($order->total_value) ? (float)$order->total_value : 0;
            if ($orderValue == 0 && $order->relationLoaded('orderItems')) {
                foreach ($order->orderItems as $item) {
                    $orderValue += $item->price_after_discount * $item->quantity;
                }
            }
            
            $totalValue += $orderValue;
            $platformName = $order->platform->name ?? 'Unknown';
            if (!isset($platformBreakdown[$platformName])) {
                $platformBreakdown[$platformName] = [
                    'count' => 0,
                    'value' => 0
                ];
            }
            $platformBreakdown[$platformName]['count']++;
            $platformBreakdown[$platformName]['value'] += $orderValue;
            
            // Use pre-calculated days_since_order if available
            $daysSinceOrder = isset($order->days_since_order) ? (int)$order->days_since_order : 0;
            if ($daysSinceOrder == 0 && $order->tanggal) {
                $daysSinceOrder = $order->tanggal->diffInDays(now());
            }
            
            if ($daysSinceOrder <= 7) {
                $ageBreakdown['0-7_days']++;
            } elseif ($daysSinceOrder <= 14) {
                $ageBreakdown['8-14_days']++;
            } elseif ($daysSinceOrder <= 21) {
                $ageBreakdown['15-21_days']++;
            } elseif ($daysSinceOrder <= 30) {
                $ageBreakdown['22-30_days']++;
            } else {
                $ageBreakdown['30+_days']++;
            }
        }
        return [
            'total_orders' => $totalOrders, 
            'total_value' => $totalValue,
            'platform_breakdown' => $platformBreakdown,
            'age_breakdown' => $ageBreakdown
        ];
    }

    /**
     * Calculate summary statistics from two separate queries (non-Blibli and Blibli)
     * without loading all data into memory
     *
     * @param \Illuminate\Database\Eloquent\Builder|null $queryNonBlibli
     * @param \Illuminate\Database\Eloquent\Builder|null $queryBlibli
     * @return array
     */
    private function calculateSummaryFromQueries($queryNonBlibli, $queryBlibli)
    {
        $totalOrders = 0;
        $totalValue = 0;
        $platformBreakdown = [];
        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15-21_days' => 0,
            '22-30_days' => 0,
            '30+_days' => 0
        ];
        
        // Process non-Blibli query
        if ($queryNonBlibli) {
            $summary = $this->calculateSummaryFromSQL($queryNonBlibli);
            $totalOrders += $summary['total_orders'];
            $totalValue += $summary['total_value'];
            
            foreach ($summary['platform_breakdown'] as $platform => $data) {
                if (!isset($platformBreakdown[$platform])) {
                    $platformBreakdown[$platform] = ['count' => 0, 'value' => 0];
                }
                $platformBreakdown[$platform]['count'] += $data['count'];
                $platformBreakdown[$platform]['value'] += $data['value'];
            }
            
            foreach ($summary['age_breakdown'] as $age => $count) {
                $ageBreakdown[$age] += $count;
            }
        }
        
        // Process Blibli query
        if ($queryBlibli) {
            $summary = $this->calculateSummaryFromSQL($queryBlibli);
            $totalOrders += $summary['total_orders'];
            $totalValue += $summary['total_value'];
            
            foreach ($summary['platform_breakdown'] as $platform => $data) {
                if (!isset($platformBreakdown[$platform])) {
                    $platformBreakdown[$platform] = ['count' => 0, 'value' => 0];
                }
                $platformBreakdown[$platform]['count'] += $data['count'];
                $platformBreakdown[$platform]['value'] += $data['value'];
            }
            
            foreach ($summary['age_breakdown'] as $age => $count) {
                $ageBreakdown[$age] += $count;
            }
        }
        
        return [
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'platform_breakdown' => $platformBreakdown,
            'age_breakdown' => $ageBreakdown
        ];
    }

    /**
     * Calculate summary statistics using SQL
     *
     * @param \Illuminate\Database\Eloquent\Builder|null $query
     * @return array
     */
    private function calculateSummaryFromSQL($query)
    {
        if (!$query) {
            return [
                'total_orders' => 0,
                'total_value' => 0,
                'platform_breakdown' => [],
                'age_breakdown' => [
                    '0-7_days' => 0,
                    '8-14_days' => 0,
                    '15-21_days' => 0,
                    '22-30_days' => 0,
                    '30+_days' => 0
                ]
            ];
        }
        
        // Get main category ID for binding
        $mainCategoryId = \App\Helpers\MainCategoryHelper::getSelectedMainCategoryId();
        
        // Check if this is Blibli query (no main category filter)
        $isBlibli = $query->getQuery()->wheres;
        $isBlibliQuery = false;
        foreach ($isBlibli as $where) {
            if (isset($where['column']) && $where['column'] === 'platform_id' && isset($where['value']) && $where['value'] == 4) {
                $isBlibliQuery = true;
                break;
            }
        }
        
        // Build base SQL query
        $baseSql = "
            SELECT 
                orders.id,
                orders.platform_id,
                orders.order_number,
                orders.tanggal,
                orders.status,
                orders.created_at,
                orders.updated_at,
                COUNT(DISTINCT order_items.id) as total_items,
                COALESCE(SUM(order_items.quantity), 0) as total_quantity,
                COALESCE(SUM(order_items.price_after_discount * order_items.quantity), 0) as total_value,
                COALESCE(DATEDIFF(NOW(), orders.tanggal), 0) as days_since_order,
                COALESCE(SUM(order_items.quantity), 0) as order_total_quantity,
                COALESCE((
                    SELECT SUM(rpd.qty)
                    FROM retur_penjualan_details rpd
                    INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                    INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                    WHERE oi.order_id = orders.id
                    AND rp.status IN ('draft', 'selesai')
                ), 0) as returned_quantity
            FROM orders
            LEFT JOIN order_items ON orders.id = order_items.order_id
            WHERE 1=1
        ";
        
        $bindings = [];
        
        // Add platform filter
        $platformCondition = $query->getQuery()->wheres;
        foreach ($platformCondition as $where) {
            if (isset($where['column']) && $where['column'] === 'platform_id') {
                if (isset($where['operator']) && $where['operator'] === '!=') {
                    $baseSql .= " AND platform_id != ?";
                    $bindings[] = $where['value'];
                } elseif (isset($where['operator']) && $where['operator'] === '=') {
                    $baseSql .= " AND platform_id = ?";
                    $bindings[] = $where['value'];
                }
                break;
            }
        }
        
        // Add financial transactions filters
        $baseSql .= "
            AND NOT EXISTS (SELECT * FROM shopee_financial_transactions WHERE orders.id = shopee_financial_transactions.order_id)
            AND NOT EXISTS (SELECT * FROM tokopedia_financial_transactions WHERE orders.id = tokopedia_financial_transactions.order_id)
            AND NOT EXISTS (SELECT * FROM tiktok_financial_transactions WHERE orders.id = tiktok_financial_transactions.order_id)
            AND NOT EXISTS (SELECT * FROM blibli_financial_transactions WHERE orders.id = blibli_financial_transactions.order_id)
        ";
        
        // Add main category filter only for non-Blibli queries
        if (!$isBlibliQuery && $mainCategoryId) {
            $baseSql .= "
                AND EXISTS (
                    SELECT *
                    FROM order_items oi2
                    WHERE oi2.order_id = orders.id
                    AND EXISTS (
                        SELECT warehouse_stock.*
                        FROM warehouse_stock
                        INNER JOIN products ON warehouse_stock.product_id = products.id
                        WHERE oi2.warehouse_stock_id = warehouse_stock.id
                        AND products.main_category_id = ?
                    )
                )
            ";
            $bindings[] = $mainCategoryId;
        }
        
        // Add GROUP BY and HAVING
        $baseSql .= "
            GROUP BY orders.id, orders.platform_id, orders.order_number, 
                     orders.tanggal, orders.status, orders.created_at, orders.updated_at
            HAVING (returned_quantity < order_total_quantity OR returned_quantity = 0)
        ";
        
        // Get overall summary
        $overallSummary = DB::select("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_value) as total_value,
                SUM(CASE WHEN days_since_order <= 7 THEN 1 ELSE 0 END) as age_0_7,
                SUM(CASE WHEN days_since_order > 7 AND days_since_order <= 14 THEN 1 ELSE 0 END) as age_8_14,
                SUM(CASE WHEN days_since_order > 14 AND days_since_order <= 21 THEN 1 ELSE 0 END) as age_15_21,
                SUM(CASE WHEN days_since_order > 21 AND days_since_order <= 30 THEN 1 ELSE 0 END) as age_22_30,
                SUM(CASE WHEN days_since_order > 30 THEN 1 ELSE 0 END) as age_30_plus
            FROM ({$baseSql}) AS overall_query
        ", $bindings);
        
        $overallSummary = $overallSummary[0] ?? (object)[
            'total_orders' => 0,
            'total_value' => 0,
            'age_0_7' => 0,
            'age_8_14' => 0,
            'age_15_21' => 0,
            'age_22_30' => 0,
            'age_30_plus' => 0
        ];
        
        $totalOrders = $overallSummary->total_orders ?? 0;
        $totalValue = $overallSummary->total_value ?? 0;
        $ageBreakdown = [
            '0-7_days' => $overallSummary->age_0_7 ?? 0,
            '8-14_days' => $overallSummary->age_8_14 ?? 0,
            '15-21_days' => $overallSummary->age_15_21 ?? 0,
            '22-30_days' => $overallSummary->age_22_30 ?? 0,
            '30+_days' => $overallSummary->age_30_plus ?? 0
        ];
        
        // Get platform breakdown
        $platformData = DB::select("
            SELECT 
                platform_id,
                COUNT(*) as count,
                SUM(total_value) as value
            FROM ({$baseSql}) AS platform_query
            GROUP BY platform_id
        ", $bindings);
        
        // Get platform names
        $platformIds = collect($platformData)->pluck('platform_id')->unique()->filter()->toArray();
        $platforms = !empty($platformIds) 
            ? Platform::whereIn('id', $platformIds)->get()->keyBy('id')
            : collect();
        
        $platformBreakdown = [];
        foreach ($platformData as $row) {
            $platformName = $platforms->get($row->platform_id)->name ?? 'Unknown';
            if (!isset($platformBreakdown[$platformName])) {
                $platformBreakdown[$platformName] = [
                    'count' => 0,
                    'value' => 0
                ];
            }
            $platformBreakdown[$platformName]['count'] += $row->count ?? 0;
            $platformBreakdown[$platformName]['value'] += $row->value ?? 0;
        }
        
        return [
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'platform_breakdown' => $platformBreakdown,
            'age_breakdown' => $ageBreakdown
        ];
    }

} 
<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
        
        // Build base queries
        if (!$filters['platform']) {
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
            $isBlibli = strtolower($filters['platform']) === 'blibli';
            
            $queryNonBlibli = $isBlibli ? null : Order::with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions')
                ->whereHas('platform', function($q) use ($filters) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($filters['platform'])]);
                });
                
            $queryBlibli = $isBlibli ? Order::withoutGlobalScope('mainCategory')->with(['orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions')
                ->whereHas('platform', function($q) use ($filters) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($filters['platform'])]);
                }) : null;
        }

        // Apply filters to queries
        $applyFilters = function($query) use ($filters) {
            if (!$query) return $query;
            
            if ($filters['from_date']) {
                $query->whereDate('tanggal', '>=', $filters['from_date']);
            }
            if ($filters['to_date']) {
                $query->whereDate('tanggal', '<=', $filters['to_date']);
            }
            if ($filters['order_number']) {
                $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
            }
            if ($filters['customer_name']) {
                $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
            }
            if ($filters['min_value']) {
                $query->whereHas('orderItems', function($q) use ($filters) {
                    $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                      ->groupBy('order_id')
                      ->having('total_value', '>=', $filters['min_value']);
                });
            }
            if ($filters['max_value']) {
                $query->whereHas('orderItems', function($q) use ($filters) {
                    $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                      ->groupBy('order_id')
                      ->having('total_value', '<=', $filters['max_value']);
                });
            }
            if ($filters['min_age']) {
                $query->where('tanggal', '<=', now()->subDays($filters['min_age']));
            }
            if ($filters['max_age']) {
                $query->where('tanggal', '>=', now()->subDays($filters['max_age']));
            }
            
            return $query;
        };

        // Apply filters to both queries
        $queryNonBlibli = $applyFilters($queryNonBlibli);
        $queryBlibli = $applyFilters($queryBlibli);

        // Get results and combine
        $ordersNonBlibli = $queryNonBlibli ? $queryNonBlibli->get() : collect();
        $ordersBlibli = $queryBlibli ? $queryBlibli->get() : collect();
        
        // Get fully returned orders (these should be moved to unpaid orders)
        $fullyReturnedOrders = $this->getFullyReturnedOrdersWithPayments($filters);
        
        // Combine all orders
        $allOrders = $ordersNonBlibli->concat($ordersBlibli)->concat($fullyReturnedOrders)->unique('id');

        // Sort collection
        $sortBy = $filters['sort_by'] === 'tanggal' ? 'tanggal' : 'order_number';
        $sortOrder = $filters['sort_order'] === 'asc' ? 'asc' : 'desc';
        
        $allOrders = $allOrders->sortBy([
            [$sortBy, $sortOrder]
        ]);

        // Manual pagination
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $unpaidOrders = new \Illuminate\Pagination\LengthAwarePaginator(
            $allOrders->forPage($page, $perPage),
            $allOrders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Calculate summary from the same filtered data
        $summary = $this->calculateSummaryFromCollection($allOrders);

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
            if ($request->filled('customer_name')) {
                $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
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

    // Tambahkan fungsi baru untuk summary dari collection
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
            $orderValue = 0;
            foreach ($order->orderItems as $item) {
                $orderValue += $item->price_after_discount * $item->quantity;
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

    /**
     * Get fully returned orders that have financial transactions
     * These should be moved to unpaid orders list with "RETUR" note
     */
    private function getFullyReturnedOrdersWithPayments($filters)
    {
        // Get orders that have financial transactions but are fully returned
        $query = Order::with(['orderItems.platformProduct.mappingBarang', 'platform', 'mainCategory'])
            ->where(function($q) {
                // Must have at least one financial transaction
                $q->whereHas('shopeeFinancialTransactions')
                  ->orWhereHas('tokopediaFinancialTransactions')
                  ->orWhereHas('tiktokFinancialTransactions')
                  ->orWhereHas('blibliFinancialTransactions');
            });

        // Apply platform filter if specified
        if ($filters['platform']) {
            $query->whereHas('platform', function($q) use ($filters) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($filters['platform'])]);
            });
        }

        // Apply date filters
        if ($filters['from_date']) {
            $query->whereDate('tanggal', '>=', $filters['from_date']);
        }
        if ($filters['to_date']) {
            $query->whereDate('tanggal', '<=', $filters['to_date']);
        }
        if ($filters['order_number']) {
            $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }
        if ($filters['customer_name']) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        $orders = $query->get();
        
        // Filter to only fully returned orders
        $fullyReturnedOrders = $orders->filter(function($order) {
            return $order->isFullyReturned();
        });

        // Add special marker for these orders to show they are returns
        $fullyReturnedOrders->each(function($order) {
            $order->is_return_unpaid = true;
            $order->unpaid_reason = 'RETUR FULL';
        });

        return $fullyReturnedOrders;
    }
} 
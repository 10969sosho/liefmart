<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\ShopeeFinancialTransaction;
use App\Models\Shopee2FinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\Tiktok2FinancialTransaction;
use App\Models\OfflineSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get current month and previous month dates
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Get platform-wise sales
        $shopeeSales = $this->getPlatformSales('shopee', $currentMonth);
        $shopee2Sales = $this->getPlatformSales('shopee2', $currentMonth);
        $tiktokSales = $this->getPlatformSales('tiktok', $currentMonth);
        $tiktok2Sales = $this->getPlatformSales('tiktok2', $currentMonth);
        $offlineSales = $this->getOfflineSales($currentMonth);
        
        // Calculate growth percentages
        $shopeeGrowth = $this->calculateGrowth('shopee', $currentMonth, $previousMonth);
        $shopee2Growth = $this->calculateGrowth('shopee2', $currentMonth, $previousMonth);
        $tiktokGrowth = $this->calculateGrowth('tiktok', $currentMonth, $previousMonth);
        $tiktok2Growth = $this->calculateGrowth('tiktok2', $currentMonth, $previousMonth);
        $offlineGrowth = $this->calculateOfflineGrowth($currentMonth, $previousMonth);
        
        // Calculate platform distribution percentages
        $totalSales = $shopeeSales + $shopee2Sales + $tiktokSales + $tiktok2Sales + $offlineSales;
        $shopeePercentage = $totalSales > 0 ? round(($shopeeSales / $totalSales) * 100, 1) : 0;
        $shopee2Percentage = $totalSales > 0 ? round(($shopee2Sales / $totalSales) * 100, 1) : 0;
        $tiktokPercentage = $totalSales > 0 ? round(($tiktokSales / $totalSales) * 100, 1) : 0;
        $tiktok2Percentage = $totalSales > 0 ? round(($tiktok2Sales / $totalSales) * 100, 1) : 0;
        $offlinePercentage = $totalSales > 0 ? round(($offlineSales / $totalSales) * 100, 1) : 0;
        
        // Get chart data
        $chartData = $this->getChartData();
        
        // Get recent transactions
        $recentTransactions = $this->getRecentTransactions();
        
        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();
        
        return view('dashboard', compact(
            'shopeeSales',
            'shopee2Sales',
            'tiktokSales',
            'tiktok2Sales',
            'offlineSales',
            'shopeeGrowth',
            'shopee2Growth',
            'tiktokGrowth',
            'tiktok2Growth',
            'offlineGrowth',
            'shopeePercentage',
            'shopee2Percentage',
            'tiktokPercentage',
            'tiktok2Percentage',
            'offlinePercentage',
            'chartData',
            'recentTransactions',
            'lowStockProducts'
        ));
    }

    /**
     * Get sales for a specific platform
     */
    private function getPlatformSales($platform, $month)
    {
        $model = match($platform) {
            'shopee' => ShopeeFinancialTransaction::class,
            'shopee2' => Shopee2FinancialTransaction::class,
            'tiktok' => TiktokFinancialTransaction::class,
            'tiktok2' => Tiktok2FinancialTransaction::class,
            default => null
        };
        
        if (!$model) return 0;
        
        return $model::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix');
    }
    
    /**
     * Calculate growth percentage for a platform
     */
    private function calculateGrowth($platform, $currentMonth, $previousMonth)
    {
        $currentSales = $this->getPlatformSales($platform, $currentMonth);
        $previousSales = $this->getPlatformSales($platform, $previousMonth);
        
        if ($previousSales == 0) return 0;
        
        return round((($currentSales - $previousSales) / $previousSales) * 100, 1);
    }
    
    /**
     * Get chart data for revenue trends
     */
    private function getChartData()
    {
        $months = collect(range(5, 0))->map(function($i) {
            return Carbon::now()->subMonths($i);
        });
        
        $chartLabels = $months->map(function($month) {
            return $month->format('M Y');
        })->toArray();
        
        $chartData = $months->map(function($month) {
            return ShopeeFinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   Shopee2FinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   TiktokFinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   Tiktok2FinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   OfflineSale::whereMonth('sale_date', $month->month)
                    ->whereYear('sale_date', $month->year)
                    ->sum('total_amount');
        })->toArray();
        
        return [
            'labels' => $chartLabels,
            'data' => $chartData
        ];
    }
    
    /**
     * Get recent transactions from all platforms
     */
    private function getRecentTransactions()
    {
        $shopeeTransactions = ShopeeFinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Shopee Lamourad' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);

        $shopee2Transactions = Shopee2FinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Shopee Liefmarket' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        $tiktokTransactions = TiktokFinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Tiktok Lamourad' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);

        $tiktok2Transactions = Tiktok2FinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Tiktok Liefmarket' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        return $shopeeTransactions->union($shopee2Transactions)
                                ->union($tiktokTransactions)
                                ->union($tiktok2Transactions)
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
    }
    
    /**
     * Get products with low stock
     */
    private function getLowStockProducts()
    {
        $mainCategoryId = session('main_category_id');
        
        return DB::table('warehouse_stock')
            ->join('products', 'warehouse_stock.product_id', '=', 'products.id')
            ->where('products.main_category_id', $mainCategoryId)
            ->select('products.id', 'products.name', 'products.sku', DB::raw('SUM(warehouse_stock.qty) as total_stock'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->having('total_stock', '<=', 10)
            ->orderBy('total_stock', 'asc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'stock' => $item->total_stock,
                    'min_stock' => 10 // You might want to make this configurable
                ];
            });
    }
    
    /**
     * Get offline sales for a specific month
     */
    private function getOfflineSales($month)
    {
        return OfflineSale::whereMonth('sale_date', $month->month)
                          ->whereYear('sale_date', $month->year)
                          ->sum('total_amount');
    }
    
    /**
     * Calculate growth percentage for offline sales
     */
    private function calculateOfflineGrowth($currentMonth, $previousMonth)
    {
        $currentSales = $this->getOfflineSales($currentMonth);
        $previousSales = $this->getOfflineSales($previousMonth);
        
        if ($previousSales == 0) return 0;
        
        return round((($currentSales - $previousSales) / $previousSales) * 100, 1);
    }
}
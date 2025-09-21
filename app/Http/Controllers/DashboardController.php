<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TokopediaFinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
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
        $tokopediaSales = $this->getPlatformSales('tokopedia', $currentMonth);
        $tiktokSales = $this->getPlatformSales('tiktok', $currentMonth);
        $blibliSales = $this->getPlatformSales('blibli', $currentMonth);
        
        // Calculate growth percentages
        $shopeeGrowth = $this->calculateGrowth('shopee', $currentMonth, $previousMonth);
        $tokopediaGrowth = $this->calculateGrowth('tokopedia', $currentMonth, $previousMonth);
        $tiktokGrowth = $this->calculateGrowth('tiktok', $currentMonth, $previousMonth);
        $blibliGrowth = $this->calculateGrowth('blibli', $currentMonth, $previousMonth);
        
        // Calculate platform distribution percentages
        $totalSales = $shopeeSales + $tokopediaSales + $tiktokSales + $blibliSales;
        $shopeePercentage = $totalSales > 0 ? round(($shopeeSales / $totalSales) * 100, 1) : 0;
        $tokopediaPercentage = $totalSales > 0 ? round(($tokopediaSales / $totalSales) * 100, 1) : 0;
        $tiktokPercentage = $totalSales > 0 ? round(($tiktokSales / $totalSales) * 100, 1) : 0;
        $blibliPercentage = $totalSales > 0 ? round(($blibliSales / $totalSales) * 100, 1) : 0;
        
        // Get chart data
        $chartData = $this->getChartData();
        
        // Get recent transactions
        $recentTransactions = $this->getRecentTransactions();
        
        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();
        
        return view('dashboard', compact(
            'shopeeSales',
            'tokopediaSales',
            'tiktokSales',
            'blibliSales',
            'shopeeGrowth',
            'tokopediaGrowth',
            'tiktokGrowth',
            'blibliGrowth',
            'shopeePercentage',
            'tokopediaPercentage',
            'tiktokPercentage',
            'blibliPercentage',
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
            'tokopedia' => TokopediaFinancialTransaction::class,
            'tiktok' => TiktokFinancialTransaction::class,
            'blibli' => BlibliFinancialTransaction::class,
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
                   TokopediaFinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   TiktokFinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix') +
                   BlibliFinancialTransaction::whereMonth('tanggal_order', $month->month)
                    ->whereYear('tanggal_order', $month->year)
                    ->sum('nominal_fix');
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
            ->selectRaw("'Shopee' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        $tokopediaTransactions = TokopediaFinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Tokopedia' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        $tiktokTransactions = TiktokFinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'TikTok' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        $blibliTransactions = BlibliFinancialTransaction::select('id', 'nominal_fix as amount', 'tanggal_order as created_at')
            ->selectRaw("'Blibli' as platform, 'Sale' as type")
            ->latest('tanggal_order')
            ->limit(5);
            
        return $shopeeTransactions->union($tokopediaTransactions)
                                ->union($tiktokTransactions)
                                ->union($blibliTransactions)
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
}
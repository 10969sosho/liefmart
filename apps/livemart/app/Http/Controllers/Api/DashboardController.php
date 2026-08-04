<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // Total orders
        $totalOrders = Order::count();

        // Total customers
        $totalCustomers = Customer::count();

        // Total products
        $totalProducts = Product::count();

        // Mock sales data
        $salesByPlatform = collect([
            ['platform' => 'shopee', 'total_sales' => 5000000, 'total_orders' => 25, 'growth_percentage' => 12.5],
            ['platform' => 'shopee2', 'total_sales' => 3500000, 'total_orders' => 18, 'growth_percentage' => 8.3],
            ['platform' => 'tiktok', 'total_sales' => 4200000, 'total_orders' => 22, 'growth_percentage' => 15.2],
            ['platform' => 'tiktok2', 'total_sales' => 2800000, 'total_orders' => 15, 'growth_percentage' => -2.1],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => 15500000,
                'total_orders' => $totalOrders,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'sales_by_platform' => $salesByPlatform,
            ],
        ]);
    }

    public function chartData(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now());
        $platform = $request->input('platform');

        $query = Order::whereBetween('order_date', [$startDate, $endDate]);
        
        if ($platform) {
            $query->where('platform', $platform);
        }

        $data = $query->select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as total_orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function recentTransactions(Request $request)
    {
        $limit = $request->input('limit', 10);

        $transactions = Order::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'platform' => $order->platform,
                    'customer_name' => $order->customer?->name ?? 'Guest',
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->input('threshold', 10);

        $products = Product::where('stock', '<=', $threshold)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}

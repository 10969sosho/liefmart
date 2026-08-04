<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsApiController extends Controller
{
    public function salesValue(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $query = Order::whereBetween('order_date', [$request->start_date, $request->end_date])
            ->where('status', 'completed');

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        $data = $query->select(
                DB::raw('DATE(order_date) as date'),
                'platform',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as avg_order')
            )
            ->groupBy('date', 'platform')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function salesVolume(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $data = OrderItem::whereHas('order', function ($q) use ($request) {
                $q->whereBetween('order_date', [$request->start_date, $request->end_date])
                  ->where('status', 'completed');
            })
            ->with('product')
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function grossProfit(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $data = OrderItem::whereHas('order', function ($q) use ($request) {
                $q->whereBetween('order_date', [$request->start_date, $request->end_date])
                  ->where('status', 'completed');
            })
            ->with('product')
            ->select(
                'product_id',
                DB::raw('SUM(qty) as qty_sold'),
                DB::raw('SUM(subtotal) as revenue'),
                DB::raw('SUM(qty * COALESCE((SELECT initial_price FROM products WHERE id = order_items.product_id), 0)) as cost')
            )
            ->groupBy('product_id')
            ->get()
            ->map(function ($item) {
                $grossProfit = $item->revenue - $item->cost;
                $margin = $item->revenue > 0 ? ($grossProfit / $item->revenue) * 100 : 0;
                
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown',
                    'qty_sold' => $item->qty_sold,
                    'revenue' => $item->revenue,
                    'cost' => $item->cost,
                    'gross_profit' => $grossProfit,
                    'margin' => round($margin, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function monthlySummary(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $data = Order::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select(
                'platform',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('platform')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function salesByPlatform(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $data = Order::whereBetween('order_date', [$request->start_date, $request->end_date])
            ->where('status', 'completed')
            ->select(
                'platform',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('platform')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function salesDetail(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $query = Order::with(['customer', 'items.product'])
            ->whereBetween('order_date', [$request->start_date, $request->end_date]);

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function dispatchExport(Request $request)
    {
        // Mock export dispatch
        return response()->json([
            'success' => true,
            'data' => ['export_id' => rand(1, 1000)],
            'message' => 'Export job dispatched',
        ]);
    }

    public function listExports(Request $request)
    {
        // Mock export list
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function downloadExport($id)
    {
        // Mock download
        return response()->json([
            'success' => false,
            'message' => 'Export not found',
        ], 404);
    }
}

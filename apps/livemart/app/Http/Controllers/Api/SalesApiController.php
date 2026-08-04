<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SalesApiController extends Controller
{
    public function getOrders(Request $request)
    {
        $query = Order::with(['customer']);

        // Filters
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->where('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('order_date', '<=', $request->end_date);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->input('per_page', 20);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($orders);
    }

    public function getOrderById($id)
    {
        $order = Order::with(['customer', 'items.product'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function deleteOrder($id, Request $request)
    {
        $order = Order::findOrFail($id);
        
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya order dengan status pending yang bisa dihapus',
            ], 400);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dihapus',
        ]);
    }

    public function storeOfflineSale(Request $request)
    {
        $request->validate([
            'order_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = 'OFF-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 5, '0', STR_PAD_LEFT);

            $totalAmount = collect($request->items)->sum(function ($item) {
                return $item['qty'] * $item['price'];
            });

            $order = Order::create([
                'order_number' => $orderNumber,
                'platform' => 'offline',
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            // Create order items
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);

                // Reduce stock
                Product::where('id', $item['product_id'])->decrement('stock', $item['qty']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load('items.product'),
                'message' => 'Penjualan offline berhasil disimpan',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating offline sale: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan penjualan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function previewImport($platform, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            $header = array_shift($rows);
            
            $previewData = [];
            $validRows = 0;
            $errorRows = 0;

            foreach ($rows as $index => $row) {
                if (empty($row[0])) continue;

                $hasError = false;
                $errorMessage = '';

                // Validate row
                if (empty($row[0])) {
                    $hasError = true;
                    $errorMessage = 'Order number kosong';
                }

                $previewData[] = [
                    'order_number' => $row[0] ?? '',
                    'order_date' => $row[1] ?? '',
                    'customer_name' => $row[2] ?? '',
                    'total_amount' => $row[3] ?? 0,
                    'has_error' => $hasError,
                    'error_message' => $errorMessage,
                ];

                if ($hasError) {
                    $errorRows++;
                } else {
                    $validRows++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_rows' => count($previewData),
                    'valid_rows' => $validRows,
                    'error_rows' => $errorRows,
                    'rows' => $previewData,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function processImport($platform, Request $request)
    {
        DB::beginTransaction();
        try {
            $rows = $request->input('rows', []);
            $imported = 0;

            foreach ($rows as $row) {
                if ($row['has_error'] ?? false) continue;

                // Check duplicate
                $exists = Order::where('order_number', $row['order_number'])
                    ->where('platform', $platform)
                    ->exists();

                if ($exists) continue;

                $order = Order::create([
                    'order_number' => $row['order_number'],
                    'platform' => $platform,
                    'order_date' => $row['order_date'],
                    'total_amount' => $row['total_amount'],
                    'status' => 'completed',
                    'payment_status' => 'paid',
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['imported' => $imported],
                'message' => "Berhasil import {$imported} order",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing orders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeOnlineManual($platform, Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'order_date' => 'required|date',
            'customer_name' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($request->items)->sum(function ($item) {
                return $item['qty'] * $item['price'];
            });

            $order = Order::create([
                'order_number' => $request->order_number,
                'platform' => $platform,
                'order_date' => $request->order_date,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'platform_product_name' => $item['platform_product_name'] ?? '',
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order berhasil disimpan',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateSJNumber()
    {
        $sjNumber = 'SJ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return response()->json([
            'success' => true,
            'data' => ['sj_number' => $sjNumber],
        ]);
    }

    public function checkDuplicateOrder(Request $request)
    {
        $exists = Order::where('order_number', $request->order_number)
            ->where('platform', $request->platform)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => ['exists' => $exists],
        ]);
    }
}

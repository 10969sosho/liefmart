<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceApiController extends Controller
{
    public function getByPlatform($platform, Request $request)
    {
        $query = FinanceTransaction::with(['order'])
            ->where('platform', $platform);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($transactions);
    }

    public function previewImport($platform, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            // Mock preview data
            return response()->json([
                'success' => true,
                'data' => [
                    'total_rows' => 5,
                    'valid_rows' => 5,
                    'error_rows' => 0,
                    'rows' => [
                        [
                            'order_number' => 'ORD-001',
                            'payment_date' => '2026-08-01',
                            'amount' => 150000,
                            'has_error' => false,
                        ],
                        [
                            'order_number' => 'ORD-002',
                            'payment_date' => '2026-08-02',
                            'amount' => 250000,
                            'has_error' => false,
                        ],
                    ],
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
            // Mock import
            return response()->json([
                'success' => true,
                'data' => ['imported' => 5],
                'message' => 'Berhasil import 5 pembayaran',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function manualStore($platform, Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($request->order_id);

            $transaction = FinanceTransaction::create([
                'transaction_number' => 'FIN-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'platform' => $platform,
                'order_id' => $request->order_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'status' => 'paid',
                'is_locked' => false,
            ]);

            // Update order payment status
            $order->update(['payment_status' => 'paid']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Pembayaran berhasil disimpan',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing manual payment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function lock($platform, $id)
    {
        $transaction = FinanceTransaction::where('platform', $platform)->findOrFail($id);
        $transaction->update(['is_locked' => true, 'status' => 'locked']);

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Transaksi berhasil dikunci',
        ]);
    }

    public function unlock($platform, $id)
    {
        $transaction = FinanceTransaction::where('platform', $platform)->findOrFail($id);
        $transaction->update(['is_locked' => false, 'status' => 'paid']);

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Transaksi berhasil di-unlock',
        ]);
    }

    public function delete($platform, $id)
    {
        $transaction = FinanceTransaction::where('platform', $platform)->findOrFail($id);
        
        if ($transaction->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi terkunci tidak bisa dihapus',
            ], 400);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus',
        ]);
    }

    public function printInvoice($platform, $id)
    {
        // Mock invoice generation
        return response()->json([
            'success' => true,
            'data' => ['invoice_url' => '/invoices/sample.pdf'],
        ]);
    }

    public function getHistory($platform, $id)
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function getUnpaidOrders(Request $request)
    {
        $query = Order::with(['customer'])
            ->where('payment_status', 'unpaid');

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        $orders = $query->orderBy('order_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}

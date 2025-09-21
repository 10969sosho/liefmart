<?php

namespace App\Http\Controllers;

use App\Models\ReturPenjualan;
use App\Models\ReturOfflineSale;
use App\Services\ReturFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturFinanceController extends Controller
{
    protected $financeService;

    public function __construct(ReturFinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Show form for processing return with financial adjustments
     */
    public function showFinanceForm($type, $id)
    {
        if ($type === 'online') {
            $retur = ReturPenjualan::with(['order.platform', 'details.orderItem'])->findOrFail($id);
            $originalTotal = $retur->order->orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            $originalTotal += $retur->order->shipping_cost ?? 0;
            
            return view('retur.finance.online', compact('retur', 'originalTotal'));
        } else {
            $retur = ReturOfflineSale::with(['offlineSale', 'details.offlineSaleItem'])->findOrFail($id);
            $originalTotal = $retur->offlineSale->total_amount;
            
            return view('retur.finance.offline', compact('retur', 'originalTotal'));
        }
    }

    /**
     * Process return with financial adjustments
     */
    public function processFinance(Request $request, $type, $id)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'additional_deduction' => 'numeric|min:0',
            'notes' => 'string|nullable'
        ]);

        try {
            DB::beginTransaction();

            if ($type === 'online') {
                $retur = ReturPenjualan::findOrFail($id);
                
                // Only allow processing if status is 'selesai'
                if ($retur->status !== 'selesai') {
                    return back()->with('error', 'Hanya retur dengan status selesai yang dapat diproses finance-nya.');
                }

                $this->financeService->handleOnlineReturFinance(
                    $retur, 
                    $request->refund_amount, 
                    $request->additional_deduction ?? 0
                );

                Log::info("Processed online return finance for retur {$retur->kode_retur}", [
                    'refund_amount' => $request->refund_amount,
                    'additional_deduction' => $request->additional_deduction
                ]);

            } else {
                $retur = ReturOfflineSale::findOrFail($id);
                
                // Only allow processing if status is 'selesai'
                if ($retur->status !== 'selesai') {
                    return back()->with('error', 'Hanya retur dengan status selesai yang dapat diproses finance-nya.');
                }

                $this->financeService->handleOfflineReturFinance(
                    $retur, 
                    $request->refund_amount, 
                    $request->additional_deduction ?? 0
                );

                Log::info("Processed offline return finance for retur {$retur->kode_retur}", [
                    'refund_amount' => $request->refund_amount,
                    'additional_deduction' => $request->additional_deduction
                ]);
            }

            DB::commit();

            return redirect()->route('retur-' . ($type === 'online' ? 'penjualan' : 'offline') . '.show', $id)
                ->with('success', 'Finance retur berhasil diproses. ' . $this->getFinanceProcessMessage($request));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing return finance: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reprocess return finance (for corrections)
     */
    public function reprocessFinance($type, $id)
    {
        try {
            DB::beginTransaction();

            if ($type === 'online') {
                $retur = ReturPenjualan::findOrFail($id);
                $this->financeService->handleOnlineReturFinance($retur);
            } else {
                $retur = ReturOfflineSale::findOrFail($id);
                $this->financeService->handleOfflineReturFinance($retur);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Finance retur berhasil diproses ulang dengan perhitungan otomatis.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reprocessing return finance: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Get finance process message based on amounts
     */
    private function getFinanceProcessMessage(Request $request): string
    {
        $refundAmount = $request->refund_amount;
        $additionalDeduction = $request->additional_deduction ?? 0;

        if ($additionalDeduction > 0) {
            return "Order tetap di finance dengan harga 0 dan outstanding adjustment Rp " . number_format($additionalDeduction, 0, ',', '.') . ".";
        } else {
            return "Pembayaran dihapus dan order dipindah ke unpaid dengan status RETUR.";
        }
    }
}
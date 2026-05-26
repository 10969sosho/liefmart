<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    public function index()
    {
        $orders = Order::whereNotIn('id', function($query) {
                $query->select('order_id')->from('financial_transactions');
            })
            ->orderBy('tanggal', 'desc')
            ->get();
            
        return view('financial.manual', compact('orders'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'tanggal_masuk_pembayaran' => 'required|date',
            'hari_masuk_pembayaran' => 'required|string',
            'admin_lain_1' => 'nullable|string',
            'admin_lain_2' => 'nullable|string',
            'admin_lain_3' => 'nullable|string',
            'admin_lain_4' => 'nullable|string',
            'admin_lain_5' => 'nullable|string',
            'adjustment' => 'nullable|numeric',
            'saldo_masuk' => 'required|numeric',
        ]);
        
        DB::beginTransaction();
        
        try {
            $order = Order::findOrFail($validated['order_id']);
            
            // Cek jika transaksi sudah ada
            $exists = FinancialTransaction::where('order_id', $order->id)->exists();
            
            if ($exists) {
                return redirect()->back()->with('error', 'Transaksi untuk order ini sudah ada');
            }
            
            // Buat instance FinancialTransaction baru
            $transaction = new FinancialTransaction();
            
            // Set data dari order
            $transaction->setDataFromOrder($order);
            
            // Set data dari form
            $transaction->tanggal_masuk_pembayaran = $validated['tanggal_masuk_pembayaran'];
            $transaction->hari_masuk_pembayaran = $validated['hari_masuk_pembayaran'];
            $transaction->admin_lain_1 = $validated['admin_lain_1'];
            $transaction->admin_lain_2 = $validated['admin_lain_2'];
            $transaction->admin_lain_3 = $validated['admin_lain_3'];
            $transaction->admin_lain_4 = $validated['admin_lain_4'];
            $transaction->admin_lain_5 = $validated['admin_lain_5'];
            $transaction->adjustment = $validated['adjustment'];
            $transaction->saldo_masuk = $validated['saldo_masuk'];
            
            // Hitung nominal fix dan outstanding
            $transaction->calculateNominalFix();
            $transaction->calculateOutstanding();
            
            // Simpan transaksi
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.manual')->with('success', 'Data berhasil disimpan');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
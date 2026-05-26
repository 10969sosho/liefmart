<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_order',
        'hari_order',
        'no_order',
        'no_invoice',
        'nominal_harga',
        'nominal_diskon1',
        'nominal_diskon2',
        'nominal_diskon3',
        'nominal_diskon4',
        'nominal_diskon5',
        'nominal_diskon6',
        'adjustment',
        'nominal_fix',
        'saldo_masuk',
        'tanggal_masuk_pembayaran',
        'hari_masuk_pembayaran',
        'outstanding',
        'persentase_diskon1',
        'persentase_diskon2',
        'persentase_diskon3',
        'persentase_diskon4',
        'persentase_diskon5',
        'persentase_diskon6',
        'total_persentase',
        'order_id'
    ];

    protected $casts = [
        'tanggal_order' => 'date',
        'tanggal_masuk_pembayaran' => 'date',
        'nominal_harga' => 'decimal:2',
        'nominal_diskon1' => 'decimal:2',
        'nominal_diskon2' => 'decimal:2',
        'nominal_diskon3' => 'decimal:2',
        'nominal_diskon4' => 'decimal:2',
        'nominal_diskon5' => 'decimal:2',
        'nominal_diskon6' => 'decimal:2',
        'adjustment' => 'decimal:2',
        'nominal_fix' => 'decimal:2',
        'saldo_masuk' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'persentase_diskon1' => 'decimal:2',
        'persentase_diskon2' => 'decimal:2',
        'persentase_diskon3' => 'decimal:2',
        'persentase_diskon4' => 'decimal:2',
        'persentase_diskon5' => 'decimal:2',
        'persentase_diskon6' => 'decimal:2',
        'total_persentase' => 'decimal:2',
    ];

    /**
     * Relasi ke model Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Menghasilkan nomor invoice sederhana
     */
    public static function generateSimpleInvoiceNumber($order)
    {
        // Format sederhana: INV-ORDER_NUMBER
        $prefix = 'INV';
        return "{$prefix}-{$order->order_number}";
    }

    /**
     * Mendapatkan data dari order terkait
     */
    public function setDataFromOrder($order)
    {
        $this->tanggal_order = $order->tanggal;
        $this->hari_order = $order->hari;
        $this->no_order = $order->order_number;
        $this->order_id = $order->id;
        
        // Hitung total harga setelah diskon dari semua item
        $totalAfterDiscount = $order->orderItems->sum('price_after_discount');
        $this->nominal_harga = $totalAfterDiscount;
        
        // Set invoice number dengan format sederhana
        $this->no_invoice = self::generateSimpleInvoiceNumber($order);
        
        return $this;
    }

    /**
     * Menghitung outstanding
     */
    public function calculateOutstanding()
    {
        $this->outstanding = $this->nominal_fix - $this->saldo_masuk;
        return $this;
    }
    
    /**
     * Menghitung nominal fix
     */
    public function calculateNominalFix()
    {
        // Nominal fix = nominal harga - (semua diskon) + adjustment
        $totalDiskon = 
            ($this->nominal_diskon1 ?? 0) + 
            ($this->nominal_diskon2 ?? 0) + 
            ($this->nominal_diskon3 ?? 0) + 
            ($this->nominal_diskon4 ?? 0) + 
            ($this->nominal_diskon5 ?? 0) + 
            ($this->nominal_diskon6 ?? 0);
            
        $this->nominal_fix = $this->nominal_harga - $totalDiskon + ($this->adjustment ?? 0);
        return $this;
    }
    
    /**
     * Menghitung persentase diskon
     */
    public function calculatePercentages()
    {
        if ($this->nominal_harga > 0) {
            $this->persentase_diskon1 = ($this->nominal_diskon1 / $this->nominal_harga) * 100;
            $this->persentase_diskon2 = ($this->nominal_diskon2 / $this->nominal_harga) * 100;
            $this->persentase_diskon3 = ($this->nominal_diskon3 / $this->nominal_harga) * 100;
            $this->persentase_diskon4 = ($this->nominal_diskon4 / $this->nominal_harga) * 100;
            $this->persentase_diskon5 = ($this->nominal_diskon5 / $this->nominal_harga) * 100;
            $this->persentase_diskon6 = ($this->nominal_diskon6 / $this->nominal_harga) * 100;
            
            $this->total_persentase = 
                $this->persentase_diskon1 + 
                $this->persentase_diskon2 + 
                $this->persentase_diskon3 + 
                $this->persentase_diskon4 + 
                $this->persentase_diskon5 + 
                $this->persentase_diskon6;
        }
        
        return $this;
    }
}
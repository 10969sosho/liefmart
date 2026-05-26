<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LazadaFinancialTransaction extends Model
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
        'adjustment_description',
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
        'percentage_paid',
        'percentage_outstanding',
        'order_id',
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
        'percentage_paid' => 'decimal:2',
        'percentage_outstanding' => 'decimal:2',
    ];

    /**
     * Format display tanggal
     */
    public function getFormattedDateAttribute()
    {
        return $this->tanggal_order ? Carbon::parse($this->tanggal_order)->format('d-m-Y') : '';
    }
    
    /**
     * Format display tanggal pembayaran
     */
    public function getFormattedPaymentDateAttribute()
    {
        return $this->tanggal_masuk_pembayaran ? Carbon::parse($this->tanggal_masuk_pembayaran)->format('d-m-Y') : '';
    }

    /**
     * Relasi ke model Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Accessor untuk persentase diskon1
     */
    public function getPersentaseDiskon1Attribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->nominal_diskon1 / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Accessor untuk persentase diskon2
     */
    public function getPersentaseDiskon2Attribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->nominal_diskon2 / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Accessor untuk persentase diskon3
     */
    public function getPersentaseDiskon3Attribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->nominal_diskon3 / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Accessor untuk persentase diskon4
     */
    public function getPersentaseDiskon4Attribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->nominal_diskon4 / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Accessor untuk total persentase
     */
    public function getTotalPersentaseAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        return $this->persentase_diskon1 + $this->persentase_diskon2 + 
               $this->persentase_diskon3 + $this->persentase_diskon4 +
               ($this->nominal_diskon5 ? abs(($this->nominal_diskon5 / $this->nominal_harga) * 100) : 0) +
               ($this->nominal_diskon6 ? abs(($this->nominal_diskon6 / $this->nominal_harga) * 100) : 0);
    }
    
    /**
     * Accessor untuk percentage paid
     */
    public function getPercentagePaidAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->saldo_masuk / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Accessor untuk percentage outstanding
     */
    public function getPercentageOutstandingAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->nominal_harga > 0) {
            return abs(($this->outstanding / $this->nominal_harga) * 100);
        }
        
        return 0;
    }
    
    /**
     * Menghasilkan nomor invoice berdasarkan tax_id dari order
     * 
     * @param Order $order
     * @param int $taxId
     * @return string
     */
    public static function generateInvoiceNumber($order, $taxId)
    {
        // Mendapatkan kategori berdasarkan tax_id
        $category = ($taxId == 1 || $taxId == 2 || $taxId == 5 || $taxId == 6) 
            ? \App\Models\InvoiceSequence::CATEGORY_KOPI 
            : \App\Models\InvoiceSequence::CATEGORY_SKINCARE;
            
        // Mendapatkan jenis penjualan (untuk Lazada selalu ONLINE)
        $salesType = \App\Models\InvoiceSequence::SALES_ONLINE;
        
        // Mendapatkan status pajak
        $taxStatus = in_array($taxId, [1, 3, 5, 7]) 
            ? \App\Models\InvoiceSequence::TAX_PKP 
            : \App\Models\InvoiceSequence::TAX_NON_PKP;
        
        // Ambil tanggal order PASTI dari tabel orders - TIDAK BOLEH NULL
        $orderDate = $order->tanggal 
            ?? Order::where('order_number', $order->order_number)->value('tanggal')
            ?? throw new \Exception("Tanggal order tidak ditemukan untuk Order {$order->order_number}");
        
        // Mendapatkan nomor invoice dari InvoiceSequence dengan tanggal ORDER
        $invoiceData = \App\Models\InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus, $orderDate);
        
        return $invoiceData['invoice_number'];
    }
    
    /**
     * Menghasilkan nomor invoice sederhana
     * @deprecated Use generateInvoiceNumber instead
     */
    public static function generateSimpleInvoiceNumber($order)
    {
        // Format sederhana: INV-ORDER_NUMBER
        $prefix = 'INV';
        return "{$prefix}-{$order->order_number}";
    }
}

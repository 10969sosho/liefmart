<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\BankAccount;
use App\Models\InvoiceSequence;

class TokopediaFinancialTransaction extends Model
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
        'nominal_diskon7',
        'nominal_diskon8',
        'nominal_diskon9',
        'nominal_diskon10',
        'nominal_diskon11',
        'nominal_diskon12',
        'adjustment',
        'adjustment_description',
        'nominal_fix',
        'qty',
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
        'persentase_diskon7',
        'persentase_diskon8',
        'persentase_diskon9',
        'persentase_diskon10',
        'persentase_diskon11',
        'persentase_diskon12',
        'total_persentase',
        'percentage_paid',
        'percentage_outstanding',
        'order_id',
        'is_locked',
        'locked_by',
        'locked_at'
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
        'nominal_diskon7' => 'decimal:2',
        'nominal_diskon8' => 'decimal:2',
        'nominal_diskon9' => 'decimal:2',
        'nominal_diskon10' => 'decimal:2',
        'nominal_diskon11' => 'decimal:2',
        'nominal_diskon12' => 'decimal:2',
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
        'persentase_diskon7' => 'decimal:2',
        'persentase_diskon8' => 'decimal:2',
        'persentase_diskon9' => 'decimal:2',
        'persentase_diskon10' => 'decimal:2',
        'persentase_diskon11' => 'decimal:2',
        'persentase_diskon12' => 'decimal:2',
        'total_persentase' => 'decimal:2',
        'percentage_paid' => 'decimal:2',
        'percentage_outstanding' => 'decimal:2',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    /**
     * Relasi ke model Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Menghasilkan nomor invoice berdasarkan tax_id dari warehouse_stock
     * 
     * @param Order $order
     * @param int $taxId
     * @return string
     */
    public static function generateInvoiceNumber($order, $taxId)
    {
        // Mendapatkan kategori berdasarkan tax_id
        $category = ($taxId == 1 || $taxId == 2 || $taxId == 5 || $taxId == 6) 
            ? InvoiceSequence::CATEGORY_KOPI 
            : InvoiceSequence::CATEGORY_SKINCARE;
            
        // Mendapatkan jenis penjualan (untuk Tokopedia selalu ONLINE)
        $salesType = InvoiceSequence::SALES_ONLINE;
        
        // Mendapatkan status pajak
        $taxStatus = in_array($taxId, [1, 3, 5, 7]) 
            ? InvoiceSequence::TAX_PKP 
            : InvoiceSequence::TAX_NON_PKP;
        
        // Mendapatkan tanggal ORDER dari order object
        $orderDate = $order->tanggal ?? null;
        
        // Mendapatkan nomor invoice dari InvoiceSequence dengan tanggal ORDER
        $invoiceData = InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus, $orderDate);
        
        return $invoiceData['invoice_number'];
    }
    
    /**
     * Get the suffix string for a given tax_id
     * Used for checking if an invoice already exists with this tax_id format
     * 
     * @param int $taxId
     * @return string
     */
    public static function getSuffixForTaxId($taxId)
    {
        switch($taxId) {
            // KOPI category
            case 1: return "HPNSDA-OLK/01";
            case 2: return "HPNSDA-OLK/02";
            case 5: return "HPNSDA-KOP/01";
            case 6: return "HPNSDA-KOP/02";
            
            // SKINCARE category
            case 3: return "HGNSDA-OL/01";
            case 4: return "HGNSDA-OL/02";
            case 7: return "HGNSDA-KOS/01";
            case 8: return "HGNSDA-KOS/02";
            
            default: return "HGNSDA-OL/01"; // Default to SKINCARE PKP ONLINE
        }
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

    /**
     * Mendapatkan data dari order terkait
     */
    public function setDataFromOrder($order)
    {
        $this->tanggal_order = $order->tanggal;
        $this->hari_order = $order->hari;
        $this->no_order = $order->order_number;
        $this->order_id = $order->id;
        
        // Hitung total harga setelah diskon dari semua item dengan quantity
        $totalAfterDiscount = 0;
        foreach ($order->orderItems as $item) {
            $totalAfterDiscount += $item->price_after_discount * $item->quantity;
        }
        $this->nominal_harga = $totalAfterDiscount;
        
        return $this;
    }

    /**
     * Calculate the nominal_fix
     * 
     * @return $this
     */
    public function calculateNominalFix()
    {
        // Calculate total discount from all discount fields
        $totalDiskon = 
            ($this->nominal_diskon1 ?? 0) + 
            ($this->nominal_diskon2 ?? 0) + 
            ($this->nominal_diskon3 ?? 0) + 
            ($this->nominal_diskon4 ?? 0) + 
            ($this->nominal_diskon5 ?? 0) + 
            ($this->nominal_diskon6 ?? 0) + 
            ($this->nominal_diskon7 ?? 0) + 
            ($this->nominal_diskon8 ?? 0) + 
            ($this->nominal_diskon9 ?? 0) + 
            ($this->nominal_diskon10 ?? 0) + 
            ($this->nominal_diskon11 ?? 0) + 
            ($this->nominal_diskon12 ?? 0);
            
        // Nominal fix = nominal harga - total diskon + adjustment
        $this->nominal_fix = $this->nominal_harga - $totalDiskon + ($this->adjustment ?? 0);
        
        return $this;
    }

    /**
     * Calculate the outstanding value
     * 
     * @return $this
     */
    public function calculateOutstanding()
    {
        $this->outstanding = $this->nominal_fix - ($this->saldo_masuk ?? 0);
        return $this;
    }

    /**
     * Calculate percentages for the transaction
     * 
     * @return $this
     */
    public function calculatePercentages()
    {
        // Calculate discount percentages
        if ($this->nominal_harga > 0) {
            $this->persentase_diskon1 = ($this->nominal_diskon1 / $this->nominal_harga) * 100;
            $this->persentase_diskon2 = ($this->nominal_diskon2 / $this->nominal_harga) * 100;
            $this->persentase_diskon3 = ($this->nominal_diskon3 / $this->nominal_harga) * 100;
            $this->persentase_diskon4 = ($this->nominal_diskon4 / $this->nominal_harga) * 100;
            $this->persentase_diskon5 = ($this->nominal_diskon5 / $this->nominal_harga) * 100;
            $this->persentase_diskon6 = ($this->nominal_diskon6 / $this->nominal_harga) * 100;
            $this->persentase_diskon7 = ($this->nominal_diskon7 / $this->nominal_harga) * 100;
            $this->persentase_diskon8 = ($this->nominal_diskon8 / $this->nominal_harga) * 100;
            $this->persentase_diskon9 = ($this->nominal_diskon9 / $this->nominal_harga) * 100;
            $this->persentase_diskon10 = ($this->nominal_diskon10 / $this->nominal_harga) * 100;
            $this->persentase_diskon11 = ($this->nominal_diskon11 / $this->nominal_harga) * 100;
            $this->persentase_diskon12 = ($this->nominal_diskon12 / $this->nominal_harga) * 100;
            
            $this->total_persentase = $this->persentase_diskon1 + $this->persentase_diskon2 + 
                                     $this->persentase_diskon3 + $this->persentase_diskon4 +
                                     $this->persentase_diskon5 + $this->persentase_diskon6 +
                                     $this->persentase_diskon7 + $this->persentase_diskon8 +
                                     $this->persentase_diskon9 + $this->persentase_diskon10 +
                                     $this->persentase_diskon11 + $this->persentase_diskon12;
        } else {
            $this->persentase_diskon1 = 0;
            $this->persentase_diskon2 = 0;
            $this->persentase_diskon3 = 0;
            $this->persentase_diskon4 = 0;
            $this->persentase_diskon5 = 0;
            $this->persentase_diskon6 = 0;
            $this->persentase_diskon7 = 0;
            $this->persentase_diskon8 = 0;
            $this->persentase_diskon9 = 0;
            $this->persentase_diskon10 = 0;
            $this->persentase_diskon11 = 0;
            $this->persentase_diskon12 = 0;
            $this->total_persentase = 0;
        }
        
        // Calculate payment percentages
        if ($this->nominal_fix > 0) {
            $this->percentage_paid = ($this->saldo_masuk ?? 0) / $this->nominal_fix * 100;
            $this->percentage_outstanding = ($this->outstanding ?? 0) / $this->nominal_fix * 100;
        } else {
            $this->percentage_paid = 0;
            $this->percentage_outstanding = 0;
        }
        
        return $this;
    }
    
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
     * Get active bank account for this transaction
     * 
     * @return \App\Models\BankAccount|null
     */
    public static function getActive()
    {
        return BankAccount::where('is_active', true)->first();
    }
    
    /**
     * Get bank account information for display on invoice
     * 
     * @return array
     */
    public static function getBankAccountInfo()
    {
        $activeAccount = self::getActive();
        
        if ($activeAccount) {
            return [
                'bank_name' => $activeAccount->bank_name,
                'account_number' => $activeAccount->account_number,
                'account_name' => $activeAccount->account_name,
                'has_active' => true
            ];
        }
        
        // Default fallback information if no active account is set
        return [
            'bank_name' => 'DANAMON',
            'account_number' => '********** (Hubungi Admin)',
            'account_name' => 'PT. HARVEST GLOBAL NIAGA',
            'has_active' => false
        ];
    }

    /**
     * Relation to the user who locked the transaction
     */
    public function lockedByUser()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Check if the transaction is locked
     * 
     * @return bool
     */
    public function isLocked()
    {
        return $this->is_locked;
    }

    /**
     * Lock the transaction
     * 
     * @param int $userId
     * @return $this
     */
    public function lock($userId)
    {
        if (!$this->is_locked) {
            $this->is_locked = true;
            $this->locked_by = $userId;
            $this->locked_at = now();
            $this->save();
        }
        
        return $this;
    }

    /**
     * Unlock the transaction
     * 
     * @return $this
     */
    public function unlock()
    {
        if ($this->is_locked) {
            $this->is_locked = false;
            $this->locked_by = null;
            $this->locked_at = null;
            $this->save();
        }
        
        return $this;
    }


} 
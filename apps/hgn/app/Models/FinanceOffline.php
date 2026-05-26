<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Shared\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Builder;
use App\Models\InvoiceSequence;

class FinanceOffline extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'nominal',
        'tanggal_invoice',
        'tanggal_bayar',
        'status',
        'print_count',
        'reprint_requested',
        'reprint_approved',
        'reprint_approved_by',
        'last_printed_at',
        'main_category_id',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'tanggal_bayar' => 'date',
        'last_printed_at' => 'datetime',
        'reprint_requested' => 'boolean',
        'reprint_approved' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('mainCategory', function (Builder $builder) {
            $mainCategoryId = MainCategoryHelper::getSelectedMainCategoryId();
            if ($mainCategoryId) {
                $builder->where(function($query) use ($mainCategoryId) {
                    $query->where('main_category_id', $mainCategoryId)
                          ->orWhereNull('main_category_id');
                });
            }
        });
    }

    /**
     * Get the barang keluar items associated with this invoice
     */
    public function barangKeluarItems()
    {
        return $this->hasMany(BarangKeluar::class, 'finance_offline_id');
    }

    /**
     * Get the formatted nominal
     */
    public function getFormattedNominalAttribute()
    {
        return number_format($this->nominal, 0, ',', '.');
    }

    /**
     * Generate a new invoice number based on tax ID
     *
     * @param int $taxId
     * @param string $orderDate Tanggal ORDER (format: Y-m-d)
     * @return string
     */
    public static function generateInvoiceNumber($taxId, $orderDate = null)
    {
        // Jika tidak ada tanggal ORDER, gunakan tanggal saat ini
        if (!$orderDate) {
            $orderDate = Carbon::now()->format('Y-m-d');
        }
        
        // Tentukan kategori, jenis penjualan, dan status pajak berdasarkan tax_id
        $category = ($taxId == 5 || $taxId == 6) 
            ? InvoiceSequence::CATEGORY_KOPI 
            : InvoiceSequence::CATEGORY_SKINCARE;
            
        $salesType = InvoiceSequence::SALES_OFFLINE;
        
        $taxStatus = in_array($taxId, [3, 5]) 
            ? InvoiceSequence::TAX_PKP 
            : InvoiceSequence::TAX_NON_PKP;
        
        // Mendapatkan nomor invoice dari InvoiceSequence dengan tanggal ORDER
        $invoiceData = InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus, $orderDate);
        
        return $invoiceData['invoice_number'];
    }

    /**
     * Generate invoice number from Surat Jalan number
     * Menggunakan nomor yang sama dengan Surat Jalan
     *
     * @param string $suratJalanNumber Nomor Surat Jalan (format: {counter}/{yearMonth}/{suffix}/{taxCode})
     * @param int $taxId Tax ID untuk menentukan taxCode
     * @return string Nomor Invoice (format: {counter}/{yearMonth}/{suffix}/{taxCode})
     */
    public static function generateInvoiceNumberFromSuratJalan($suratJalanNumber, $taxId)
    {
        // Parse surat jalan number
        // Format SJ: {counter}/{yearMonth}/{suffix}/{taxCode}
        // Format INV: {counter}/{yearMonth}/{suffix}/{taxCode}
        // Contoh: 0003/2508/HGNSDA-KOS/01
        
        // Split by '/'
        $parts = explode('/', $suratJalanNumber);
        
        if (count($parts) < 3) {
            // Jika format tidak sesuai, fallback ke generateInvoiceNumber biasa
            \Log::warning("Invalid surat jalan format: {$suratJalanNumber}, falling back to normal generation");
            return self::generateInvoiceNumber($taxId);
        }
        
        $counter = $parts[0]; // Contoh: 0003
        $yearMonth = $parts[1]; // Contoh: 2508
        $suffix = $parts[2]; // Contoh: HGNSDA-KOS
        
        // Tentukan taxCode berdasarkan tax_id
        $taxCode = in_array($taxId, [3, 5]) ? '01' : '02';
        
        // Jika ada part ke-4 (taxCode dari SJ), kita tetap gunakan taxCode berdasarkan tax_id
        // karena tax_id dari barang keluar mungkin berbeda dengan tax_id dari SJ
        
        // Format invoice number: {counter}/{yearMonth}/{suffix}/{taxCode}
        return "{$counter}/{$yearMonth}/{$suffix}/{$taxCode}";
    }

    /**
     * Scope a query to only include unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope a query to only include paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Get all payments for this invoice
     */
    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'finance_offline_id');
    }

    /**
     * Get the user who approved the reprint
     */
    public function reprintApprovedBy()
    {
        return $this->belongsTo(User::class, 'reprint_approved_by');
    }

    /**
     * Check if the invoice can be printed
     * 
     * @param \App\Models\User $user The user trying to print
     * @return bool
     */
    public function canBePrinted(User $user)
    {
        // Super admins can always print
        if ($user->isSuperAdmin()) {
            return true;
        }

        // If never printed, anyone can print the first time
        if ($this->print_count === 0) {
            return true;
        }

        // For reprints, admin needs approval or be a superadmin
        return $this->reprint_approved === true;
    }

    /**
     * Request a reprint for this invoice
     * 
     * @return bool
     */
    public function requestReprint()
    {
        if ($this->print_count > 0 && !$this->reprint_requested && !$this->reprint_approved) {
            $this->reprint_requested = true;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Approve a reprint request
     * 
     * @param \App\Models\User $approver
     * @return bool
     */
    public function approveReprint(User $approver)
    {
        if ($this->reprint_requested && $approver->isSuperAdmin()) {
            $this->reprint_approved = true;
            $this->reprint_approved_by = $approver->id;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Track a successful print
     * 
     * @return bool
     */
    public function trackPrint()
    {
        $this->print_count += 1;
        $this->last_printed_at = now();
        
        // If this was an approved reprint, reset the approval flags
        if ($this->reprint_approved) {
            $this->reprint_requested = false;
            $this->reprint_approved = false;
            // Keep reprint_approved_by for audit trail
        }
        
        return $this->save();
    }

    /**
     * Get the main category for this invoice
     */
    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }

    /**
     * Recalculate nominal from offline_sales total_amount
     * This ensures nominal matches the offline_sales total_amount
     * Uses original quantity (before return) for accurate calculation
     * 
     * @return float The recalculated nominal
     */
    public function recalculateNominal()
    {
        $barangKeluarItems = $this->barangKeluarItems;
        
        if ($barangKeluarItems->isEmpty()) {
            return 0;
        }
        
        // Calculate nominal from unique offline_sale items (not from barang_keluar items)
        // This ensures each sale item is only counted once, even if split into multiple barang_keluar
        $nominal = 0;
        $processedSaleItemIds = [];
        
        foreach ($barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem) {
                $saleItemId = $bk->offlineSaleItem->id;
                
                // Only count each sale item once
                if (!in_array($saleItemId, $processedSaleItemIds)) {
                    $subtotal = $bk->offlineSaleItem->subtotal ?? 0;
                    $nominal += $subtotal;
                    $processedSaleItemIds[] = $saleItemId;
                }
            }
        }
        
        // Format nominal (this is DPP, not grand total)
        return \App\Helpers\NumberFormatter::formatForDatabase($nominal);
    }
    
    /**
     * Calculate item value with all discounts using specified quantity
     */
    private function calculateItemValueWithQuantity($item, $qty)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)$qty;
        
        // Start with base total (price × quantity)
        $currentTotal = $basePrice * $qty;
        
        // Apply percentage discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }
        
        // Apply nominal discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }
        
        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }
    
    /**
     * Calculate item value with all discounts (uses current quantity)
     */
    private function calculateItemValue($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        
        return $this->calculateItemValueWithQuantity($item, $qty);
    }

    /**
     * Update nominal using recalculateNominal and save
     * 
     * @return bool
     */
    public function updateNominal()
    {
        $this->nominal = $this->recalculateNominal();
        return $this->save();
    }
}

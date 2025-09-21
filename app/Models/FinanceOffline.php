<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Helpers\MainCategoryHelper;
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
     * @return string
     */
    public static function generateInvoiceNumber($taxId)
    {
        // Mendapatkan kategori berdasarkan tax_id
        $category = ($taxId == 5 || $taxId == 6) 
            ? InvoiceSequence::CATEGORY_KOPI 
            : InvoiceSequence::CATEGORY_SKINCARE;
            
        // Mendapatkan jenis penjualan (untuk Finance Offline selalu OFFLINE)
        $salesType = InvoiceSequence::SALES_OFFLINE;
        
        // Mendapatkan status pajak - fix untuk offline cosmetics
        // Tax ID 3 = HGN (PKP), Tax ID 4 = LM (Non-PKP) untuk kosmetik offline
        // Tax ID 5 = PKP, Tax ID 6 = Non-PKP untuk kopi offline
        $taxStatus = in_array($taxId, [3, 5, 7]) 
            ? InvoiceSequence::TAX_PKP 
            : InvoiceSequence::TAX_NON_PKP;
        
        // Mendapatkan nomor invoice dari InvoiceSequence
        $invoiceData = InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus);
        
        return $invoiceData['invoice_number'];
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
}

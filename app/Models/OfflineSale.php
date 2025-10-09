<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Builder;

class OfflineSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_jalan_number',
        'No_PO',
        'sale_date',
        'customer_name',
        'customer_id',
        'status',
        'payment_date',
        'payment_method',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
        'main_category_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'payment_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
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
     * Get all items for this offline sale
     */
    public function items()
    {
        return $this->hasMany(OfflineSaleItem::class);
    }

    /**
     * Get the user who created this sale
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the customer for this sale
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'name');
    }

    // Add a direct relationship using customer_id
    public function customerInfo()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the main category for this sale
     */
    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }

    /**
     * Get all finance offline records related to this sale
     */
    public function getFinanceOfflineAttribute()
    {
        return $this->getInvoices();
    }

    /**
     * Check if this offline sale has any invoices
     */
    public function hasInvoices()
    {
        return $this->items()
            ->whereHas('barangKeluar', function($query) {
                $query->whereNotNull('finance_offline_id');
            })
            ->exists();
    }

    /**
     * Get all invoices related to this offline sale
     */
    public function getInvoices()
    {
        return FinanceOffline::whereHas('barangKeluarItems', function($query) {
            $query->whereHas('offlineSaleItem', function($subQuery) {
                $subQuery->where('offline_sale_id', $this->id);
            });
        })->get();
    }

    /**
     * Check if all invoices related to this offline sale are paid
     */
    public function areAllInvoicesPaid()
    {
        $invoices = $this->getInvoices();
        
        if ($invoices->isEmpty()) {
            return false; // No invoices means not paid
        }
        
        return $invoices->every(function($invoice) {
            return $invoice->status === 'paid';
        });
    }

    /**
     * Check if any invoice related to this offline sale is paid
     */
    public function hasAnyPaidInvoice()
    {
        $invoices = $this->getInvoices();
        
        return $invoices->contains(function($invoice) {
            return $invoice->status === 'paid';
        });
    }

    /**
     * Get the payment status of this offline sale
     * Returns: 'pending', 'partial', 'paid'
     */
    public function getPaymentStatus()
    {
        $invoices = $this->getInvoices();
        
        if ($invoices->isEmpty()) {
            return 'pending'; // No invoices created yet
        }
        
        $allPaid = $invoices->every(function($invoice) {
            return $invoice->status === 'paid';
        });
        
        $anyPaid = $invoices->contains(function($invoice) {
            return $invoice->status === 'paid';
        });
        
        if ($allPaid) {
            return 'paid';
        } elseif ($anyPaid) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Update the status based on payment status
     */
    public function updateStatusBasedOnPayment()
    {
        $paymentStatus = $this->getPaymentStatus();
        
        $newStatus = match($paymentStatus) {
            'paid' => 'paid',
            'partial' => 'pending', // Keep as pending if partially paid
            default => 'pending'
        };
        
        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Generate a unique surat jalan number based on tax ID and main category
     *
     * @param int $taxId
     * @param int $mainCategoryId
     * @return string
     */
    public static function generateSuratJalanNumber($taxId = null, $mainCategoryId = null)
    {
        $yearMonth = Carbon::now()->format('ym'); // Format: 2504 for April 2025
        
        // Determine the suffix based on tax id and main category
        $suffix = "";
        
        if ($mainCategoryId == 1) { // HPNSDA
            if ($taxId == 1) { // PKP
                $suffix = "HPNSDA-KOP/01";
            } elseif ($taxId == 2) { // Non PKP
                $suffix = "HPNSDA-KOP/02";
            } else {
                // Default if tax ID not specified
                $suffix = "HPNSDA-KOP/01";
            }
        } elseif ($mainCategoryId == 2) { // HGNSDA
            if ($taxId == 3) { // HGN
                $suffix = "HGNSDA-KOS/01";
            } elseif ($taxId == 4) { // LM
                $suffix = "HGNSDA-KOS/02";
            } else {
                // Default if tax ID not specified
                $suffix = "HGNSDA-KOS/01";
            }
        } else {
            // If main category not specified, use the old format
            $today = Carbon::now()->format('Ymd');
            $latestSJ = self::where('surat_jalan_number', 'like', "SJ-OFF-{$today}%")
                ->where(function($query) use ($mainCategoryId) {
                    if ($mainCategoryId) {
                        $query->where('main_category_id', $mainCategoryId)
                              ->orWhereNull('main_category_id');
                    }
                })
                ->orderBy('surat_jalan_number', 'desc')
                ->value('surat_jalan_number');

            if ($latestSJ) {
                $lastNumber = (int) substr($latestSJ, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return "SJ-OFF-{$today}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        }
        
        // Find latest invoice number for this format and year-month
        $latestSJ = self::where('surat_jalan_number', 'like', "%/$yearMonth/$suffix")
                       ->where(function($query) use ($mainCategoryId) {
                           if ($mainCategoryId) {
                               $query->where('main_category_id', $mainCategoryId)
                                     ->orWhereNull('main_category_id');
                           }
                       })
                       ->orderBy('surat_jalan_number', 'desc')
                       ->first();
        
        $counter = 1;
        
        if ($latestSJ) {
            // Extract the counter from the latest SJ
            $parts = explode('/', $latestSJ->surat_jalan_number);
            if (count($parts) > 0) {
                $counter = (int) $parts[0];
                $counter++;
            }
        }
        
        // Format: 0001/2504/HGNSDA-KOS/01
        return sprintf('%04d/%s/%s', $counter, $yearMonth, $suffix);
    }

    /**
     * Check if this offline sale has any completed returns
     */
    public function hasReturns()
    {
        return \App\Models\ReturOfflineSale::where('offline_sale_id', $this->id)
            ->where('status', 'selesai')
            ->exists();
    }

    /**
     * Get all completed returns for this offline sale
     */
    public function getReturns()
    {
        return \App\Models\ReturOfflineSale::where('offline_sale_id', $this->id)
            ->where('status', 'selesai')
            ->with('details')
            ->get();
    }
} 
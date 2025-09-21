<?php

namespace App\Models;

use App\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'platform_id',
        'order_number',
        'order_date',
        'customer_name',
        'platform',
        'total_amount',
        'status',
        'tanggal',
        'hari',
        'status_hari',
        'main_category_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'date',
        'order_date' => 'date',
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
                $builder->whereHas('orderItems.warehouseStock.product', function($query) use ($mainCategoryId) {
                    $query->where('main_category_id', $mainCategoryId);
                });
            }
        });
    }

    /**
     * Relasi ke platform
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Relasi ke order items
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the main category for this order
     */
    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }

    /**
     * Relation to Shopee financial transactions
     */
    public function shopeeFinancialTransactions()
    {
        return $this->hasMany(ShopeeFinancialTransaction::class);
    }
    
    /**
     * Relation to TikTok financial transactions
     */
    public function tiktokFinancialTransactions()
    {
        return $this->hasMany('App\Models\TiktokFinancialTransaction');
    }
    
    /**
     * Relation to Tokopedia financial transactions
     */
    public function tokopediaFinancialTransactions()
    {
        return $this->hasMany('App\Models\TokopediaFinancialTransaction');
    }
    
    /**
     * Relation to Blibli financial transactions
     */
    public function blibliFinancialTransactions()
    {
        return $this->hasMany('App\Models\BlibliFinancialTransaction');
    }

    /**
     * Relation to retur penjualan (sales returns)
     */
    public function returPenjualan()
    {
        return $this->hasMany(ReturPenjualan::class);
    }

    /**
     * Check if this order has any returns
     */
    public function hasReturns()
    {
        return $this->returPenjualan()->exists();
    }

    /**
     * Check if this order is fully returned (all items returned)
     */
    public function isFullyReturned()
    {
        if (!$this->relationLoaded('orderItems')) {
            $this->load('orderItems.platformProduct.mappingBarang');
        }
        
        $totalOriginalQuantity = 0;
        $totalReturnedQuantity = 0;
        
        foreach ($this->orderItems as $item) {
            // Calculate original quantity for this item
            $originalQuantity = $item->quantity;
            $totalOriginalQuantity += $originalQuantity;
            
            // Calculate returned quantity for this item
            $returnedQuantityIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                ->whereHas('returPenjualan', function($q) { 
                    $q->whereIn('status', ['draft', 'selesai']); 
                })
                ->sum('qty');
            
            // Convert individual retur quantity back to package quantity
            $packageQuantity = 1; // Default for non-package products
            if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
            }
            
            $returnedQuantity = $packageQuantity > 0 ? $returnedQuantityIndividual / $packageQuantity : $returnedQuantityIndividual;
            $totalReturnedQuantity += $returnedQuantity;
        }
        
        // If total returned quantity equals or exceeds original quantity, this is fully returned
        return $totalReturnedQuantity >= $totalOriginalQuantity && $totalReturnedQuantity > 0;
    }

    /**
     * Customize the array representation
     * 
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add both camelCase and snake_case forms of the relationships for JS compatibility
        if ($this->relationLoaded('orderItems')) {
            $array['orderItems'] = $this->orderItems->toArray();
            $array['order_items'] = $array['orderItems']; // For snake_case JS access
        }
        
        if ($this->relationLoaded('platform')) {
            $array['platform'] = $this->platform ? $this->platform->toArray() : null;
        }
        
        return $array;
    }
}

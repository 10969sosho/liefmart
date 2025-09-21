<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'platform_product_id',
        'quantity',
        'price_after_discount',
        'tracking_number',
        'warehouse_stock_id',
    ];

    /**
     * Relasi ke order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relasi ke platform product
     */
    public function platformProduct()
    {
        return $this->belongsTo(PlatformProduct::class);
    }

    /**
     * Relasi ke warehouse stock
     */
    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class);
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
        if ($this->relationLoaded('platformProduct')) {
            $array['platformProduct'] = $this->platformProduct ? $this->platformProduct->toArray() : null;
            $array['platform_product'] = $array['platformProduct']; // For snake_case JS access
        }
        
        if ($this->relationLoaded('warehouseStock')) {
            $array['warehouseStock'] = $this->warehouseStock ? $this->warehouseStock->toArray() : null;
            $array['warehouse_stock'] = $array['warehouseStock']; // For snake_case JS access
        }
        
        return $array;
    }
}

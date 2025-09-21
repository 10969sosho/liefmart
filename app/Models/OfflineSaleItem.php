<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'offline_sale_id',
        'product_id',
        'warehouse_stock_id',
        'quantity',
        'unit_price',
        'discount_amount_1',
        'discount_percent_1',
        'discount_amount_2',
        'discount_percent_2',
        'discount_amount_3',
        'discount_percent_3',
        'discount_amount_4',
        'discount_percent_4',
        'discount_amount_5',
        'discount_percent_5',
        'subtotal',
        'notes',
        'discount_mapping',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount_1' => 'decimal:2',
        'discount_percent_1' => 'decimal:2',
        'discount_amount_2' => 'decimal:2',
        'discount_percent_2' => 'decimal:2',
        'discount_amount_3' => 'decimal:2',
        'discount_percent_3' => 'decimal:2',
        'discount_amount_4' => 'decimal:2',
        'discount_percent_4' => 'decimal:2',
        'discount_amount_5' => 'decimal:2',
        'discount_percent_5' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_mapping' => 'array',
    ];

    /**
     * Get the offline sale that owns the item
     */
    public function offlineSale()
    {
        return $this->belongsTo(OfflineSale::class);
    }

    /**
     * Get the product for this item
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse stock for this item
     */
    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class);
    }

    /**
     * Get barang keluar records related to this sale item
     */
    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class, 'offline_sale_item_id');
    }
} 
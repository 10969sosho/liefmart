<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturPenjualanDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'retur_penjualan_id',
        'order_item_id',
        'product_id',
        'qty',
        'kondisi',
        'alasan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    /**
     * Get the retur penjualan that owns the detail
     */
    public function returPenjualan(): BelongsTo
    {
        return $this->belongsTo(ReturPenjualan::class);
    }

    /**
     * Get the order item associated with the retur
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product being returned
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


} 
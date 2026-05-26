<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_type_id',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the product type that owns this product size.
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get all products for this product size.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    /**
     * Get all product variants for this product size.
     */
    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
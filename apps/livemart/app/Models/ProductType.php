<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_category_id',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the product category that owns this product type.
     */
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get all product sizes for this product type.
     */
    public function productSizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * Get all product variants for this product type through product sizes.
     */
    public function productVariants()
    {
        return $this->hasManyThrough(ProductVariant::class, ProductSize::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sub_brand_id',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the sub brand that owns this product category.
     */
    public function subBrand()
    {
        return $this->belongsTo(SubBrand::class);
    }

    /**
     * Get all product types for this product category.
     */
    public function productTypes()
    {
        return $this->hasMany(ProductType::class);
    }
}
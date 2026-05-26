<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInitialPriceVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'version',
        'initial_price',
        'discount_percentage',
        'is_active',
        'valid_from',
        'valid_until',
        'parent_version_id',
        'change_reason',
    ];

    protected $casts = [
        'initial_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function parentVersion()
    {
        return $this->belongsTo(ProductInitialPriceVersion::class, 'parent_version_id');
    }

    public function childVersions()
    {
        return $this->hasMany(ProductInitialPriceVersion::class, 'parent_version_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query
            ->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>', $date);
            });
    }

    public static function getVersionForDate($productId, $date)
    {
        return static::where('product_id', $productId)
            ->forDate($date)
            ->orderBy('valid_from', 'desc')
            ->first();
    }

    public static function getInitialPriceForDate($productId, $date): string
    {
        $version = static::getVersionForDate($productId, $date);
        return (string) ($version?->initial_price ?? 0);
    }
}

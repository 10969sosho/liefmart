<?php

namespace App\Models;

use Shared\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'main_category_id',
        'tax_category_id',
        'brand_id',
        'sub_brand_id',
        'product_category_id',
        'product_type_id',
        'product_size_id',
        'product_variant_id',
        'description',
        'sku',
        'barcode',
        'is_active',
        'initial_price',
        'discount_percentage',
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
                $builder->where('main_category_id', $mainCategoryId);
            }
        });

        static::created(function (Product $product) {
            if ($product->initialPriceVersions()->exists()) {
                return;
            }

            $effectiveFrom = $product->created_at ?? now();

            $product->initialPriceVersions()->create([
                'version' => 1,
                'initial_price' => $product->initial_price ?? 0,
                'discount_percentage' => $product->discount_percentage ?? 0,
                'is_active' => true,
                'valid_from' => $effectiveFrom,
                'valid_until' => null,
                'parent_version_id' => null,
                'change_reason' => 'Product created',
            ]);
        });

        static::saved(function (Product $product) {
            if ($product->wasRecentlyCreated) {
                return;
            }

            if (!$product->wasChanged('initial_price') && !$product->wasChanged('discount_percentage')) {
                return;
            }

            DB::transaction(function () use ($product) {
                $effectiveFrom = $product->updated_at ?? now();

                $previousActive = $product->initialPriceVersions()
                    ->active()
                    ->orderByDesc('version')
                    ->first();

                $product->initialPriceVersions()
                    ->active()
                    ->update([
                        'is_active' => false,
                        'valid_until' => $effectiveFrom,
                    ]);

                $latestVersion = (int) $product->initialPriceVersions()
                    ->lockForUpdate()
                    ->max('version');

                $product->initialPriceVersions()->create([
                    'version' => $latestVersion + 1,
                    'initial_price' => $product->initial_price ?? 0,
                    'discount_percentage' => $product->discount_percentage ?? 0,
                    'is_active' => true,
                    'valid_from' => $effectiveFrom,
                    'valid_until' => null,
                    'parent_version_id' => $previousActive?->id,
                    'change_reason' => null,
                ]);
            });
        });
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subBrand(): BelongsTo
    {
        return $this->belongsTo(SubBrand::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
    
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function initialPriceVersions(): HasMany
    {
        return $this->hasMany(ProductInitialPriceVersion::class);
    }

    public function latestInitialPriceVersion(): HasOne
    {
        return $this->hasOne(ProductInitialPriceVersion::class)
            ->ofMany(['version' => 'max'], function ($query) {
                $query->where('is_active', true);
            });
    }

    public function getInitialPriceAt($date): string
    {
        $version = $this->initialPriceVersions()
            ->forDate($date)
            ->orderBy('valid_from', 'desc')
            ->first();

        return (string) ($version?->initial_price ?? $this->initial_price ?? 0);
    }

    /**
     * Mutator untuk initial_price - pastikan null menjadi 0
     */
    public function setInitialPriceAttribute($value)
    {
        $this->attributes['initial_price'] = $value ?? 0;
    }

    /**
     * Accessor untuk initial_price - pastikan null menjadi 0
     */
    public function getInitialPriceAttribute($value)
    {
        return $value ?? 0;
    }

    /**
     * Mutator untuk discount_percentage - pastikan null menjadi 0
     */
    public function setDiscountPercentageAttribute($value)
    {
        $this->attributes['discount_percentage'] = $value ?? 0;
    }

    /**
     * Accessor untuk discount_percentage - pastikan null menjadi 0
     */
    public function getDiscountPercentageAttribute($value)
    {
        return $value ?? 0;
    }
}

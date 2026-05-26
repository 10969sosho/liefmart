<?php

namespace App\Models;

use Shared\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TaxCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'main_category_id', 'tax_percentage', 'description', 'is_active'];

    protected $casts = [
        'tax_percentage' => 'decimal:2',
        'is_active' => 'boolean',
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
    }

    /**
     * Get the main category that owns this tax category.
     */
    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }

    /**
     * Get all products with this tax category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

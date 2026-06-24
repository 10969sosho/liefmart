<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\MainCategory;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $mainCategory = MainCategory::where('is_active', true)->first();

        // Create minimal hierarchy records if they don't exist
        if ($mainCategory) {
            $brand = Brand::firstOrCreate(
                ['name' => 'Test Brand', 'main_category_id' => $mainCategory->id],
                ['is_active' => true]
            );
            $subBrand = SubBrand::firstOrCreate(
                ['name' => 'Test SubBrand', 'brand_id' => $brand->id],
                ['is_active' => true]
            );
            $productCategory = ProductCategory::firstOrCreate(
                ['name' => 'Test Category', 'sub_brand_id' => $subBrand->id],
                ['is_active' => true]
            );
            $productType = ProductType::firstOrCreate(
                ['name' => 'Test Type', 'product_category_id' => $productCategory->id],
                ['is_active' => true]
            );
            $productSize = ProductSize::firstOrCreate(
                ['name' => 'Test Size', 'product_type_id' => $productType->id],
                ['is_active' => true]
            );
        }

        return [
            'name' => $this->faker->unique()->words(3, true),
            'main_category_id' => $mainCategory?->id ?? 1,
            'brand_id' => $brand->id ?? 1,
            'sub_brand_id' => $subBrand->id ?? 1,
            'product_category_id' => $productCategory->id ?? 1,
            'product_type_id' => $productType->id ?? 1,
            'product_size_id' => $productSize->id ?? 1,
            'is_active' => true,
        ];
    }
}

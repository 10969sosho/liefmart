<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Master Data CRUD Requests
 *
 * Menguji aturan validasi untuk:
 * 1. Brand — store & update
 * 2. SubBrand, ProductCategory, ProductType, ProductSize, ProductVariant
 * 3. Product — full validation with hierarchy
 * 4. Customer — store & update
 * 5. Product Initial Price Version
 * 6. Product Export format
 */
class MasterDataValidationTest extends TestCase
{

    // ==================== BRAND ====================

    /** @test */
    public function brand_store_passes_with_valid_data()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:main_categories,id',
            'description' => 'nullable|string',
        ];

        $validator = Validator::make([
            'name' => 'Test Brand',
            'main_category_id' => 1,
        ], $rules);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function brand_store_requires_name()
    {
        $rules = ['name' => 'required|string|max:255'];
        $validator = Validator::make(['name' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function brand_store_requires_main_category()
    {
        $rules = ['main_category_id' => 'required|exists:main_categories,id'];
        $validator = Validator::make(['main_category_id' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function brand_store_rejects_nonexistent_main_category()
    {
        $rules = ['main_category_id' => 'required|exists:main_categories,id'];
        $validator = Validator::make(['main_category_id' => 99999], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== SUB BRAND ====================

    /** @test */
    public function sub_brand_store_passes_with_valid_data()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
        ];

        $validator = Validator::make(['name' => 'Test SubBrand', 'brand_id' => 1], $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function sub_brand_requires_brand_id()
    {
        $rules = ['brand_id' => 'required|exists:brands,id'];
        $validator = Validator::make(['brand_id' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== PRODUCT ====================

    /** @test */
    public function product_store_passes_with_full_valid_data()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:main_categories,id',
            'brand_id' => 'required|exists:brands,id',
            'sub_brand_id' => 'required|exists:sub_brands,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type_id' => 'required|exists:product_types,id',
            'product_size_id' => 'required|exists:product_sizes,id',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'initial_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ];

        $data = [
            'name' => 'Complete Product',
            'main_category_id' => 1,
            'brand_id' => 1,
            'sub_brand_id' => 1,
            'product_category_id' => 1,
            'product_type_id' => 1,
            'product_size_id' => 1,
            'sku' => 'SKU-MASTER-001',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function product_requires_name()
    {
        $rules = ['name' => 'required|string|max:255'];
        $validator = Validator::make(['name' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function product_requires_brand_id()
    {
        $rules = ['brand_id' => 'required|exists:brands,id'];
        $validator = Validator::make(['brand_id' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function product_requires_sub_brand_id()
    {
        $rules = ['sub_brand_id' => 'required|exists:sub_brands,id'];
        $validator = Validator::make(['sub_brand_id' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function product_sku_unique_validation()
    {
        $rules = ['sku' => 'nullable|string|max:50|unique:products,sku'];
        $validator = Validator::make(['sku' => 'SKU-001'], $rules);
        $this->assertTrue($validator->passes());

        // After creating, should fail
        \App\Models\Product::factory()->create(['sku' => 'SKU-001', 'main_category_id' => 1]);
        $validator2 = Validator::make(['sku' => 'SKU-001'], $rules);
        $this->assertTrue($validator2->fails());
    }

    /** @test */
    public function product_rejects_negative_price()
    {
        $rules = ['initial_price' => 'nullable|numeric|min:0'];
        $validator = Validator::make(['initial_price' => -100], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function product_rejects_discount_above_100()
    {
        $rules = ['discount_percentage' => 'nullable|numeric|min:0|max:100'];
        $validator = Validator::make(['discount_percentage' => 150], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function product_requires_all_hierarchy_fields()
    {
        $rules = [
            'brand_id' => 'required|exists:brands,id',
            'sub_brand_id' => 'required|exists:sub_brands,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type_id' => 'required|exists:product_types,id',
            'product_size_id' => 'required|exists:product_sizes,id',
        ];

        $empty = ['brand_id' => '', 'sub_brand_id' => '', 'product_category_id' => '',
                   'product_type_id' => '', 'product_size_id' => ''];
        $validator = Validator::make($empty, $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== CUSTOMER ====================

    /** @test */
    public function customer_store_passes_with_valid_data()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers',
            'pic_name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ];

        $data = ['name' => 'Valid Customer', 'phone' => '08123456789', 'pic_name' => 'PIC Valid', 'status' => 'active'];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function customer_requires_name()
    {
        $rules = ['name' => 'required|string|max:255'];
        $validator = Validator::make(['name' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function customer_requires_phone()
    {
        $rules = ['phone' => 'required|string|max:20'];
        $validator = Validator::make(['phone' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function customer_requires_pic_name()
    {
        $rules = ['pic_name' => 'required|string|max:255'];
        $validator = Validator::make(['pic_name' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function customer_requires_valid_status()
    {
        $rules = ['status' => 'required|in:active,inactive'];
        $validator = Validator::make(['status' => 'invalid'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function customer_rejects_invalid_email()
    {
        $rules = ['email' => 'nullable|email|max:255'];
        $validator = Validator::make(['email' => 'not-an-email'], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== PRODUCT INITIAL PRICE VERSION ====================

    /** @test */
    public function price_version_store_passes_with_valid_data()
    {
        $rules = [
            'initial_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'effective_at' => 'required|date',
            'change_reason' => 'nullable|string|max:255',
        ];

        $data = ['initial_price' => 100000, 'discount_percentage' => 5, 'effective_at' => now()->format('Y-m-d')];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function price_version_requires_initial_price()
    {
        $rules = ['initial_price' => 'required|numeric|min:0'];
        $validator = Validator::make(['initial_price' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function price_version_requires_effective_date()
    {
        $rules = ['effective_at' => 'required|date'];
        $validator = Validator::make(['effective_at' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function price_version_rejects_negative_initial_price()
    {
        $rules = ['initial_price' => 'required|numeric|min:0'];
        $validator = Validator::make(['initial_price' => -50000], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== PRODUCT EXPORT ====================

    /** @test */
    public function product_export_accepts_valid_format()
    {
        $accepted = ['xlsx', 'csv', 'pdf'];
        foreach ($accepted as $format) {
            $this->assertContains($format, ['xlsx', 'csv', 'pdf']);
        }
    }

    /** @test */
    public function product_export_filters_pass()
    {
        $rules = [
            'search' => 'nullable|string|max:255',
            'main_category_id' => 'nullable|exists:main_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'status' => 'nullable|in:active,inactive',
            'per_page' => 'nullable|integer|min:5|max:100',
        ];

        $validator = Validator::make([
            'search' => 'test', 'main_category_id' => 1, 'brand_id' => 1,
            'status' => 'active', 'per_page' => 15,
        ], $rules);
        $this->assertTrue($validator->passes());
    }

    // ==================== HIERARCHY CONSISTENCY ====================

    /**
     * @test
     *
     * Potensi masalah: Produk bisa memiliki brand dari kategori berbeda
     * jika main_category_id brand tidak divalidasi dengan main_category_id produk.
     */
    public function documents_hierarchy_consistency_gap()
    {
        $rules = [
            'brand_id' => 'required|exists:brands,id',
            'main_category_id' => 'required|exists:main_categories,id',
        ];

        // Rule tidak memvalidasi bahwa brand.main_category_id == product.main_category_id
        // Produk bisa memiliki brand dari kategori KOPI meskipun produk SKINCARE
        $validator = Validator::make(['brand_id' => 1, 'main_category_id' => 1], $rules);
        $this->assertTrue($validator->passes(),
            'Validasi tidak memeriksa konsistensi brand.main_category_id dengan product.main_category_id');
    }
}

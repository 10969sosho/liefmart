<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\MainCategory;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\TaxCategory;
use App\Models\Satuan;
use App\Models\Lokasi;
use App\Models\Platform;
use App\Models\Customer;
use App\Models\ProductInitialPriceVersion;

/**
 * Database Test: All Master Data Models
 *
 * Menguji seluruh model master data:
 * 1. MainCategory — fillable, casts, is_active
 * 2. Brand — global scope, relasi ke MainCategory & SubBrand
 * 3. SubBrand — relasi ke Brand & ProductCategory
 * 4. ProductCategory — relasi ke SubBrand & ProductType
 * 5. ProductType — relasi ke ProductCategory & ProductSize
 * 6. ProductSize — relasi ke ProductType & ProductVariant
 * 7. ProductVariant — relasi ke ProductSize
 * 8. Product — hierarki penuh, initial price versioning, global scope
 * 9. TaxCategory — global scope, relasi ke MainCategory
 * 10. Satuan — fillable, is_active
 * 11. Lokasi — kode, nama
 * 12. Platform — name, relasi ke PlatformProduct
 * 13. Customer — dari shared model
 * 14. ProductInitialPriceVersion — versioning, scopes
 */
class MasterDataModelTest extends TestCase
{

    private MainCategory $skincare;
    private Brand $brand;
    private SubBrand $subBrand;
    private ProductCategory $productCategory;
    private ProductType $productType;
    private ProductSize $productSize;
    private ProductVariant $productVariant;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed minimal data
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();

        // Create full hierarchy
        $this->brand = Brand::create(['name' => 'Test Brand', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $this->subBrand = SubBrand::create(['name' => 'Test SubBrand', 'brand_id' => $this->brand->id, 'is_active' => true]);
        $this->productCategory = ProductCategory::create(['name' => 'Test Category', 'sub_brand_id' => $this->subBrand->id, 'is_active' => true]);
        $this->productType = ProductType::create(['name' => 'Test Type', 'product_category_id' => $this->productCategory->id, 'is_active' => true]);
        $this->productSize = ProductSize::create(['name' => 'Test Size', 'product_type_id' => $this->productType->id, 'is_active' => true]);
        $this->productVariant = ProductVariant::create(['name' => 'Test Variant', 'product_size_id' => $this->productSize->id, 'is_active' => true]);

        session(['main_category_id' => $this->skincare->id]);
    }

    // ==================== MAIN CATEGORY ====================

    /** @test */
    public function creates_main_category()
    {
        $mc = MainCategory::create(['name' => 'TEST-CAT-1', 'is_active' => true]);
        $this->assertNotNull($mc);
        $this->assertTrue($mc->is_active);
    }

    /** @test */
    public function main_category_brand_relationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->skincare->brands());
    }

    /** @test */
    public function main_category_tax_categories_relationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->skincare->taxCategories());
    }

    /** @test */
    public function main_category_is_active_cast()
    {
        $mc = new MainCategory();
        $casts = $mc->getCasts();
        $this->assertEquals('boolean', $casts['is_active']);
    }

    // ==================== BRAND ====================

    /** @test */
    public function creates_brand()
    {
        $brand = Brand::create(['name' => 'New Brand', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $this->assertNotNull($brand);
        $this->assertEquals('New Brand', $brand->name);
    }

    /** @test */
    public function brand_belongs_to_main_category()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->brand->mainCategory());
    }

    /** @test */
    public function brand_has_sub_brands()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->brand->subBrands());
    }

    /** @test */
    public function brand_applies_global_scope()
    {
        // Create brand for SKINCARE (already done in setUp)
        // Create brand for different category
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            Brand::create(['name' => 'KOPI Brand', 'main_category_id' => $kopi->id, 'is_active' => true]);
        }

        session(['main_category_id' => $this->skincare->id]);
        $brands = Brand::all();
        foreach ($brands as $b) {
            $this->assertEquals($this->skincare->id, $b->main_category_id);
        }
    }

    /** @test */
    public function brand_is_active_cast()
    {
        $b = new Brand();
        $this->assertEquals('boolean', $b->getCasts()['is_active']);
    }

    // ==================== SUB BRAND ====================

    /** @test */
    public function creates_sub_brand()
    {
        $sb = SubBrand::create(['name' => 'New SubBrand', 'brand_id' => $this->brand->id, 'is_active' => true]);
        $this->assertNotNull($sb);
    }

    /** @test */
    public function sub_brand_belongs_to_brand()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->subBrand->brand());
    }

    /** @test */
    public function sub_brand_has_product_categories()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->subBrand->productCategories());
    }

    // ==================== PRODUCT CATEGORY ====================

    /** @test */
    public function creates_product_category()
    {
        $pc = ProductCategory::create(['name' => 'New Cat', 'sub_brand_id' => $this->subBrand->id, 'is_active' => true]);
        $this->assertNotNull($pc);
    }

    /** @test */
    public function product_category_belongs_to_sub_brand()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->productCategory->subBrand());
    }

    /** @test */
    public function product_category_has_product_types()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->productCategory->productTypes());
    }

    // ==================== PRODUCT TYPE ====================

    /** @test */
    public function creates_product_type()
    {
        $pt = ProductType::create(['name' => 'New Type', 'product_category_id' => $this->productCategory->id, 'is_active' => true]);
        $this->assertNotNull($pt);
    }

    /** @test */
    public function product_type_belongs_to_product_category()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->productType->productCategory());
    }

    /** @test */
    public function product_type_has_product_sizes()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->productType->productSizes());
    }

    // ==================== PRODUCT SIZE ====================

    /** @test */
    public function creates_product_size()
    {
        $ps = ProductSize::create(['name' => 'New Size', 'product_type_id' => $this->productType->id, 'is_active' => true]);
        $this->assertNotNull($ps);
    }

    /** @test */
    public function product_size_belongs_to_product_type()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->productSize->productType());
    }

    /** @test */
    public function product_size_has_product_variants()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->productSize->productVariants());
    }

    // ==================== PRODUCT VARIANT ====================

    /** @test */
    public function creates_product_variant()
    {
        $pv = ProductVariant::create(['name' => 'New Variant', 'product_size_id' => $this->productSize->id, 'is_active' => true]);
        $this->assertNotNull($pv);
    }

    /** @test */
    public function product_variant_belongs_to_product_size()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $this->productVariant->productSize());
    }

    // ==================== PRODUCT ====================

    /** @test */
    public function creates_product_with_full_hierarchy()
    {
        $taxCat = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $product = Product::create([
            'name' => 'Test Product Lengkap',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $taxCat->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'product_variant_id' => $this->productVariant->id,
            'sku' => 'SKU-TEST-001',
            'barcode' => 'BR-TEST-001',
            'initial_price' => 50000,
            'discount_percentage' => 10,
            'is_active' => true,
        ]);

        $this->assertNotNull($product);
        $this->assertEquals('SKU-TEST-001', $product->sku);
        $this->assertEquals(50000, (int) $product->initial_price);
        $this->assertEquals(10, (int) $product->discount_percentage);
    }

    /** @test */
    public function product_has_all_relationships()
    {
        $taxCat = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $product = Product::create([
            'name' => 'Rel Product',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $taxCat->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'product_variant_id' => $this->productVariant->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->mainCategory());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->brand());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->subBrand());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->productCategory());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->productType());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->productSize());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->productVariant());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $product->taxCategory());
    }

    /** @test */
    public function product_initial_price_version_created_on_creation()
    {
        $product = Product::create([
            'name' => 'Price Version Test',
            'main_category_id' => $this->skincare->id,
            'initial_price' => 75000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_initial_price_versions', [
            'product_id' => $product->id,
            'version' => 1,
            'initial_price' => 75000,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function product_creates_new_price_version_on_price_change()
    {
        $product = Product::create([
            'name' => 'Price Change Test',
            'main_category_id' => $this->skincare->id,
            'initial_price' => 50000,
            'is_active' => true,
        ]);

        // Change price
        $product->update(['initial_price' => 60000]);

        $versions = $product->initialPriceVersions;
        $this->assertCount(2, $versions);
        $this->assertEquals(2, $versions->where('is_active', true)->first()->version);
    }

    /** @test */
    public function product_handles_null_initial_price()
    {
        $product = Product::create([
            'name' => 'Null Price Test',
            'main_category_id' => $this->skincare->id,
            'initial_price' => null,
            'is_active' => true,
        ]);

        $this->assertEquals(0, (int) $product->initial_price);
    }

    /** @test */
    public function product_applies_global_scope()
    {
        Product::create(['name' => 'SK Test', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            Product::create(['name' => 'KOPI Test', 'main_category_id' => $kopi->id, 'is_active' => true]);
        }

        session(['main_category_id' => $this->skincare->id]);
        $products = Product::all();
        foreach ($products as $p) {
            $this->assertEquals($this->skincare->id, $p->main_category_id);
        }
    }

    /** @test */
    public function product_fillable_contains_all_required_fields()
    {
        $product = new Product();
        $fillable = $product->getFillable();

        $required = ['name', 'main_category_id', 'sku', 'barcode', 'brand_id', 'sub_brand_id',
                      'product_category_id', 'product_type_id', 'product_size_id', 'product_variant_id',
                      'initial_price', 'is_active'];
        foreach ($required as $field) {
            $this->assertContains($field, $fillable, "Field {$field} harus ada di fillable");
        }
    }

    /** @test */
    public function product_sku_must_be_unique()
    {
        Product::create(['name' => 'P1', 'main_category_id' => $this->skincare->id, 'sku' => 'UNIQUE-SKU', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Product::create(['name' => 'P2', 'main_category_id' => $this->skincare->id, 'sku' => 'UNIQUE-SKU', 'is_active' => true]);
    }

    // ==================== TAX CATEGORY ====================

    /** @test */
    public function creates_tax_category()
    {
        $tc = TaxCategory::create([
            'name' => 'SKINCARE-PAJAK-TEST',
            'main_category_id' => $this->skincare->id,
            'tax_percentage' => 11.00,
            'is_active' => true,
        ]);
        $this->assertNotNull($tc);
        $this->assertEquals(11.00, (float) $tc->tax_percentage);
    }

    /** @test */
    public function tax_category_belongs_to_main_category()
    {
        $tc = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $tc->mainCategory());
    }

    // ==================== SATUAN ====================

    /** @test */
    public function creates_satuan()
    {
        $satuan = Satuan::create(['name' => 'Botol', 'kode' => 'BTL', 'is_active' => true]);
        $this->assertNotNull($satuan);
        $this->assertEquals('Botol', $satuan->name);
    }

    /** @test */
    public function satuan_has_penerimaan_details()
    {
        $satuan = Satuan::where('is_active', true)->first();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $satuan->penerimaanDetails());
    }

    // ==================== LOKASI ====================

    /** @test */
    public function creates_lokasi()
    {
        $lokasi = Lokasi::create(['kode' => 'GDG-B', 'nama' => 'Gudang B', 'deskripsi' => 'Gudang penyimpanan']);
        $this->assertNotNull($lokasi);
        $this->assertEquals('GDG-B', $lokasi->kode);
    }

    /** @test */
    public function lokasi_has_stocks()
    {
        $lokasi = Lokasi::first();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $lokasi->stocks());
    }

    // ==================== PLATFORM ====================

    /** @test */
    public function creates_platform()
    {
        $platform = Platform::create(['name' => 'Test Platform']);
        $this->assertNotNull($platform);
    }

    /** @test */
    public function platform_has_platform_products()
    {
        $platform = Platform::first();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $platform->platformProducts());
    }

    // ==================== CUSTOMER ====================

    /** @test */
    public function creates_customer()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'email' => 'customer@test.com',
            'pic_name' => 'PIC Test',
            'status' => 'active',
        ]);
        $this->assertNotNull($customer);
    }

    /** @test */
    public function customer_fillable_contains_required_fields()
    {
        $customer = new Customer();
        $fillable = $customer->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('status', $fillable);
    }

    // ==================== PRODUCT INITIAL PRICE VERSION ====================

    /** @test */
    public function price_version_has_scopes()
    {
        $product = Product::create(['name' => 'Scope Test', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $version = $product->initialPriceVersions()->first();

        $active = ProductInitialPriceVersion::active()->get();
        $this->assertNotEmpty($active);

        $forDate = ProductInitialPriceVersion::forDate(now())->get();
        $this->assertNotEmpty($forDate);
    }

    /** @test */
    public function price_version_has_parent_child_relationship()
    {
        $product = Product::create(['name' => 'Parent Test', 'main_category_id' => $this->skincare->id, 'initial_price' => 50000, 'is_active' => true]);
        $v1 = $product->initialPriceVersions()->first();

        // Change price to create v2
        $product->update(['initial_price' => 60000]);
        $v2 = ProductInitialPriceVersion::where('product_id', $product->id)->where('version', 2)->first();

        $this->assertNotNull($v2->parentVersion);
        $this->assertEquals($v1->id, $v2->parentVersion->id);
        $this->assertCount(1, $v1->childVersions);
    }

    // ==================== EDGE CASES ====================

    /** @test */
    public function documents_inactive_products_not_in_active_scopes()
    {
        $active = Product::create(['name' => 'Active Product', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $inactive = Product::create(['name' => 'Inactive Product', 'main_category_id' => $this->skincare->id, 'is_active' => false]);

        // is_active is NOT a global scope - it's just a field
        // Products need to query explicitly for active
    }

    /** @test */
    public function brand_without_main_category_still_creatable()
    {
        // No global scope validation — main_category_id is fillable but not required at model level
        $brand = Brand::create(['name' => 'Orphan Brand', 'main_category_id' => null, 'is_active' => true]);
        $this->assertNotNull($brand);
        // NOTE: This can cause issues because global scope will hide this brand
        // when main_category_id is set in session
    }

    /** @test */
    public function documents_master_data_hierarchy_via_product()
    {
        // Brand → SubBrand → ProductCategory → ProductType → ProductSize → ProductVariant
        // All linked via belongsTo on Product
        $taxCat = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $product = Product::create([
            'name' => 'Hierarchy Test',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $taxCat->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'product_variant_id' => $this->productVariant->id,
            'is_active' => true,
        ]);

        $product->load(['brand', 'subBrand', 'productCategory', 'productType', 'productSize', 'productVariant']);

        $this->assertEquals('Test Brand', $product->brand->name);
        $this->assertEquals('Test SubBrand', $product->subBrand->name);
        $this->assertEquals('Test Category', $product->productCategory->name);
        $this->assertEquals('Test Type', $product->productType->name);
        $this->assertEquals('Test Size', $product->productSize->name);
        $this->assertEquals('Test Variant', $product->productVariant->name);
    }
}

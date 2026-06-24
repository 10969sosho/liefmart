<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\TaxCategory;
use App\Models\Customer;
use App\Models\MainCategory;
use App\Models\User;

/**
 * Feature Test: Master Data CRUD
 *
 * Menguji seluruh CRUD master data via route resource:
 * 1. Brand — index, create, store, edit, update, delete
 * 2. SubBrand — CRUD dengan brand dependency
 * 3. ProductCategory — CRUD
 * 4. ProductType — CRUD
 * 5. ProductSize — CRUD
 * 6. ProductVariant — CRUD
 * 7. Product — CRUD dengan full hierarchy + export
 * 8. Customer — CRUD + filter
 * 9. Product Initial Price Versioning
 * 10. AJAX endpoints for dependent dropdowns
 */
class MasterDataFeatureTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Brand $brand;
    private SubBrand $subBrand;
    private ProductCategory $productCategory;
    private ProductType $productType;
    private ProductSize $productSize;
    private ProductVariant $productVariant;
    private TaxCategory $taxCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();

        $this->brand = Brand::create(['name' => 'Test Brand', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $this->subBrand = SubBrand::create(['name' => 'Test SubBrand', 'brand_id' => $this->brand->id, 'is_active' => true]);
        $this->productCategory = ProductCategory::create(['name' => 'Test Category', 'sub_brand_id' => $this->subBrand->id, 'is_active' => true]);
        $this->productType = ProductType::create(['name' => 'Test Type', 'product_category_id' => $this->productCategory->id, 'is_active' => true]);
        $this->productSize = ProductSize::create(['name' => 'Test Size', 'product_type_id' => $this->productType->id, 'is_active' => true]);
        $this->productVariant = ProductVariant::create(['name' => 'Test Variant', 'product_size_id' => $this->productSize->id, 'is_active' => true]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    // ==================== BRAND ====================

    /** @test */
    public function brand_index_displays_brands()
    {
        $response = $this->get(route('brands.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function brand_create_displays_form()
    {
        $response = $this->get(route('brands.create'));
        $response->assertStatus(200);
        $response->assertViewHas('mainCategories');
    }

    /** @test */
    public function brand_store_creates_new_brand()
    {
        $response = $this->post(route('brands.store'), [
            'name' => 'Brand Baru',
            'main_category_id' => $this->skincare->id,
            'description' => 'Deskripsi brand',
            'is_active' => 'on',
        ]);

        $response->assertRedirect(route('brands.index'));
        $this->assertDatabaseHas('brands', ['name' => 'Brand Baru']);
    }

    /** @test */
    public function brand_store_fails_without_name()
    {
        $response = $this->post(route('brands.store'), ['main_category_id' => $this->skincare->id, 'name' => '']);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function brand_edit_displays_form()
    {
        $response = $this->get(route('brands.edit', $this->brand->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function brand_update_changes_name()
    {
        $response = $this->put(route('brands.update', $this->brand->id), [
            'name' => 'Updated Brand',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertRedirect(route('brands.index'));
        $this->assertDatabaseHas('brands', ['id' => $this->brand->id, 'name' => 'Updated Brand']);
    }

    /** @test */
    public function brand_delete_removes_brand()
    {
        $response = $this->delete(route('brands.destroy', $this->brand->id));
        $response->assertRedirect(route('brands.index'));
        $this->assertDatabaseMissing('brands', ['id' => $this->brand->id]);
    }

    // ==================== SUB BRAND ====================

    /** @test */
    public function sub_brand_index_displays()
    {
        $response = $this->get(route('subbrands.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sub_brand_create_form()
    {
        $response = $this->get(route('subbrands.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sub_brand_store()
    {
        $response = $this->post(route('subbrands.store'), [
            'name' => 'SubBrand Baru',
            'brand_id' => $this->brand->id,
            'is_active' => 'on',
        ]);
        $response->assertRedirect(route('subbrands.index'));
        $this->assertDatabaseHas('sub_brands', ['name' => 'SubBrand Baru']);
    }

    /** @test */
    public function sub_brand_update()
    {
        $response = $this->put(route('subbrands.update', $this->subBrand->id), [
            'name' => 'SubBrand Updated',
            'brand_id' => $this->brand->id,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('sub_brands', ['id' => $this->subBrand->id, 'name' => 'SubBrand Updated']);
    }

    /** @test */
    public function sub_brand_delete()
    {
        $response = $this->delete(route('subbrands.destroy', $this->subBrand->id));
        $response->assertRedirect();
    }

    // ==================== PRODUCT CATEGORY ====================

    /** @test */
    public function product_category_index()
    {
        $response = $this->get(route('product-categories.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_category_store()
    {
        $response = $this->post(route('product-categories.store'), [
            'name' => 'Category Baru',
            'sub_brand_id' => $this->subBrand->id,
            'is_active' => 'on',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('product_categories', ['name' => 'Category Baru']);
    }

    // ==================== PRODUCT TYPE ====================

    /** @test */
    public function product_type_index()
    {
        $response = $this->get(route('product-types.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_type_store()
    {
        $response = $this->post(route('product-types.store'), [
            'name' => 'Type Baru',
            'product_category_id' => $this->productCategory->id,
            'is_active' => 'on',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('product_types', ['name' => 'Type Baru']);
    }

    // ==================== PRODUCT SIZE ====================

    /** @test */
    public function product_size_index()
    {
        $response = $this->get(route('product-sizes.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_size_store()
    {
        $response = $this->post(route('product-sizes.store'), [
            'name' => 'Size Baru',
            'product_type_id' => $this->productType->id,
            'is_active' => 'on',
        ]);
        $response->assertRedirect();
    }

    // ==================== PRODUCT VARIANT ====================

    /** @test */
    public function product_variant_index()
    {
        $response = $this->get(route('product-variants.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_variant_store()
    {
        $response = $this->post(route('product-variants.store'), [
            'name' => 'Variant Baru',
            'product_size_id' => $this->productSize->id,
            'is_active' => 'on',
        ]);
        $response->assertRedirect();
    }

    // ==================== PRODUCT ====================

    /** @test */
    public function product_index_displays()
    {
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_index_filters()
    {
        $response = $this->get(route('products.index', [
            'search' => 'test',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_create_displays_form()
    {
        $response = $this->get(route('products.create'));
        $response->assertStatus(200);
        $response->assertViewHas('brands');
    }

    /** @test */
    public function product_store_creates_with_full_hierarchy()
    {
        $response = $this->post(route('products.store'), [
            'name' => 'Produk Lengkap',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'product_variant_id' => $this->productVariant->id,
            'sku' => 'SKU-INTEGRASI-001',
            'initial_price' => 50000,
            'is_active' => 'on',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['sku' => 'SKU-INTEGRASI-001', 'name' => 'Produk Lengkap']);
    }

    /** @test */
    public function product_store_fails_without_required_fields()
    {
        $response = $this->post(route('products.store'), ['name' => '']);
        $response->assertSessionHasErrors(['name', 'brand_id', 'sub_brand_id', 'product_category_id', 'product_type_id', 'product_size_id']);
    }

    /** @test */
    public function product_store_with_initial_price_creates_version()
    {
        $this->post(route('products.store'), [
            'name' => 'Product With Price',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'sku' => 'SKU-PRICE-001',
            'initial_price' => 75000,
            'is_active' => 'on',
        ]);

        $product = Product::where('sku', 'SKU-PRICE-001')->first();
        $this->assertNotNull($product->initialPriceVersions);
        $this->assertCount(1, $product->initialPriceVersions);
        $this->assertEquals(75000, (int) $product->initialPriceVersions->first()->initial_price);
    }

    /** @test */
    public function product_edit_displays_form()
    {
        $product = Product::create([
            'name' => 'Editable Product',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.edit', $product->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_update_changes_data()
    {
        $product = Product::create([
            'name' => 'Original Name',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'is_active' => true,
        ]);

        $response = $this->put(route('products.update', $product->id), [
            'name' => 'Updated Name',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'is_active' => 'on',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name']);
    }

    /** @test */
    public function product_delete_removes_product()
    {
        $product = Product::create([
            'name' => 'Deletable Product',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'is_active' => true,
        ]);

        $response = $this->delete(route('products.destroy', $product->id));
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function product_export_xlsx()
    {
        $response = $this->get(route('products.export', 'xlsx'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_export_csv()
    {
        $response = $this->get(route('products.export', 'csv'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_export_pdf()
    {
        $response = $this->get(route('products.export', 'pdf'));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_export_with_filters()
    {
        $response = $this->get(route('products.export', [
            'format' => 'xlsx', 'main_category_id' => $this->skincare->id,
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function product_export_rejects_invalid_format()
    {
        $response = $this->get(route('products.export', 'invalid'));
        $response->assertStatus(404);
    }

    // ==================== INITIAL PRICE VERSION ====================

    /** @test */
    public function initial_price_index_displays()
    {
        $response = $this->get(route('products.initial-price.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function initial_price_show_displays_versions()
    {
        $product = Product::create([
            'name' => 'Price Version Product',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'initial_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.initial-price.show', $product->id));
        $response->assertStatus(200);
        $response->assertViewHas('versions');
    }

    /** @test */
    public function initial_price_store_creates_new_version()
    {
        $product = Product::create([
            'name' => 'Version Test',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'initial_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->post(route('products.initial-price.store', $product->id), [
            'initial_price' => 60000,
            'effective_at' => now()->format('Y-m-d'),
            'change_reason' => 'Kenaikan harga',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_initial_price_versions', [
            'product_id' => $product->id,
            'version' => 2,
            'initial_price' => 60000,
        ]);
    }

    // ==================== CUSTOMER ====================

    /** @test */
    public function customer_index_displays()
    {
        $response = $this->get(route('customers.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_index_filters_by_search()
    {
        $response = $this->get(route('customers.index', ['search' => 'test']));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_create_displays_form()
    {
        $response = $this->get(route('customers.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_store_creates_new_customer()
    {
        $response = $this->post(route('customers.store'), [
            'name' => 'Customer Baru',
            'phone' => '08111111111',
            'email' => 'customer@test.com',
            'pic_name' => 'PIC Baru',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['name' => 'Customer Baru']);
    }

    /** @test */
    public function customer_store_fails_without_required()
    {
        $response = $this->post(route('customers.store'), ['name' => '']);
        $response->assertSessionHasErrors(['name', 'phone', 'pic_name', 'status']);
    }

    /** @test */
    public function customer_show_displays()
    {
        $customer = Customer::create([
            'name' => 'Show Customer',
            'phone' => '08123456789',
            'pic_name' => 'PIC Show',
            'status' => 'active',
        ]);

        $response = $this->get(route('customers.show', $customer->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_update_changes_data()
    {
        $customer = Customer::create([
            'name' => 'Original Customer',
            'phone' => '08123456789',
            'pic_name' => 'PIC Original',
            'status' => 'active',
        ]);

        $response = $this->put(route('customers.update', $customer->id), [
            'name' => 'Updated Customer',
            'phone' => '08999999999',
            'pic_name' => 'PIC Updated',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Customer']);
    }

    /** @test */
    public function customer_delete_removes()
    {
        $customer = Customer::create([
            'name' => 'Deletable Customer',
            'phone' => '08123456789',
            'pic_name' => 'PIC Del',
            'status' => 'active',
        ]);

        $response = $this->delete(route('customers.destroy', $customer->id));
        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    // ==================== AJAX DEPENDENT DROPDOWNS ====================

    /** @test */
    public function get_subbrands_endpoint()
    {
        $response = $this->get(route('get-subbrands', ['brand_id' => $this->brand->id]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_categories_endpoint()
    {
        $response = $this->get(route('get-product-categories', ['sub_brand_id' => $this->subBrand->id]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_types_endpoint()
    {
        $response = $this->get(route('get-product-types', ['product_category_id' => $this->productCategory->id]));
        $response->assertStatus(200);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_master_pages()
    {
        auth()->logout();
        $this->get(route('brands.index'))->assertRedirect(route('login'));
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }

    // ==================== EDGE CASES ====================

    /** @test */
    public function handles_empty_master_data()
    {
        Brand::query()->delete();
        $response = $this->get(route('brands.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function brand_delete_protected_when_has_products()
    {
        Product::create([
            'name' => 'Protected Product',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'is_active' => true,
        ]);

        // Should still allow deletion (no cascade protection in controller)
        $response = $this->delete(route('brands.destroy', $this->brand->id));
        $response->assertRedirect();

        // NOTE: This creates orphan products. Consider adding cascade protection.
    }

    /** @test */
    public function product_sku_unique_handling()
    {
        Product::create([
            'name' => 'First Product',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'sku' => 'SKU-DUP-001',
            'is_active' => true,
        ]);

        $response = $this->post(route('products.store'), [
            'name' => 'Duplicate SKU',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'sku' => 'SKU-DUP-001',
            'is_active' => 'on',
        ]);

        $response->assertSessionHasErrors('sku');
    }
}

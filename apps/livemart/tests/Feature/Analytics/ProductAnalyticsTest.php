<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\MappingBarang;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\WarehouseStock;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\User;

/**
 * Feature Test: Product Analytics & Gross Profit Analytics
 *
 * 1. Sales By Master Product — table, modal, filters (brand/sub_brand/category/type/size/variant)
 * 2. Sales By Master Product Special
 * 3. Sales By Platform Product (Gross Profit)
 * 4. Produk Internal Terlaris
 * 5. Produk Platform Terlaris
 * 6. Gross Profit Offline Report — HPP calculation, margin
 * 7. AJAX endpoints: getSubBrands, getProductTypes, getProductSizes, etc
 * 8. ALL exports
 */
class ProductAnalyticsTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Platform $platform;
    private Brand $brand;
    private SubBrand $subBrand;
    private ProductCategory $productCategory;
    private ProductType $productType;
    private ProductSize $productSize;
    private ProductVariant $productVariant;
    private Product $product;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->platform = Platform::first();

        $this->brand = Brand::create(['name' => 'Analytics Brand', 'main_category_id' => $this->skincare->id, 'is_active' => true]);
        $this->subBrand = SubBrand::create(['name' => 'Analytics Sub', 'brand_id' => $this->brand->id, 'is_active' => true]);
        $this->productCategory = ProductCategory::create(['name' => 'Analytics Cat', 'sub_brand_id' => $this->subBrand->id, 'is_active' => true]);
        $this->productType = ProductType::create(['name' => 'Analytics Type', 'product_category_id' => $this->productCategory->id, 'is_active' => true]);
        $this->productSize = ProductSize::create(['name' => 'Analytics Size', 'product_type_id' => $this->productType->id, 'is_active' => true]);
        $this->productVariant = ProductVariant::create(['name' => 'Analytics Variant', 'product_size_id' => $this->productSize->id, 'is_active' => true]);

        $this->product = Product::factory()->create([
            'name' => 'Analytics Product Test',
            'main_category_id' => $this->skincare->id,
            'brand_id' => $this->brand->id,
            'sub_brand_id' => $this->subBrand->id,
            'product_category_id' => $this->productCategory->id,
            'product_type_id' => $this->productType->id,
            'product_size_id' => $this->productSize->id,
            'product_variant_id' => $this->productVariant->id,
            'sku' => 'SKU-ANALYTICS-001',
            'is_active' => true,
        ]);

        $stock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => Lokasi::first()->id,
            'tax_id' => TaxCategory::where('main_category_id', $this->skincare->id)->first()->id,
            'qty' => 100, 'source_type' => 'penerimaan', 'source_id' => 1,
        ]);

        $pp = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Platform Analytics Product',
        ]);

        $this->order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-PROD-ANALYTICS',
            'order_date' => now(), 'tanggal' => now(),
            'status' => 'completed', 'total_amount' => 300000,
            'main_category_id' => $this->skincare->id,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'platform_product_id' => $pp->id,
            'quantity' => 6, 'price_after_discount' => 50000,
            'warehouse_stock_id' => $stock->id,
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    // ==================== SALES BY MASTER PRODUCT ====================

    /** @test */
    public function sales_by_master_product_displays()
    {
        $response = $this->get(route('analytics.sales-by-master-product'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_master_product_with_filters()
    {
        $response = $this->get(route('analytics.sales-by-master-product', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'brand_id' => [$this->brand->id],
            'sub_brand_id' => [$this->subBrand->id],
            'product_category_id' => [$this->productCategory->id],
            'product_type_id' => [$this->productType->id],
            'product_size_id' => [$this->productSize->id],
            'product_variant_id' => [$this->productVariant->id],
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_master_product_table()
    {
        $response = $this->get(route('analytics.sales-by-master-product.table'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_master_product_modal()
    {
        $response = $this->get(route('analytics.sales-by-master-product.modal', ['product_id' => $this->product->id]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_master_product_export()
    {
        $response = $this->get(route('analytics.sales-by-master-product.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY MASTER PRODUCT SPECIAL ====================

    /** @test */
    public function sales_by_master_product_special_displays()
    {
        $response = $this->get(route('analytics.sales-by-master-product-special'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_master_product_special_table()
    {
        $response = $this->get(route('analytics.sales-by-master-product-special.table'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY PLATFORM PRODUCT (GROSS PROFIT) ====================

    /** @test */
    public function sales_by_platform_product_displays()
    {
        $response = $this->get(route('analytics.sales-by-platform-product'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_product_export()
    {
        $response = $this->get(route('analytics.sales-by-platform-product.export'));
        $response->assertStatus(200);
    }

    // ==================== PRODUK INTERNAL TERLARIS ====================

    /** @test */
    public function produk_internal_terlaris_displays()
    {
        $response = $this->get(route('analytics.produk-internal-terlaris'));
        $response->assertStatus(200);
    }

    /** @test */
    public function produk_internal_terlaris_with_filters()
    {
        $response = $this->get(route('analytics.produk-internal-terlaris', [
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'limit' => 20,
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function produk_internal_terlaris_export()
    {
        $response = $this->get(route('analytics.produk-internal-terlaris.export'));
        $response->assertStatus(200);
    }

    // ==================== PRODUK PLATFORM TERLARIS ====================

    /** @test */
    public function produk_platform_terlaris_displays()
    {
        $response = $this->get(route('analytics.produk-platform-terlaris'));
        $response->assertStatus(200);
    }

    /** @test */
    public function produk_platform_terlaris_export()
    {
        $response = $this->get(route('analytics.produk-platform-terlaris.export'));
        $response->assertStatus(200);
    }

    // ==================== AJAX ENDPOINTS ====================

    /** @test */
    public function get_brands_endpoint()
    {
        $response = $this->get(route('analytics.get-brands'));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_subbrands_endpoint()
    {
        $response = $this->get(route('analytics.get-subbrands', ['brand_ids' => [$this->brand->id]]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_categories_endpoint()
    {
        $response = $this->get(route('analytics.get-product-categories', ['sub_brand_ids' => [$this->subBrand->id]]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_types_endpoint()
    {
        $response = $this->get(route('analytics.get-product-types', ['category_ids' => [$this->productCategory->id]]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_sizes_endpoint()
    {
        $response = $this->get(route('analytics.get-product-sizes', ['type_ids' => [$this->productType->id]]));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_product_variants_endpoint()
    {
        $response = $this->get(route('analytics.get-product-variants', ['size_ids' => [$this->productSize->id]]));
        $response->assertStatus(200);
    }

    /** @test */
    public function ajax_endpoints_with_search()
    {
        $response = $this->get(route('analytics.get-subbrands', [
            'brand_ids' => [$this->brand->id],
            'search' => 'Analytics',
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function ajax_endpoints_with_empty_ids_return_empty()
    {
        $response = $this->get(route('analytics.get-subbrands', ['brand_ids' => []]));
        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    // ==================== GROSS PROFIT OFFLINE ====================

    /** @test */
    public function gross_profit_offline_displays()
    {
        $response = $this->get(route('analytics.offline.gross-profit'));
        $response->assertStatus(200);
    }

    /** @test */
    public function gross_profit_offline_with_filters()
    {
        $response = $this->get(route('analytics.offline.gross-profit', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function gross_profit_offline_export()
    {
        $response = $this->get(route('analytics.offline.gross-profit.export'));
        $response->assertStatus(200);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_product_analytics()
    {
        auth()->logout();
        $this->get(route('analytics.sales-by-master-product'))->assertRedirect(route('login'));
        $this->get(route('analytics.produk-internal-terlaris'))->assertRedirect(route('login'));
    }
}

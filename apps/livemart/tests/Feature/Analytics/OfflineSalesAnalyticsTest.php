<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\MainCategory;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use App\Models\TaxCategory;
use App\Models\User;

/**
 * Feature Test: Offline Sales Analytics
 *
 * 1. Offline Sales Detail Report — filters, summary
 * 2. Offline Monthly Sales Summary
 * 3. Offline Sales By Customer
 * 4. Offline Sales By Product
 * 5. ALL exports
 */
class OfflineSalesAnalyticsTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Product $product;
    private Customer $customer;
    private OfflineSale $sale;
    private OfflineSaleItem $saleItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->product = Product::factory()->create(['name' => 'Offline Analytics Product', 'main_category_id' => $this->skincare->id]);
        $this->customer = Customer::create(['name' => 'Analytics Customer', 'phone' => '08123456789', 'status' => 'active']);

        $stock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => Lokasi::first()->id,
            'tax_id' => TaxCategory::where('main_category_id', $this->skincare->id)->first()->id,
            'qty' => 100, 'source_type' => 'penerimaan', 'source_id' => 1,
        ]);

        $this->sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-OFF-ANALYTICS',
            'sale_date' => now(), 'customer_name' => $this->customer->name,
            'customer_id' => $this->customer->id,
            'subtotal' => 500000, 'total_amount' => 500000,
            'status' => 'paid', 'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $this->saleItem = OfflineSaleItem::create([
            'offline_sale_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'quantity' => 10, 'unit_price' => 50000, 'subtotal' => 500000,
            'warehouse_stock_id' => $stock->id,
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    /** @test */
    public function offline_analytics_index_displays()
    {
        $response = $this->get(route('analytics.offline.index'));
        $response->assertStatus(200);
    }

    // ==================== SALES DETAIL REPORT ====================

    /** @test */
    public function offline_sales_detail_report_displays()
    {
        $response = $this->get(route('analytics.offline.sales-detail-report'));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_sales_detail_report_with_filters()
    {
        $response = $this->get(route('analytics.offline.sales-detail-report', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_sales_detail_report_export()
    {
        $response = $this->get(route('analytics.offline.sales-detail-report.export'));
        $response->assertStatus(200);
    }

    // ==================== MONTHLY SALES SUMMARY ====================

    /** @test */
    public function offline_monthly_sales_summary_displays()
    {
        $response = $this->get(route('analytics.offline.monthly-sales-summary'));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_monthly_sales_summary_with_year()
    {
        $response = $this->get(route('analytics.offline.monthly-sales-summary', ['year' => now()->year]));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_monthly_sales_summary_export()
    {
        $response = $this->get(route('analytics.offline.monthly-sales-summary.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY CUSTOMER ====================

    /** @test */
    public function offline_sales_by_customer_displays()
    {
        $response = $this->get(route('analytics.offline.sales-by-customer'));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_sales_by_customer_with_filters()
    {
        $response = $this->get(route('analytics.offline.sales-by-customer', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_sales_by_customer_export()
    {
        $response = $this->get(route('analytics.offline.sales-by-customer.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY PRODUCT ====================

    /** @test */
    public function offline_sales_by_product_displays()
    {
        $response = $this->get(route('analytics.offline.sales-by-product'));
        $response->assertStatus(200);
    }

    /** @test */
    public function offline_sales_by_product_export()
    {
        $response = $this->get(route('analytics.offline.sales-by-product.export'));
        $response->assertStatus(200);
    }

    // ==================== EMPTY STATE ====================

    /** @test */
    public function offline_analytics_empty_state()
    {
        OfflineSale::query()->delete();
        OfflineSaleItem::query()->delete();

        $response = $this->get(route('analytics.offline.sales-detail-report'));
        $response->assertStatus(200);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_offline_analytics()
    {
        auth()->logout();
        $this->get(route('analytics.offline.index'))->assertRedirect(route('login'));
        $this->get(route('analytics.offline.sales-by-customer'))->assertRedirect(route('login'));
    }
}

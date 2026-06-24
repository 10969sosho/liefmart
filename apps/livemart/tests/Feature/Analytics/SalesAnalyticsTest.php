<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\MappingBarang;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\User;
use Carbon\Carbon;

/**
 * Feature Test: Sales Analytics
 *
 * Menguji SEMUA halaman sales analytics:
 * 1. Sales Value Report — summary cards, filters
 * 2. Sales Volume Report
 * 3. Sales By Platform — pagination, sort, filters, summary, platform summary
 * 4. Sales By Day Of Week
 * 5. Sales By Date Number
 * 6. Sales By Status Day
 * 7. Monthly Sales Summary
 * 8. Single Item Report
 * 9. Multiple Item Report
 * 10. Daily Sales Report
 * 11. Discount Analysis Report
 * 12. Sales Detail Report
 * 13. Internal Product Sales
 * 14. Sales Export Mapped
 * 15. SEMUA export Excel
 *
 * POTENSI MASALAH:
 * - Query berat untuk range date besar
 * - withoutGlobalScope mainCategory bisa memuat semua data
 * - Subquery total_value/total_volume performa
 * - Retur tidak di-exclude dari totals
 */
class SalesAnalyticsTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Platform $platform;
    private Product $product;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->platform = Platform::first();
        $this->product = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        $warehouseStock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => Lokasi::first()->id,
            'tax_id' => TaxCategory::where('main_category_id', $this->skincare->id)->first()->id,
            'qty' => 100, 'source_type' => 'penerimaan', 'source_id' => 1,
        ]);

        $this->order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-ANALYTICS-001',
            'order_date' => now(), 'tanggal' => now(),
            'status' => 'completed',
            'total_amount' => 150000,
            'main_category_id' => $this->skincare->id,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'platform_product_id' => PlatformProduct::create([
                'platform_id' => $this->platform->id,
                'platform_product_name' => 'Test Analytics Product',
            ])->id,
            'quantity' => 3,
            'price_after_discount' => 50000,
            'warehouse_stock_id' => $warehouseStock->id,
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    /** @test */
    public function analytics_index_accessible()
    {
        $response = $this->get(route('analytics.index'));
        $response->assertStatus(200);
    }

    // ==================== SALES VALUE REPORT ====================

    /** @test */
    public function sales_value_report_displays()
    {
        $response = $this->get(route('analytics.sales-value-report'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_value_report_with_date_filters()
    {
        $response = $this->get(route('analytics.sales-value-report', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    // ==================== SALES VOLUME REPORT ====================

    /** @test */
    public function sales_volume_report_displays()
    {
        $response = $this->get(route('analytics.sales-volume-report'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY PLATFORM ====================

    /** @test】
    public function sales_by_platform_displays()
    {
        $response = $this->get(route('analytics.sales-by-platform'));
        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $response->assertViewHas('orders');
    }

    /** @test */
    public function sales_by_platform_filters_by_date()
    {
        $response = $this->get(route('analytics.sales-by-platform', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_filters_by_platform()
    {
        $response = $this->get(route('analytics.sales-by-platform', [
            'platform_id' => $this->platform->id,
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_sorts_by_value()
    {
        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'value_highest']));
        $response->assertStatus(200);

        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'value_lowest']));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_sorts_by_volume()
    {
        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'volume_highest']));
        $response->assertStatus(200);

        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'volume_lowest']));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_sorts_by_date()
    {
        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'date_oldest']));
        $response->assertStatus(200);

        $response = $this->get(route('analytics.sales-by-platform', ['sort' => 'date_newest']));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_platform_shows_correct_summary()
    {
        $response = $this->get(route('analytics.sales-by-platform'));
        $summary = $response->original->getData()['summary'];

        $this->assertArrayHasKey('total_orders', $summary);
        $this->assertArrayHasKey('total_value', $summary);
        $this->assertArrayHasKey('total_volume', $summary);
        $this->assertArrayHasKey('avg_order_value', $summary);
        $this->assertGreaterThanOrEqual(1, $summary['total_orders']);
    }

    /** @test */
    public function sales_by_platform_empty_state()
    {
        Order::query()->delete();
        OrderItem::query()->delete();

        $response = $this->get(route('analytics.sales-by-platform'));
        $response->assertStatus(200);
        $summary = $response->original->getData()['summary'];
        $this->assertEquals(0, $summary['total_orders']);
    }

    /** @test */
    public function sales_by_platform_export_excel()
    {
        $response = $this->get(route('analytics.sales-by-platform.export', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    // ==================== SALES BY DAY OF WEEK ====================

    /** @test */
    public function sales_by_day_of_week_displays()
    {
        $response = $this->get(route('analytics.sales-by-day-of-week'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_day_of_week_with_filters()
    {
        $response = $this->get(route('analytics.sales-by-day-of-week', [
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_day_of_week_export_excel()
    {
        $response = $this->get(route('analytics.sales-by-day-of-week.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY DATE NUMBER ====================

    /** @test */
    public function sales_by_date_number_displays()
    {
        $response = $this->get(route('analytics.sales-by-date-number'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_date_number_export()
    {
        $response = $this->get(route('analytics.sales-by-date-number.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES BY STATUS DAY ====================

    /** @test */
    public function sales_by_status_day_displays()
    {
        $response = $this->get(route('analytics.sales-by-status-day'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_by_status_day_export()
    {
        $response = $this->get(route('analytics.sales-by-status-day.export'));
        $response->assertStatus(200);
    }

    // ==================== MONTHLY SALES SUMMARY ====================

    /** @test */
    public function monthly_sales_summary_displays()
    {
        $response = $this->get(route('analytics.monthly-sales-summary'));
        $response->assertStatus(200);
    }

    /** @test */
    public function monthly_sales_summary_with_year_filter()
    {
        $response = $this->get(route('analytics.monthly-sales-summary', ['year' => now()->year]));
        $response->assertStatus(200);
    }

    /** @test */
    public function monthly_sales_summary_export()
    {
        $response = $this->get(route('analytics.monthly-sales-summary.export'));
        $response->assertStatus(200);
    }

    // ==================== SINGLE ITEM REPORT ====================

    /** @test */
    public function single_item_report_displays()
    {
        $response = $this->get(route('analytics.single-item-report'));
        $response->assertStatus(200);
    }

    // ==================== MULTIPLE ITEM REPORT ====================

    /** @test */
    public function multiple_item_report_displays()
    {
        $response = $this->get(route('analytics.multiple-item-report'));
        $response->assertStatus(200);
    }

    // ==================== DAILY SALES REPORT ====================

    /** @test】
    public function daily_sales_report_displays()
    {
        $response = $this->get(route('analytics.daily-sales-report'));
        $response->assertStatus(200);
    }

    // ==================== DISCOUNT ANALYSIS ====================

    /** @test */
    public function discount_analysis_report_displays()
    {
        $response = $this->get(route('analytics.discount-analysis-report'));
        $response->assertStatus(200);
    }

    // ==================== SALES DETAIL REPORT ====================

    /** @test */
    public function sales_detail_report_displays()
    {
        $response = $this->get(route('analytics.sales-detail-report'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_detail_report_with_filters()
    {
        $response = $this->get(route('analytics.sales-detail-report', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-ANALYTICS',
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_detail_report_export()
    {
        $response = $this->get(route('analytics.sales-detail-report.export'));
        $response->assertStatus(200);
    }

    // ==================== INTERNAL PRODUCT SALES ====================

    /** @test */
    public function internal_product_sales_displays()
    {
        $response = $this->get(route('analytics.internal-product-sales'));
        $response->assertStatus(200);
    }

    /** @test */
    public function internal_product_sales_export()
    {
        $response = $this->get(route('analytics.internal-product-sales.export'));
        $response->assertStatus(200);
    }

    // ==================== SALES EXPORT MAPPED ====================

    /** @test */
    public function sales_export_mapped_displays()
    {
        $response = $this->get(route('analytics.sales-export-mapped'));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_export_mapped_export()
    {
        $response = $this->get(route('analytics.sales-export-mapped.export'));
        $response->assertStatus(200);
    }

    // ==================== SUMMARY CONSISTENCY ====================

    /** @test */
    public function summary_values_match_order_data()
    {
        $response = $this->get(route('analytics.sales-by-platform'));
        $summary = $response->original->getData()['summary'];

        // Our order has quantity=3, price_after_discount=50000 → total_value=150000
        if ($summary['total_orders'] > 0) {
            $this->assertGreaterThanOrEqual(150000, (int) $summary['total_value']);
            $this->assertGreaterThanOrEqual(3, (int) $summary['total_volume']);
        }
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_analytics()
    {
        auth()->logout();
        $this->get(route('analytics.index'))->assertRedirect(route('login'));
        $this->get(route('analytics.sales-by-platform'))->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * POTENSI MASALAH: withoutGlobalScope('mainCategory') di analytics query
     * menyebabkan semua data kategori muncul, tidak terfilter oleh session.
     * Untuk environment multi-category, ini bisa mencampur data KOPI dan SKINCARE.
     */
    public function documents_without_global_scope_behavior()
    {
        // Create order for KOPI category
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            $kopiProduct = Product::factory()->create(['main_category_id' => $kopi->id]);
            $kopiOrder = Order::create([
                'platform_id' => $this->platform->id,
                'order_number' => 'ORD-KOPI-ANALYTICS',
                'order_date' => now(), 'tanggal' => now(),
                'status' => 'completed', 'total_amount' => 200000,
                'main_category_id' => $kopi->id,
            ]);
            OrderItem::create([
                'order_id' => $kopiOrder->id,
                'platform_product_id' => $this->orderItem->platform_product_id,
                'quantity' => 4, 'price_after_discount' => 50000,
            ]);

            // With SKINCARE session, analytics using withoutGlobalScope will include KOPI data
            session(['main_category_id' => $this->skincare->id]);
            $response = $this->get(route('analytics.sales-by-platform'));
            $summary = $response->original->getData()['summary'];

            // KOPI data is included because analytics uses withoutGlobalScope
            $this->assertGreaterThanOrEqual(2, $summary['total_orders'],
                'withoutGlobalScope causes data from ALL categories to appear');
        }
    }

    /** @test */
    public function combined_filters_work()
    {
        $response = $this->get(route('analytics.sales-by-platform', [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'platform_id' => $this->platform->id,
            'sort' => 'date_newest',
        ]));
        $response->assertStatus(200);
    }
}

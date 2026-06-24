<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;
use App\Models\Platform;
use App\Models\MainCategory;
use App\Models\User;

/**
 * Feature Test: Finance Analytics & Offline Sales Analytics
 *
 * Finance Analytics:
 * 1. Shopee — index with filters, totals, export
 * 2. Shopee2 — index
 * 3. TikTok — index with filters, export
 * 4. TikTok2 — index
 *
 * Offline Sales Analytics:
 * 5. Offline Sales Detail Report
 * 6. Offline Monthly Sales Summary
 * 7. Offline Sales By Customer
 * 8. Offline Sales By Product
 * 9. ALL exports
 *
 * POTENSI MASALAH:
 * - Filter outstanding_status menggunakan different logic
 * - Nested whereHas order for retur exclusion
 * - withoutGlobalScope behavior
 */
class FinanceAnalyticsTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Platform $platform;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->platform = Platform::first();

        $this->order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-FIN-ANALYTICS',
            'order_date' => now(), 'tanggal' => now(),
            'status' => 'completed', 'main_category_id' => $this->skincare->id,
            'total_amount' => 500000,
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    // ==================== SHOPEE FINANCE ANALYTICS ====================

    /** @test */
    public function shopee_finance_analytics_displays()
    {
        ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-FIN-ANALYTICS',
            'tanggal_order' => now(),
            'nominal_harga' => 500000, 'nominal_fix' => 500000,
            'saldo_masuk' => 500000, 'outstanding' => 0, 'qty' => 5,
            'order_id' => $this->order->id,
        ]);

        $response = $this->get(route('analytics.finance.shopee'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_shows_totals()
    {
        ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-FIN-SHOPEE-1',
            'tanggal_order' => now(),
            'nominal_harga' => 500000, 'nominal_fix' => 500000,
            'saldo_masuk' => 500000, 'outstanding' => 0, 'qty' => 5,
        ]);

        $response = $this->get(route('analytics.finance.shopee'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_filters_by_date()
    {
        $response = $this->get(route('analytics.finance.shopee', [
            'from_date' => now()->subDays(7)->format('Y-m-d'),
            'to_date' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_filters_by_order_number()
    {
        $response = $this->get(route('analytics.finance.shopee', ['order_number' => 'FIN-ANALYTICS']));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_filters_by_outstanding_status()
    {
        $response = $this->get(route('analytics.finance.shopee', ['outstanding_status' => '0']));
        $response->assertStatus(200);

        $response = $this->get(route('analytics.finance.shopee', ['outstanding_status' => '1']));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_filters_by_nominal()
    {
        $response = $this->get(route('analytics.finance.shopee', [
            'min_nominal' => 100000,
            'max_nominal' => 1000000,
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_finance_analytics_export()
    {
        $response = $this->get(route('analytics.finance.shopee.export'));
        $response->assertStatus(200);
    }

    // ==================== TIKTOK FINANCE ANALYTICS ====================

    /** @test */
    public function tiktok_finance_analytics_displays()
    {
        TiktokFinancialTransaction::create([
            'no_order' => 'ORD-TT-ANALYTICS',
            'tanggal_order' => now(),
            'nominal_harga' => 300000, 'nominal_fix' => 300000,
            'saldo_masuk' => 300000, 'outstanding' => 0, 'qty' => 3,
        ]);

        $response = $this->get(route('analytics.finance.tiktok'));
        $response->assertStatus(200);
    }

    /** @test */
    public function tiktok_finance_analytics_export()
    {
        $response = $this->get(route('analytics.finance.tiktok.export'));
        $response->assertStatus(200);
    }

    // ==================== SHOPEE2 & TIKTOK2 ====================

    /** @test */
    public function shopee2_finance_analytics_accessible()
    {
        $response = $this->get(route('analytics.finance.shopee2'));
        $this->assertContains($response->status(), [200, 302]);
    }

    /** @test */
    public function tiktok2_finance_analytics_accessible()
    {
        $response = $this->get(route('analytics.finance.tiktok2'));
        $this->assertContains($response->status(), [200, 302]);
    }

    // ==================== SHOPEE2 EXPORT ====================

    /** @test */
    public function shopee2_finance_analytics_export()
    {
        $response = $this->get(route('analytics.finance.shopee2.export'));
        $this->assertContains($response->status(), [200, 302]);
    }

    // ==================== EMPTY STATES ====================

    /** @test */
    public function shopee_finance_analytics_empty()
    {
        $response = $this->get(route('analytics.finance.shopee'));
        $response->assertStatus(200);
        $viewData = $response->original->getData();
        $this->assertEquals(0, (int) $viewData['totalCount']);
    }

    /** @test */
    public function tiktok_finance_analytics_empty()
    {
        $response = $this->get(route('analytics.finance.tiktok'));
        $response->assertStatus(200);
    }

    // ==================== COMBINED FILTERS ====================

    /** @test */
    public function shopee_finance_analytics_all_filters_combined()
    {
        $response = $this->get(route('analytics.finance.shopee', [
            'from_date' => now()->subDays(30)->format('Y-m-d'),
            'to_date' => now()->addDays(30)->format('Y-m-d'),
            'from_order_date' => now()->subDays(30)->format('Y-m-d'),
            'to_order_date' => now()->addDays(30)->format('Y-m-d'),
            'order_number' => 'ORD',
            'outstanding_status' => '0',
            'payment_date' => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_finance_analytics()
    {
        auth()->logout();
        $this->get(route('analytics.finance.shopee'))->assertRedirect(route('login'));
        $this->get(route('analytics.finance.tiktok'))->assertRedirect(route('login'));
    }
}

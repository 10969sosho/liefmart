<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Platform;
use App\Models\MainCategory;

/**
 * Feature Test: Unpaid Orders
 */
class UnpaidOrdersTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $skincare = MainCategory::where('name', 'SKINCARE')->first();
        $platform = Platform::first();

        Order::create([
            'platform_id' => $platform->id,
            'order_number' => 'ORD-UNPAID-001',
            'order_date' => now(), 'tanggal' => now(),
            'status' => 'completed', 'main_category_id' => $skincare->id,
            'total_amount' => 500000,
        ]);

        session(['main_category_id' => $skincare->id]);
        $this->loginAsSuperadmin();
    }

    /** @test */
    public function unpaid_orders_page_accessible()
    {
        $response = $this->get(route('finance.unpaid-orders.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function unpaid_orders_export_excel()
    {
        $response = $this->get(route('finance.unpaid-orders.export.excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function unpaid_orders_export_pdf()
    {
        $response = $this->get(route('finance.unpaid-orders.export.pdf'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_blocked_from_unpaid_orders()
    {
        auth()->logout();
        $this->get(route('finance.unpaid-orders.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function unpaid_orders_empty_state()
    {
        Order::query()->delete();
        $response = $this->get(route('finance.unpaid-orders.index'));
        $response->assertStatus(200);
    }
}

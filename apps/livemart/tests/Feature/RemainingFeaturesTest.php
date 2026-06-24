<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MainCategory;
use App\Models\User;

/**
 * Feature Test: Remaining system features
 *
 * 1. Bank Accounts
 * 2. Maintenance & Under Construction pages
 * 3. Database Restore
 * 4. API check-order endpoint
 * 5. Table demo
 */
class RemainingFeaturesTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $skincare = MainCategory::where('name', 'SKINCARE')->first();
        session(['main_category_id' => $skincare->id]);
        $this->loginAsSuperadmin();
    }

    // ==================== BANK ACCOUNTS ====================

    /** @test */
    public function bank_accounts_index_accessible()
    {
        $response = $this->get(route('bank-accounts.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function bank_accounts_create_accessible()
    {
        $response = $this->get(route('bank-accounts.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function bank_accounts_store()
    {
        $response = $this->post(route('bank-accounts.store'), [
            'platform' => 'shopee',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'PT Test',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('bank_accounts', ['account_number' => '1234567890']);
    }

    // ==================== MAINTENANCE ====================

    /** @test */
    public function maintenance_page_accessible()
    {
        $response = $this->get(route('maintenance'));
        $response->assertStatus(200);
    }

    /** @test */
    public function under_construction_page_accessible()
    {
        $response = $this->get(route('under-construction'));
        $response->assertStatus(200);
    }

    // ==================== DATABASE RESTORE ====================

    /** @test */
    public function database_restore_index_accessible()
    {
        $response = $this->get(route('database-restore.index'));
        $response->assertStatus(200);
    }

    // ==================== API ENDPOINTS ====================

    /** @test */
    public function api_check_order_endpoint()
    {
        $response = $this->get(route('sales.check-order-exists', [
            'platform' => 'shopee',
            'order_number' => 'TEST-ORDER',
        ]));
        $response->assertStatus(200);
        $response->assertJsonStructure(['exists']);
    }

    /** @test */
    public function api_tax_categories_endpoint()
    {
        $response = $this->get('/api/tax-categories', [
            'main_category_id' => 1,
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function api_products_endpoint()
    {
        $response = $this->get('/api/products', [
            'main_category_id' => 1,
        ]);
        $response->assertStatus(200);
    }

    // ==================== TABLE DEMO ====================

    /** @test */
    public function table_demo_page_accessible()
    {
        $response = $this->get('/table-demo');
        $response->assertStatus(200);
    }

    // ==================== RETUR PEMBELIAN FULL CRUD ====================

    /** @test */
    public function retur_pembelian_export_accessible()
    {
        $response = $this->get(route('retur-pembelian.export'));
        $response->assertStatus(200);
    }

    /** @test */
    public function retur_penjualan_export_accessible()
    {
        $response = $this->get(route('retur-penjualan.export'));
        $response->assertStatus(200);
    }

    // ==================== FINANCE CHOOSE ====================

    /** @test */
    public function finance_choose_page_accessible()
    {
        $response = $this->get(route('finance.choose'));
        $response->assertStatus(200);
    }

    // ==================== GROSS PROFIT REPORT ONLINE ====================

    /** @test */
    public function gross_profit_report_online_accessible()
    {
        $response = $this->get(route('analytics.gross-profit-report'));
        $response->assertStatus(200);
    }

    // ==================== PERMISSION STORE/EDIT ====================

    /** @test */
    public function permission_edit_accessible()
    {
        $permission = \App\Models\Permission::first();
        if ($permission) {
            $response = $this->get(route('admin.permissions.edit', $permission->id));
            $response->assertStatus(200);
        }
    }
}

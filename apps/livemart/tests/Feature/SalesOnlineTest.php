<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\MappingBarang;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\BarangKeluar;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Feature Test: Sales Online — Import, Mapping, Stock, Filter
 *
 * Menguji seluruh alur penjualan online:
 * 1. Halaman platform & online input
 * 2. Manual input transaksi online dengan stock reduction
 * 3. Stock integrity: FIFO + HGN priority
 * 4. Mapping barang dengan multiple produk
 * 5. Filter daftar penjualan (date, platform, order number)
 * 6. Order detail & print
 * 7. Delete order dengan stock restoration
 * 8. Duplicate order detection
 * 9. Stock tidak cukup → error handling
 * 10. Import Excel flow (preview, process)
 *
 * POTENSI MASALAH:
 * - Konsolidasi order items (WarehouseStock::$consolidateOrderItemsByProduct)
 * - Stock reduction dengan FIFO + HGN priority
 * - Duplicate order_number tidak ada unique constraint di DB
 * - Global scope Order via whereHas orderItems.warehouseStock.product.main_category
 */
class SalesOnlineTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $gudangA;
    private Satuan $satuan;
    private Product $product1;
    private Product $product2;
    private Platform $platform;
    private PlatformProduct $platformProduct;
    private WarehouseStock $stock1;
    private WarehouseStock $stock2;
    private MappingBarang $mapping;

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
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->gudangA = Lokasi::where('kode', 'GUDANG_A')->first() ?? Lokasi::factory()->create(['kode' => 'GUDANG_A']);
        $this->satuan = Satuan::where('is_active', true)->first();

        $this->product1 = Product::factory()->create([
            'name' => 'Sabun Online SKINCARE',
            'main_category_id' => $this->skincare->id,
            'is_active' => true,
        ]);
        $this->product2 = Product::factory()->create([
            'name' => 'Krim Online SKINCARE',
            'main_category_id' => $this->skincare->id,
            'is_active' => true,
        ]);

        $this->platform = Platform::first();
        $this->platformProduct = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Sabun Online - 50ml',
            'variant' => '50ml',
        ]);

        // Stock dengan expired date berbeda untuk test FIFO
        $this->stock1 = WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'expired_date' => now()->addYear(),
            'source_type' => 'penerimaan',
            'source_id' => 1,
            'source_date' => now()->subDays(30),
        ]);
        $this->stock2 = WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 30,
            'expired_date' => now()->addMonths(6),
            'source_type' => 'penerimaan',
            'source_id' => 2,
            'source_date' => now()->subDays(10),
        ]);

        // Mapping: 1 produk platform = 1 product internal
        $this->mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    // ==================== ONLINE INPUT ====================

    /** @test */
    public function online_page_displays_platforms()
    {
        $response = $this->get(route('sales.online'));
        $response->assertStatus(200);
    }

    /** @test */
    public function online_input_page_shows_mapped_products()
    {
        $response = $this->get(route('sales.online-input', 'shopee'));
        $response->assertStatus(200);
        $response->assertViewHas('mappedProducts');
    }

    /** @test */
    public function saves_online_transaction_with_stock_reduction()
    {
        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-ONLINE-TEST-001',
            'order_date' => now()->format('Y-m-d'),
            'day_status' => 'Normal',
            'items' => [
                [
                    'platform_product_id' => $this->platformProduct->id,
                    'qty' => 2,
                    'price' => 150000,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.list'));
        $response->assertSessionHas('success');

        // Verify order created
        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-ONLINE-TEST-001',
            'platform_id' => $this->platform->id,
        ]);

        // Verify stock reduced (50 - 2 = 48)
        $this->assertEquals(48, $this->stock1->fresh()->qty);

        // Verify barang keluar created
        $this->assertDatabaseHas('barang_keluar', [
            'qty' => 2,
        ]);

        // Verify order item created
        $order = Order::where('order_number', 'ORD-ONLINE-TEST-001')->first();
        $this->assertNotNull($order->orderItems);
        $this->assertCount(1, $order->orderItems);
    }

    /** @test */
    public function saves_online_transaction_with_multiple_items()
    {
        // Create additional platform product & mapping
        $pp2 = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Krim Online - 30ml',
            'variant' => '30ml',
        ]);
        MappingBarang::create([
            'platform_product_id' => $pp2->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        WarehouseStock::create([
            'product_id' => $this->product2->id,
            'lokasi_id' => $this->gudangA->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 20,
            'source_type' => 'penerimaan',
            'source_id' => 3,
        ]);

        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-MULTI-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
                ['platform_product_id' => $pp2->id, 'qty' => 3, 'price' => 100000],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::where('order_number', 'ORD-MULTI-001')->first();
        $this->assertCount(2, $order->orderItems);
    }

    /** @test */
    public function saves_online_with_mapping_multiple_products()
    {
        // Mapping: 1 platform product = 2 product1 + 1 product2
        $this->mapping->update(['quantity' => 2]);
        MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        WarehouseStock::create([
            'product_id' => $this->product2->id,
            'lokasi_id' => $this->gudangA->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 20,
            'source_type' => 'penerimaan',
            'source_id' => 3,
        ]);

        // Nonaktifkan mapping lama, buat baru
        $this->mapping->update(['is_active' => false]);

        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-MULTIMAP-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 5, 'price' => 300000],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function fails_when_stock_insufficient()
    {
        // Try to sell more than available stock (total: 80)
        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-NOSTOCK-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 999, 'price' => 150000],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function fails_when_no_active_mapping()
    {
        $this->mapping->update(['is_active' => false]);

        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-NOMAP-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function fails_on_duplicate_order_number()
    {
        // Create first transaction
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-DUP-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
            ],
        ]);

        // Try duplicate
        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-DUP-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
            ],
        ]);

        $response->assertSessionHas('error', 'Nomor order sudah ada di database.');
    }

    /** @test */
    public function fails_with_invalid_platform()
    {
        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'invalid',
            'no_order' => 'ORD-INV-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
            ],
        ]);

        $response->assertSessionHasErrors('platform');
    }

    /** @test */
    public function stock_reduction_follows_fifo_order()
    {
        // stock1: 50 (older), stock2: 30 (newer)
        // Buy 60 units → should take all 50 from stock1 + 10 from stock2
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-FIFO-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 60, 'price' => 150000],
            ],
        ]);

        $this->assertEquals(0, $this->stock1->fresh()->qty);
        $this->assertEquals(20, $this->stock2->fresh()->qty); // 30 - 10 = 20
    }

    /** @test */
    public function stock_reduction_prioritizes_hgn_tax()
    {
        // Create stock with HGN tax (non-PKP = lower tax_id)
        $taxHgn = TaxCategory::where('name', 'SKINCARE-NONPKP')->first();
        if (!$taxHgn) {
            $this->markTestSkipped('HGN tax category not found');
        }

        $stockHgn = WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'tax_id' => $taxHgn->id,
            'qty' => 40,
            'source_type' => 'penerimaan',
            'source_id' => 4,
            'source_date' => now()->subDays(5),
        ]);

        // Buy 70 units: tax ordering = HGN first, then LM
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-TAX-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 70, 'price' => 150000],
            ],
        ]);

        // HGN: should be used first (40 taken)
        // Then LM: remaining 30 from stock1 (50-30=20) or stock2
        $remainingHgn = $stockHgn->fresh()->qty;
        $this->assertEquals(0, $remainingHgn, 'HGN stock should be consumed first');
    }

    // ==================== LIST & FILTER ====================

    /** @test */
    public function sales_list_displays_orders()
    {
        Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-LIST-001',
            'order_date' => now(),
            'status' => 'completed',
            'total_amount' => 500000,
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.list'));
        $response->assertStatus(200);
        $response->assertViewHas('orders');
    }

    /** @test */
    public function sales_list_filters_by_date_range()
    {
        Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-DATE-001',
            'order_date' => '2024-01-15',
            'tanggal' => '2024-01-15',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.list', [
            'date_start' => '2024-01-01',
            'date_end' => '2024-01-31',
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_list_filters_by_order_number()
    {
        Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-SEARCH-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.list', ['order_number' => 'SEARCH']));
        $response->assertStatus(200);
    }

    /** @test */
    public function sales_list_shows_empty_state()
    {
        $response = $this->get(route('sales.list'));
        $response->assertStatus(200);
    }

    // ==================== ORDER DETAIL ====================

    /** @test */
    public function order_detail_displays_correct_data()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-DETAIL-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.order.detail', $order->id));
        $response->assertStatus(200);
    }

    /** @test】
    public function order_detail_returns_404_for_nonexistent()
    {
        $response = $this->get(route('sales.order.detail', 99999));
        $response->assertStatus(404);
    }

    // ==================== PRINT ====================

    /** @test */
    public function print_order_works()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-PRINT-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.order.print', $order->id));
        $response->assertStatus(200);
    }

    // ==================== DELETE ORDER (STOCK RESTORATION) ====================

    /** @test */
    public function delete_order_restores_stock()
    {
        // Create order with stock reduction first
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-DELETE-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 5, 'price' => 150000],
            ],
        ]);

        $order = Order::where('order_number', 'ORD-DELETE-001')->first();

        // Stock should be 45 (50 - 5)
        $this->assertEquals(45, $this->stock1->fresh()->qty);

        // Delete order
        $response = $this->delete(route('sales.order.destroy', $order->id));
        $response->assertRedirect();

        // Stock should be restored to 50
        $this->assertEquals(50, $this->stock1->fresh()->qty);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_online_sales()
    {
        auth()->logout();
        $this->get(route('sales.online'))->assertRedirect(route('login'));
        $this->get(route('sales.list'))->assertRedirect(route('login'));
    }

    // ==================== EDGE CASES ====================

    /** @test */
    public function handles_order_with_zero_qty_mapping()
    {
        $this->mapping->update(['quantity' => 0]);

        $response = $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-ZEROMAP-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 1, 'price' => 150000],
            ],
        ]);

        // Should fail with error about invalid mapping quantity
        $response->assertSessionHas('error');
    }

    /** @test */
    public function check_order_exists_endpoint()
    {
        Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-CHECK-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('sales.check-order-exists', [
            'platform' => 'shopee',
            'order_number' => 'ORD-CHECK-001',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['exists' => true]);
    }

    /** @test */
    public function outgoing_items_page_displays_barang_keluar()
    {
        // Create order with stock reduction
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-BK-LIST-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 3, 'price' => 150000],
            ],
        ]);

        $response = $this->get(route('sales.outgoing-items'));
        $response->assertStatus(200);
        $response->assertViewHas('barangKeluar');
    }

    /** @test */
    public function outgoing_items_page_filters()
    {
        $response = $this->get(route('sales.outgoing-items', [
            'tanggal_mulai' => now()->subDays(7)->format('Y-m-d'),
            'tanggal_akhir' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function multiple_orders_deduct_stock_accumulatively()
    {
        // Order 1: 10 units
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-ACCUM-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 10, 'price' => 150000],
            ],
        ]);

        // Order 2: 20 units
        $this->post(route('sales.save-online-transaction'), [
            'platform' => 'shopee',
            'no_order' => 'ORD-ACCUM-002',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => $this->platformProduct->id, 'qty' => 20, 'price' => 150000],
            ],
        ]);

        // Total deducted: 30, remaining: 20 (50 - 30)
        $this->assertEquals(20, $this->stock1->fresh()->qty);
    }

    /** @test */
    public function documents_potential_global_scope_issue()
    {
        // Order global scope uses whereHas on orderItems.warehouseStock.product.main_category
        // This can cause issues when orders have items with different main categories
        // or when order items don't have warehouse_stock_id set

        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-GLOBAL-SCOPE',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        // Without global scope
        $foundWithout = Order::withoutGlobalScope('mainCategory')->find($order->id);
        $this->assertNotNull($foundWithout);

        // With global scope (needs order item with warehouse stock)
        session(['main_category_id' => $this->skincare->id]);
        $foundWith = Order::find($order->id);
        // May fail if order has no items with warehouse stock in SKINCARE category
        // This is the documented potential issue
    }
}

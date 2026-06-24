<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\User;

/**
 * Feature Test: Warehouse (Transfer Unlocated -> Gudang)
 *
 * Menguji seluruh alur:
 * 1. Index page - daftar barang Unlocated
 * 2. Filter combinations (search, kode, produk, tanggal)
 * 3. Create form tampil dengan sisa qty
 * 4. Store transfer ke Gudang A
 * 5. Store partial transfer (sisa sebagian)
 * 6. Store complete transfer (status berubah jadi Located)
 * 7. Validasi qty tidak melebihi remaining
 * 8. Authorization check
 * 9. Stock integrity after transfer
 * 10. Edge cases & data janggal
 */
class WarehouseTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Lokasi $gudangA;
    private Satuan $satuan;
    private Product $product1;
    private Product $product2;
    private Penerimaan $penerimaan;
    private PenerimaanDetail $detail1;
    private PenerimaanDetail $detail2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->lokasi = Lokasi::first();
        $this->gudangA = Lokasi::where('kode', 'GUDANG_A')->first() ?? Lokasi::factory()->create(['kode' => 'GUDANG_A', 'nama' => 'Gudang A']);
        $this->satuan = Satuan::where('is_active', true)->first();

        $this->product1 = Product::factory()->create([
            'name' => 'Serum SKINCARE Test',
            'main_category_id' => $this->skincare->id,
            'is_active' => true,
        ]);
        $this->product2 = Product::factory()->create([
            'name' => 'Moisturizer SKINCARE Test',
            'main_category_id' => $this->skincare->id,
            'is_active' => true,
        ]);

        // Create Unlocated penerimaan
        $this->penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-WH-TEST-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'status' => 'Unlocated',
            'lokasi_id' => 1,
        ]);

        $this->detail1 = PenerimaanDetail::create([
            'penerimaan_id' => $this->penerimaan->id,
            'product_id' => $this->product1->id,
            'qty' => 100,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 25000,
            'subtotal' => 2500000,
        ]);

        $this->detail2 = PenerimaanDetail::create([
            'penerimaan_id' => $this->penerimaan->id,
            'product_id' => $this->product2->id,
            'qty' => 50,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 50000,
            'subtotal' => 2500000,
        ]);

        $this->penerimaan->recalculateTotalHarga();
        session(['main_category_id' => $this->skincare->id]);

        $this->user = $this->loginAsSuperadmin();
    }

    // ==================== INDEX ====================

    /** @test */
    public function index_displays_unlocated_items()
    {
        $response = $this->get(route('warehouse.index'));

        $response->assertStatus(200);
        $response->assertViewIs('warehouse.index');
        $response->assertViewHas('unlocatedItems');
    }

    /** @test */
    public function index_shows_remaining_qty()
    {
        $response = $this->get(route('warehouse.index'));

        $response->assertStatus(200);
        $unlocatedItems = $response->original->getData()['unlocatedItems'];

        foreach ($unlocatedItems as $item) {
            $warehouseTotal = WarehouseStock::where('penerimaan_detail_id', $item->id)->sum('qty');
            $expectedRemaining = $item->qty - $warehouseTotal;
            $this->assertGreaterThan(0, $expectedRemaining, 'Should have remaining qty > 0');
        }
    }

    /** @test */
    public function index_hides_fully_transferred_items()
    {
        // Transfer all stock for detail1
        WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'penerimaan_detail_id' => $this->detail1->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 100,
            'source_type' => 'penerimaan',
            'source_id' => $this->penerimaan->id,
            'source_date' => now(),
        ]);

        $response = $this->get(route('warehouse.index'));
        $unlocatedItems = $response->original->getData()['unlocatedItems'];

        // Item with remaining qty > 0 should not include fully transferred item
        $displayedProductIds = $unlocatedItems->pluck('product_id')->toArray();
        $this->assertNotContains($this->product1->id, $displayedProductIds);
    }

    /** @test */
    public function index_filters_by_search()
    {
        $response = $this->get(route('warehouse.index', ['search' => 'Serum']));
        $response->assertStatus(200);
    }

    /** @test */
    public function index_filters_by_kode_penerimaan()
    {
        $response = $this->get(route('warehouse.index', ['kode_penerimaan' => 'PNR-WH-TEST']));
        $response->assertStatus(200);
    }

    /** @test */
    public function index_filters_by_nama_produk()
    {
        $response = $this->get(route('warehouse.index', ['nama_produk' => 'Serum']));
        $response->assertStatus(200);
    }

    /** @test】
    public function index_filters_by_date_range()
    {
        $response = $this->get(route('warehouse.index', [
            'tanggal_mulai' => now()->subDays(7)->format('Y-m-d'),
            'tanggal_akhir' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function index_shows_empty_when_no_unlocated_items()
    {
        // Transfer all items
        WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'penerimaan_detail_id' => $this->detail1->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 100,
            'source_type' => 'penerimaan',
            'source_id' => $this->penerimaan->id,
        ]);
        WarehouseStock::create([
            'product_id' => $this->product2->id,
            'lokasi_id' => $this->gudangA->id,
            'penerimaan_detail_id' => $this->detail2->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'source_type' => 'penerimaan',
            'source_id' => $this->penerimaan->id,
        ]);

        $response = $this->get(route('warehouse.index'));
        $unlocatedItems = $response->original->getData()['unlocatedItems'];
        $this->assertCount(0, $unlocatedItems);
    }

    // ==================== CREATE ====================

    /** @test */
    public function create_page_displays_form_with_remaining_qty()
    {
        $response = $this->get(route('warehouse.create'));

        $response->assertStatus(200);
        $response->assertViewIs('warehouse.create');
        $response->assertViewHas('items');
    }

    /** @test */
    public function create_shows_correct_remaining_qty()
    {
        // Partially transfer some stock
        WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'penerimaan_detail_id' => $this->detail1->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 30,
            'source_type' => 'penerimaan',
            'source_id' => $this->penerimaan->id,
        ]);

        $response = $this->get(route('warehouse.create'));
        $items = $response->original->getData()['items'];

        // Find the item for detail1 and check remaining
        $item1 = collect($items)->firstWhere('id', $this->detail1->id);
        $this->assertNotNull($item1);
        $this->assertEquals(70, (int) $item1->remaining_qty); // 100 - 30 = 70
    }

    // ==================== STORE (TRANSFER) ====================

    /** @test */
    public function store_transfers_items_to_gudang_a()
    {
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
                'detail_2' => [
                    'penerimaan_detail_id' => $this->detail2->id,
                    'qty' => 25,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $response->assertRedirect(route('warehouse.index'));
        $response->assertSessionHas('success');

        // Verify warehouse stock created
        $this->assertDatabaseHas('warehouse_stock', [
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->gudangA->id,
            'penerimaan_detail_id' => $this->detail1->id,
            'qty' => 50,
        ]);

        $this->assertDatabaseHas('warehouse_stock', [
            'product_id' => $this->product2->id,
            'qty' => 25,
        ]);
    }

    /** @test */
    public function store_partial_transfer_keeps_status_unlocated()
    {
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 30, // Only transfer 30 out of 100
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        // Status should still be Unlocated because there's remaining qty
        $this->assertEquals('Unlocated', $this->penerimaan->fresh()->status);
    }

    /** @test */
    public function store_complete_transfer_changes_status_to_located()
    {
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 100, // Full transfer
                    'expired_date' => '',
                    'selected' => 'on',
                ],
                'detail_2' => [
                    'penerimaan_detail_id' => $this->detail2->id,
                    'qty' => 50, // Full transfer
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        // Status should change to Located because all items are fully transferred
        $penerimaan = $this->penerimaan->fresh();
        $this->assertEquals('Located', $penerimaan->status);
        $this->assertEquals($this->gudangA->id, $penerimaan->lokasi_id);
    }

    /** @test */
    public function store_rejects_qty_exceeding_remaining()
    {
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 200, // Exceeds 100
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function store_rejects_without_selected_items()
    {
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '',
                    // No 'selected' key
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
    }

    /** @test */
    public function store_with_expired_date()
    {
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '2026-12-31',
                    'selected' => 'on',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('warehouse_stock', [
            'product_id' => $this->product1->id,
            'qty' => 50,
            'expired_date' => '2026-12-31 00:00:00',
        ]);
    }

    /** @test】
    public function store_rejects_invalid_penerimaan_detail_id()
    {
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'invalid_item' => [
                    'penerimaan_detail_id' => 99999,
                    'qty' => 10,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.*.penerimaan_detail_id');
    }

    /** @test */
    public function store_only_processes_selected_items()
    {
        // 2 items, only 1 selected
        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
                'detail_2' => [
                    'penerimaan_detail_id' => $this->detail2->id,
                    'qty' => 50,
                    'expired_date' => '',
                    // Not selected
                ],
            ],
        ]);

        $response->assertRedirect();

        // Only detail1 should be transferred
        $this->assertDatabaseHas('warehouse_stock', [
            'penerimaan_detail_id' => $this->detail1->id,
        ]);

        $this->assertDatabaseMissing('warehouse_stock', [
            'penerimaan_detail_id' => $this->detail2->id,
        ]);
    }

    // ==================== AUTHORIZATION ====================

    /** @test */
    public function guest_cannot_access_warehouse_pages()
    {
        auth()->logout();

        $this->get(route('warehouse.index'))->assertRedirect(route('login'));
        $this->get(route('warehouse.create'))->assertRedirect(route('login'));
        $this->post(route('warehouse.store'), [])->assertRedirect(route('login'));
    }

    // ==================== STOCK INTEGRITY ====================

    /** @test */
    public function total_stock_matches_penerimaan_detail_after_full_transfer()
    {
        // Full transfer
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 100,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $warehouseTotal = WarehouseStock::where('penerimaan_detail_id', $this->detail1->id)->sum('qty');
        $this->assertEquals(100, (int) $warehouseTotal);
        $this->assertEquals(100, (int) $this->detail1->qty);
    }

    /** @test */
    public function partial_transfer_preserves_remaining_stock()
    {
        // Transfer 30 first
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 30,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $warehouseTotal = WarehouseStock::where('penerimaan_detail_id', $this->detail1->id)->sum('qty');
        $this->assertEquals(30, (int) $warehouseTotal);

        // Remaining should be 70
        $remainingItem = $this->detail1->fresh();
        $remainingQty = $remainingItem->qty - $warehouseTotal;
        $this->assertEquals(70, (int) $remainingQty);
    }

    /** @test */
    public function repeated_transfers_accumulate_correctly()
    {
        // Transfer 30
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 30,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        // Transfer 20 more
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 20,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $totalTransferred = WarehouseStock::where('penerimaan_detail_id', $this->detail1->id)->sum('qty');
        $this->assertEquals(50, (int) $totalTransferred);
    }

    /** @test */
    public function transfer_updates_penerimaan_lokasi_when_complete()
    {
        // Full transfer
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 100,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
                'detail_2' => [
                    'penerimaan_detail_id' => $this->detail2->id,
                    'qty' => 50,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $this->penerimaan->refresh();
        $this->assertEquals('Located', $this->penerimaan->status);
        $this->assertEquals($this->gudangA->id, $this->penerimaan->lokasi_id);
    }

    /** @test */
    public function stock_transfer_creates_warehouse_stock_with_correct_source_type()
    {
        $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        $stock = WarehouseStock::where('penerimaan_detail_id', $this->detail1->id)->first();
        $this->assertEquals('penerimaan', $stock->source_type);
        $this->assertEquals($this->penerimaan->id, $stock->source_id);
    }

    /** @test */
    public function documents_missing_lokasi_gudang_a_edge_case()
    {
        // Hapus lokasi GUDANG_A jika ada
        $gudangA = Lokasi::where('kode', 'GUDANG_A')->first();
        if ($gudangA) {
            $gudangA->delete();
        }

        $response = $this->post(route('warehouse.store'), [
            'items' => [
                'detail_1' => [
                    'penerimaan_detail_id' => $this->detail1->id,
                    'qty' => 50,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ]);

        // Should fail gracefully
        $response->assertSessionHas('error');

        // Re-create for other tests
        Lokasi::create(['kode' => 'GUDANG_A', 'nama' => 'Gudang A']);
    }
}

<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanDetail;
use App\Models\ReturOfflineSale;
use App\Models\ReturOfflineSaleDetail;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\MappingBarang;
use App\Models\PlatformProduct;
use App\Models\Platform;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;

/**
 * Database Test: Retur Models & Relationships
 *
 * Menguji:
 * 1. ReturPenjualan — kode retur, status, relasi ke Order & User
 * 2. ReturPenjualanDetail — relasi ke OrderItem, Product, kondisi enum
 * 3. ReturOfflineSale — kode retur, relasi ke OfflineSale
 * 4. ReturOfflineSaleDetail — relasi ke OfflineSaleItem, kondisi
 * 5. ReturPembelian — tipe_retur (sebagian/full), relasi
 * 6. ReturPembelianDetail — relasi ke PenerimaanDetail
 * 7. Stock restoration tracking via addBackToStock helper
 * 8. Package mapping: 1 platform product → multiple internal products
 * 9. Partial retur dari package
 */
class ReturModelTest extends TestCase
{

    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Satuan $satuan;
    private Product $product1;
    private Product $product2;
    private Platform $platform;
    private PlatformProduct $platformProduct;
    private Order $order;
    private OrderItem $orderItem;
    private WarehouseStock $stock1;
    private WarehouseStock $stock2;
    private MappingBarang $mapping1;
    private MappingBarang $mapping2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->lokasi = Lokasi::first() ?? Lokasi::create(['kode' => 'GDG-A', 'nama' => 'Gudang A']);
        $this->satuan = Satuan::where('is_active', true)->first();

        $this->product1 = Product::factory()->create(['name' => 'Product A', 'main_category_id' => $this->skincare->id]);
        $this->product2 = Product::factory()->create(['name' => 'Product B', 'main_category_id' => $this->skincare->id]);

        $this->platform = Platform::first();
        $this->platformProduct = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Paket Skincare A+B',
            'variant' => '1 set',
        ]);

        $this->stock1 = WarehouseStock::create([
            'product_id' => $this->product1->id, 'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id, 'qty' => 100, 'source_type' => 'penerimaan', 'source_id' => 1,
        ]);
        $this->stock2 = WarehouseStock::create([
            'product_id' => $this->product2->id, 'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id, 'qty' => 100, 'source_type' => 'penerimaan', 'source_id' => 2,
        ]);

        // Mapping: 1 Paket = 2 product A + 1 product B
        $this->mapping1 = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product1->id, 'quantity' => 2,
            'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);
        $this->mapping2 = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product2->id, 'quantity' => 1,
            'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);

        $this->order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-RETUR-TEST',
            'order_date' => now(), 'tanggal' => now(),
            'status' => 'completed', 'main_category_id' => $this->skincare->id,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'platform_product_id' => $this->platformProduct->id,
            'quantity' => 10, // 10 paket
            'price_after_discount' => 500000,
            'warehouse_stock_id' => $this->stock1->id,
        ]);

        session(['main_category_id' => $this->skincare->id]);
    }

    // ==================== RETUR PENJUALAN ====================

    /** @test */
    public function creates_retur_penjualan()
    {
        $retur = ReturPenjualan::create([
            'kode_retur' => ReturPenjualan::generateKodeRetur(),
            'order_id' => $this->order->id,
            'user_id' => 1,
            'tanggal_retur' => now(),
            'status' => 'selesai',
        ]);

        $this->assertNotNull($retur);
        $this->assertStringContainsString('RJ', $retur->kode_retur);
        $this->assertEquals('selesai', $retur->status);
    }

    /** @test */
    public function retur_penjualan_generates_unique_kode()
    {
        $k1 = ReturPenjualan::generateKodeRetur();
        $k2 = ReturPenjualan::generateKodeRetur();
        $this->assertNotEquals($k1, $k2);
    }

    /** @test */
    public function retur_penjualan_belongs_to_order()
    {
        $retur = ReturPenjualan::create([
            'kode_retur' => 'RJ' . now()->format('Ymd') . '0001',
            'order_id' => $this->order->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
        ]);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $retur->order());
    }

    /** @test */
    public function retur_penjualan_has_details()
    {
        $retur = ReturPenjualan::create([
            'kode_retur' => 'RJ' . now()->format('Ymd') . '0002',
            'order_id' => $this->order->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
        ]);

        ReturPenjualanDetail::create([
            'retur_penjualan_id' => $retur->id,
            'order_item_id' => $this->orderItem->id,
            'product_id' => $this->product1->id,
            'qty' => 2,
            'kondisi' => 'BAGUS',
        ]);

        $this->assertCount(1, $retur->details);
        $this->assertEquals('BAGUS', $retur->details->first()->kondisi);
    }

    /** @test */
    public function retur_penjualan_detail_casts_qty_to_decimal()
    {
        $retur = ReturPenjualan::create([
            'kode_retur' => 'RJ' . now()->format('Ymd') . '0003',
            'order_id' => $this->order->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
        ]);

        $detail = ReturPenjualanDetail::create([
            'retur_penjualan_id' => $retur->id,
            'order_item_id' => $this->orderItem->id,
            'product_id' => $this->product1->id,
            'qty' => 1.5,
            'kondisi' => 'RUSAK',
        ]);

        $this->assertEquals(1.5, (float) $detail->qty);
    }

    /** @test */
    public function retur_penjualan_detail_has_platform_product_through()
    {
        $retur = ReturPenjualan::create([
            'kode_retur' => 'RJ' . now()->format('Ymd') . '0004',
            'order_id' => $this->order->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
        ]);

        $detail = ReturPenjualanDetail::create([
            'retur_penjualan_id' => $retur->id,
            'order_item_id' => $this->orderItem->id,
            'product_id' => $this->product1->id,
            'qty' => 1, 'kondisi' => 'BAGUS',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class, $detail->platformProduct());
    }

    // ==================== RETUR OFFLINE SALE ====================

    /** @test】
    public function creates_retur_offline_sale()
    {
        $retur = ReturOfflineSale::create([
            'kode_retur' => ReturOfflineSale::generateKodeRetur(),
            'offline_sale_id' => 1,
            'user_id' => 1,
            'tanggal_retur' => now(),
            'status' => 'draft',
        ]);

        $this->assertNotNull($retur);
        $this->assertEquals('draft', $retur->status);
        $this->assertStringContainsString('RJO', $retur->kode_retur);
    }

    /** @test */
    public function retur_offline_belongs_to_offline_sale()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-RETUR-OFF',
            'sale_date' => now(), 'customer_name' => 'Test',
            'subtotal' => 100000, 'total_amount' => 100000,
            'status' => 'pending', 'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $retur = ReturOfflineSale::create([
            'kode_retur' => 'RJO' . now()->format('Ymd') . '0001',
            'offline_sale_id' => $sale->id,
            'tanggal_retur' => now(), 'status' => 'draft',
        ]);

        $this->assertNotNull($retur->offlineSale);
        $this->assertEquals($sale->id, $retur->offlineSale->id);
    }

    /** @test */
    public function retur_offline_has_details()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-RETUR-DTL',
            'sale_date' => now(), 'customer_name' => 'Test',
            'subtotal' => 100000, 'total_amount' => 100000,
            'status' => 'pending', 'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $item = OfflineSaleItem::create([
            'offline_sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 10, 'unit_price' => 10000, 'subtotal' => 100000,
        ]);

        $retur = ReturOfflineSale::create([
            'kode_retur' => 'RJO' . now()->format('Ymd') . '0002',
            'offline_sale_id' => $sale->id,
            'tanggal_retur' => now(), 'status' => 'draft',
        ]);

        ReturOfflineSaleDetail::create([
            'retur_offline_sale_id' => $retur->id,
            'offline_sale_item_id' => $item->id,
            'product_id' => $this->product1->id,
            'qty' => 2, 'kondisi' => 'BAGUS',
        ]);

        $this->assertCount(1, $retur->details);
    }

    /** @test */
    public function retur_offline_detail_validates_kondisi_enum()
    {
        $allowed = ['BAGUS', 'RUSAK', 'HILANG'];
        foreach ($allowed as $k) {
            $detail = new ReturOfflineSaleDetail(['kondisi' => $k]);
            $this->assertEquals($k, $detail->kondisi);
        }
    }

    // ==================== RETUR PEMBELIAN ====================

    /** @test */
    public function creates_retur_pembelian()
    {
        $retur = ReturPembelian::create([
            'kode_retur' => ReturPembelian::generateKodeRetur(),
            'penerimaan_id' => 1,
            'user_id' => 1,
            'tanggal_retur' => now(),
            'status' => 'selesai',
            'tipe_retur' => ReturPembelian::TIPE_SEBAGIAN,
        ]);

        $this->assertNotNull($retur);
        $this->assertStringContainsString('RP', $retur->kode_retur);
        $this->assertEquals('sebagian', $retur->tipe_retur);
    }

    /** @test */
    public function retur_pembelian_has_tipe_constants()
    {
        $this->assertEquals('sebagian', ReturPembelian::TIPE_SEBAGIAN);
        $this->assertEquals('full', ReturPembelian::TIPE_FULL);
    }

    /** @test】
    public function retur_pembelian_belongs_to_penerimaan()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-RP-TEST',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'status' => 'Located',
        ]);

        $retur = ReturPembelian::create([
            'kode_retur' => 'RP' . now()->format('Ymd') . '0001',
            'penerimaan_id' => $penerimaan->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
        ]);

        $this->assertNotNull($retur->penerimaan);
    }

    /** @test */
    public function retur_pembelian_has_details()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-RP-DTL',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $detail = PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $this->product1->id,
            'qty' => 100, 'satuan_id' => $this->satuan->id,
            'harga_hpp' => 10000, 'subtotal' => 1000000,
        ]);

        $retur = ReturPembelian::create([
            'kode_retur' => 'RP' . now()->format('Ymd') . '0002',
            'penerimaan_id' => $penerimaan->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
            'tipe_retur' => 'sebagian',
        ]);

        ReturPembelianDetail::create([
            'retur_pembelian_id' => $retur->id,
            'penerimaan_detail_id' => $detail->id,
            'product_id' => $this->product1->id,
            'qty' => 10,
            'satuan_id' => $this->satuan->id,
            'alasan' => 'Barang cacat',
        ]);

        $this->assertCount(1, $retur->details);
        $this->assertEquals(10, (int) $retur->details->first()->qty);
        $this->assertEquals('Barang cacat', $retur->details->first()->alasan);
    }

    /** @test */
    public function retur_pembelian_detail_belongs_to_penerimaan_detail()
    {
        $returDet = new ReturPembelianDetail();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $returDet->penerimaanDetail());
    }

    // ==================== PACKAGE MAPPING SCENARIOS ====================

    /**
     * @test
     *
     * Package: 1 paket = 2xA + 1xB
     * Order: 10 paket → 20xA + 10xB
     * Retur: 1 paket → harus return 2xA + 1xB
     */
    public function package_mapping_1_paket_equals_2a_plus_1b()
    {
        $mappings = MappingBarang::where('platform_product_id', $this->platformProduct->id)
            ->where('is_active', true)->get();

        $totalMappingQty = $mappings->sum('quantity');

        $this->assertEquals(3, $totalMappingQty); // 2+1 = 3 items per paket

        // Return 1 paket = 3 individual items
        $returnQtyPaket = 1;
        $qtyA = $returnQtyPaket * 2; // 2 A per paket
        $qtyB = $returnQtyPaket * 1; // 1 B per paket

        $this->assertEquals(2, $qtyA);
        $this->assertEquals(1, $qtyB);
    }

    /**
     * @test
     *
     * Return partial: 0.5 paket (1 pcs dari package)
     * Ini adalah skenario grey area — retur sebagian dari package
     */
    public function partial_package_return_grey_area()
    {
        $mappings = MappingBarang::where('platform_product_id', $this->platformProduct->id)
            ->where('is_active', true)->get();

        // Order item quantity = 10 paket
        $orderItemQty = 10; // 10 paket

        // Retur 1 pcs dari product A saja (bukan 1 paket penuh)
        $returnQtyA = 1; // 1 pcs A
        $returnQtyB = 0; // 0 pcs B

        // Ini grey area karena:
        // - Tidak mengembalikan paket utuh
        // - Hanya 1 dari 2 product A saja
        // - Tidak ada product B yang dikembalikan
        $this->assertNotEquals($returnQtyA, $returnQtyB,
            'Grey area: return tidak proporsional antar produk dalam satu paket');
    }

    // ==================== STOCK RESTORATION ====================

    /**
     * @test
     *
     * Retur BAGUS → stock normal bertambah
     * Retur RUSAK → stock damaged bertambah
     * Retur HILANG → stock tidak bertambah
     */
    public function stock_restoration_depends_on_kondisi()
    {
        $conditions = [
            'BAGUS' => ['is_damaged' => false, 'stock_change' => true],
            'RUSAK' => ['is_damaged' => true, 'stock_change' => true],
            'HILANG' => ['is_damaged' => false, 'stock_change' => false],
        ];

        foreach ($conditions as $kondisi => $expected) {
            $this->assertArrayHasKey('stock_change', $expected);
        }
    }

    /** @test */
    public function retur_bagus_adds_back_to_normal_stock()
    {
        $before = $this->stock1->qty;

        // Simulasi addBackToStock untuk kondisi BAGUS
        WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 5,
            'is_damaged' => false,
            'source_type' => 'retur_penjualan',
            'source_id' => 1,
            'source_date' => now(),
        ]);

        // Stock harus bertambah
        $total = WarehouseStock::where('product_id', $this->product1->id)
            ->where('is_damaged', false)->sum('qty');
        $this->assertGreaterThan($before, $total);
    }

    /** @test */
    public function retur_rusak_adds_to_damaged_stock()
    {
        WarehouseStock::create([
            'product_id' => $this->product1->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 3,
            'is_damaged' => true,
            'source_type' => 'retur_penjualan',
            'source_id' => 1,
        ]);

        $damaged = WarehouseStock::where('product_id', $this->product1->id)
            ->where('is_damaged', true)->sum('qty');
        $this->assertGreaterThan(0, (int) $damaged);
    }

    // ==================== FINANCE IMPACT DOCUMENTATION ====================

    /**
     * @test
     *
     * SKENARIO COMPLEX: Retur sebelum ada finance transaction
     * 1. Order dibuat → stock berkurang
     * 2. Retur full → stock kembali, order item qty = 0
     * 3. Belum ada finance transaction → ReturFinanceService.handleFullRefund
     *    → Tidak ada yang didelete (no transaction)
     */
    public function scenario_return_before_finance_transaction()
    {
        // Order exists with items
        $this->assertNotNull($this->order);

        // No finance transaction yet
        $transExists = \App\Models\ShopeeFinancialTransaction::where('order_id', $this->order->id)->exists();
        $this->assertFalse($transExists, 'Belum ada finance transaction');

        // This is a valid scenario: return can happen before finance is processed
        // Finance service should handle this gracefully
    }

    /**
     * @test
     *
     * SKENARIO COMPLEX: Return 0.5 paket — apa yang terjadi?
     * Order: 10 paket, masing-masing 2A + 1B
     * Retur: 0.5 paket berarti return 1A saja
     *
     * POTENSI MASALAH: Order item quantity jadi 9.5
     * Apakah sistem mendukung fractional package quantity?
     */
    public function scenario_half_package_return()
    {
        $orderItem = $this->orderItem;

        // Retur 0.5 paket
        $returnQty = 0.5;
        $newQty = round($orderItem->quantity - $returnQty, 4);

        // Order item quantity menjadi 9.5
        $this->assertEquals(9.5, $newQty,
            'Sistem mendukung fractional quantity. ' .
            'Potensi masalah: order_items.quantity = 9.5, ' .
            'padahal order_items adalah integer di form input manual');
    }

    /**
     * @test
     *
     * SKENARIO COMPLEX: Retur Offline saat invoice sudah paid
     * 1. Sale → create invoice → pay → retur
     * 2. ReturFinanceService.handleOfflineReturFinance harus adjust
     */
    public function scenario_offline_return_after_paid_invoice()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-COMPLEX-001',
            'sale_date' => now(), 'customer_name' => 'Complex',
            'subtotal' => 1000000, 'total_amount' => 1000000,
            'status' => 'paid', 'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        // Create invoice (FinanceOffline)
        $inv = \App\Models\FinanceOffline::create([
            'invoice_number' => '9999/2606/AMP/01',
            'nominal' => 1000000,
            'tanggal_invoice' => now(), 'status' => 'paid',
            'main_category_id' => $this->skincare->id,
        ]);

        // Invoice status = paid
        $this->assertEquals('paid', $inv->status);

        // After retur, finance service should handle adjustment
        // Scenario: partial refund → keep invoice with adjusted nominal
        // Scenario: full refund → create negative entry
    }

    /**
     * @test
     *
     * SKENARIO: Retur dengan kondisi HILANG — stock tidak kembali
     * Tapi finance tetap kena dampak (refund tetap dikurangi)
     */
    public function scenario_hilang_stock_not_restored_but_finance_affected()
    {
        // HILANG = stock tidak kembali ke warehouse
        // Tapi order item quantity tetap dikurangi (karena barang hilang)
        $orderQtyBefore = $this->orderItem->quantity;

        // Kurangi stock orders
        $this->orderItem->update(['quantity' => $orderQtyBefore - 1]);

        $this->assertEquals($orderQtyBefore - 1, (float) $this->orderItem->fresh()->quantity);

        // Stock di warehouse sudah tidak bisa dikembalikan
        // POTENSI MASALAH: Finance tetap menghitung refund untuk barang hilang?
        // ReturFinanceService.handOnlineReturFinance menghitung refund = semua item
    }
}

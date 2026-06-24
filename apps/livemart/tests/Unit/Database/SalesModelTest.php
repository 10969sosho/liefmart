<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\MappingBarang;
use App\Models\PlatformProduct;
use App\Models\Platform;
use App\Models\BarangKeluar;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;

/**
 * Database Test: Sales Models
 *
 * Menguji model-model utama sales:
 * 1. Order & OrderItem — relasi, casts, global scope
 * 2. OfflineSale & OfflineSaleItem — SJ generation, relasi
 * 3. MappingBarang — versioning, active scoping
 * 4. BarangKeluar — kode generation, stock tracking
 * 5. PlatformProduct — relasi ke platform & mapping
 */
class SalesModelTest extends TestCase
{

    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Satuan $satuan;
    private Product $product;
    private Platform $platform;
    private PlatformProduct $platformProduct;

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
        $this->lokasi = Lokasi::first();
        $this->satuan = Satuan::where('is_active', true)->first();
        $this->product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $this->platform = Platform::first();
        $this->platformProduct = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Test Product Online',
            'variant' => 'Variant A',
        ]);

        session(['main_category_id' => $this->skincare->id]);
    }

    // ==================== ORDER ====================

    /** @test */
    public function order_creates_with_valid_data()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-TEST-001',
            'order_date' => now(),
            'customer_name' => 'Test Customer',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
            'total_amount' => 500000,
        ]);

        $this->assertNotNull($order);
        $this->assertEquals('ORD-TEST-001', $order->order_number);
    }

    /** @test */
    public function order_has_order_items_relationship()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-REL-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'platform_product_id' => $this->platformProduct->id,
            'quantity' => 2,
            'price_after_discount' => 250000,
        ]);

        $this->assertCount(1, $order->orderItems);
        $this->assertEquals(2, $order->orderItems->first()->quantity);
    }

    /** @test */
    public function order_has_total_amount_casts_to_decimal()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-CAST-001',
            'total_amount' => 123456.78,
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals(123456.78, (float) $order->total_amount);
    }

    /** @test */
    public function checks_if_order_is_fully_returned()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-RETURN-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertFalse($order->isFullyReturned()); // No returns yet
    }

    // ==================== ORDER ITEM ====================

    /** @test */
    public function order_item_has_warehouse_stock_relationship()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-ITEM-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $warehouseStock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'platform_product_id' => $this->platformProduct->id,
            'quantity' => 2,
            'warehouse_stock_id' => $warehouseStock->id,
            'price_after_discount' => 250000,
        ]);

        $this->assertNotNull($item->warehouseStock);
        $this->assertEquals(50, $item->warehouseStock->qty);
    }

    /** @test */
    public function order_item_has_barang_keluar_relationship()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-BK-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $stock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'platform_product_id' => $this->platformProduct->id,
            'quantity' => 2,
            'warehouse_stock_id' => $stock->id,
            'price_after_discount' => 250000,
        ]);

        BarangKeluar::create([
            'kode_barang_keluar' => BarangKeluar::generateKode(),
            'order_item_id' => $item->id,
            'warehouse_stock_id' => $stock->id,
            'qty' => 2,
            'tanggal_keluar' => now(),
        ]);

        $this->assertCount(1, $item->barangKeluar);
    }

    // ==================== OFFLINE SALE ====================

    /** @test */
    public function offline_sale_creates_with_valid_data()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-TEST-001',
            'No_PO' => 'PO-OFF-001',
            'sale_date' => now(),
            'customer_name' => 'Walk-in Customer',
            'subtotal' => 500000,
            'tax_amount' => 55000,
            'total_amount' => 555000,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $this->assertNotNull($sale);
        $this->assertEquals('SJ-TEST-001', $sale->surat_jalan_number);
    }

    /** @test */
    public function offline_sale_generates_surat_jalan_number()
    {
        $sjNumber = OfflineSale::generateSuratJalanNumber(
            $this->taxCategory->id,
            $this->skincare->id,
            now()->format('Y-m-d')
        );

        $this->assertStringContainsString('SJ-', $sjNumber);
    }

    /** @test */
    public function offline_sale_has_items_relationship()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-ITEMS-001',
            'sale_date' => now(),
            'customer_name' => 'Test',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        OfflineSaleItem::create([
            'offline_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'subtotal' => 100000,
        ]);

        $this->assertCount(1, $sale->items);
        $this->assertEquals(2, $sale->items->first()->quantity);
    }

    /** @test */
    public function offline_sale_checks_payment_status()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-PAY-001',
            'sale_date' => now(),
            'customer_name' => 'Test',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $this->assertEquals('pending', $sale->getPaymentStatus());
    }

    /** @test */
    public function offline_sale_has_retur_offline_sales_relationship()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-RETUR-001',
            'sale_date' => now(),
            'customer_name' => 'Test',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $sale->returOfflineSales()
        );
    }

    // ==================== OFFLINE SALE ITEM ====================

    /** @test */
    public function offline_sale_item_has_5_level_discount_structure()
    {
        $item = new OfflineSaleItem();

        $this->assertContains('discount_percent_1', $item->getFillable());
        $this->assertContains('discount_amount_1', $item->getFillable());
        $this->assertContains('discount_percent_5', $item->getFillable());
        $this->assertContains('discount_amount_5', $item->getFillable());
    }

    /** @test */
    public function offline_sale_item_casts_to_decimal()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-CAST-001',
            'sale_date' => now(),
            'customer_name' => 'Test',
            'subtotal' => 0,
            'total_amount' => 0,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        $item = OfflineSaleItem::create([
            'offline_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 3.5,
            'unit_price' => 25000.50,
            'subtotal' => 87501.75,
        ]);

        $this->assertEquals(3.5, (float) $item->quantity);
        $this->assertEquals(25000.50, (float) $item->unit_price);
    }

    // ==================== MAPPING BARANG ====================

    /** @test */
    public function mapping_barang_creates_with_active_status()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        $this->assertNotNull($mapping);
        $this->assertTrue($mapping->is_active);
        $this->assertEquals(1, $mapping->version);
    }

    /** @test */
    public function mapping_barang_versioning_creates_new_version()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        $newMapping = $mapping->createNewVersion(
            ['quantity' => 2],
            'Updated quantity'
        );

        $this->assertEquals(2, $newMapping->version);
        $this->assertEquals(2, $newMapping->quantity);
        $this->assertTrue($newMapping->is_active);
        $this->assertEquals($mapping->id, $newMapping->parent_mapping_id);

        // Old mapping should be inactive
        $mapping->refresh();
        $this->assertFalse($mapping->is_active);
    }

    /** @test */
    public function mapping_barang_checks_if_used_in_sales()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        $this->assertFalse($mapping->hasBeenUsedInSales());
    }

    /** @test */
    public function gets_latest_active_mapping()
    {
        MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        $latest = MappingBarang::getLatestActive($this->platformProduct->id);
        $this->assertNotNull($latest);
        $this->assertEquals(1, $latest->version);
    }

    // ==================== BARANG KELUAR ====================

    /** @test */
    public function barang_keluar_generates_unique_kode()
    {
        $kode1 = BarangKeluar::generateKode();
        $kode2 = BarangKeluar::generateKode();

        $this->assertNotEquals($kode1, $kode2);
        $this->assertStringContainsString('BK-', $kode1);
    }

    /** @test */
    public function barang_keluar_has_related_models()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-BK2-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $stock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 10,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'platform_product_id' => $this->platformProduct->id,
            'quantity' => 1,
            'warehouse_stock_id' => $stock->id,
        ]);

        $bk = BarangKeluar::create([
            'kode_barang_keluar' => BarangKeluar::generateKode(),
            'order_item_id' => $item->id,
            'warehouse_stock_id' => $stock->id,
            'qty' => 1,
            'tanggal_keluar' => now(),
        ]);

        $this->assertNotNull($bk->warehouseStock);
        $this->assertNotNull($bk->orderItem);
        $this->assertEquals($stock->id, $bk->warehouseStock->id);
    }

    // ==================== PLATFORM PRODUCT ====================

    /** @test */
    public function platform_product_has_mapping_barang_relationship()
    {
        MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'version' => 1,
            'is_active' => true,
            'valid_from' => now(),
        ]);

        $this->assertCount(1, $this->platformProduct->mappingBarang);
    }

    /** @test */
    public function platform_product_belongs_to_platform()
    {
        $this->assertNotNull($this->platformProduct->platform);
        $this->assertEquals($this->platform->id, $this->platformProduct->platform->id);
    }

    // ==================== EDGE CASES ====================

    /** @test */
    public function handles_order_with_zero_amount()
    {
        $order = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-ZERO-001',
            'total_amount' => 0,
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals(0, (float) $order->total_amount);
    }

    /** @test */
    public function handles_offline_sale_without_main_category()
    {
        $sale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-NULLCAT-001',
            'sale_date' => now(),
            'customer_name' => 'Test',
            'subtotal' => 0,
            'total_amount' => 0,
            'status' => 'pending',
            'main_category_id' => null,
            'created_by' => 1,
        ]);

        // Should be accessible via global scope (orNull)
        $found = OfflineSale::find($sale->id);
        $this->assertNotNull($found);
    }

    /** @test */
    public function documents_offline_sale_item_casts()
    {
        $item = new OfflineSaleItem();
        $casts = $item->getCasts();

        $this->assertEquals('decimal:2', $casts['quantity']);
        $this->assertEquals('decimal:2', $casts['unit_price']);
        $this->assertEquals('decimal:2', $casts['subtotal']);
        $this->assertEquals('decimal:2', $casts['discount_percent_1']);
        $this->assertEquals('decimal:2', $casts['discount_amount_1']);
        $this->assertEquals('array', $casts['discount_mapping']);
    }

    /**
     * @test
     *
     * Potensi masalah: Duplicate order_number tidak ada unique constraint di level database
     */
    public function documents_order_number_uniqueness()
    {
        Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-DUP-001',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        // Duplicate - mungkin berhasil karena tidak ada unique constraint di database
        $duplicate = Order::create([
            'platform_id' => $this->platform->id,
            'order_number' => 'ORD-DUP-001', // Same number
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertNotNull($duplicate);
        // NOTE: Duplicate order_number diperbolehkan di database level.
        // Validasi hanya dilakukan di controller (SalesController saveOnlineTransaction)
    }
}

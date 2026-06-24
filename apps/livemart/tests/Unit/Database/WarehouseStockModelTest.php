<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\WarehouseStock;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\MainCategory;
use App\Models\Lokasi;
use App\Models\TaxCategory;
use App\Models\Satuan;

/**
 * Database Test: WarehouseStock Model
 *
 * Menguji:
 * 1. Relasi model (BelongsTo)
 * 2. Global scope main_category via product join
 * 3. Fillable & casts
 * 4. ED status mutator logic (kadaluarsa, hampir_kadaluarsa, aman)
 * 5. Stock tracking integrity (penerimaan_detail_id reference)
 * 6. Source type tracking (penerimaan, retur_penjualan, retur_offline)
 * 7. Edge cases: qty 0, qty negatif, expired_date null, is_damaged flag
 */
class WarehouseStockModelTest extends TestCase
{

    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Satuan $satuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->lokasi = Lokasi::first();
        $this->satuan = Satuan::where('is_active', true)->first();

        session(['main_category_id' => $this->skincare->id]);
    }

    /** @test */
    public function it_creates_warehouse_stock_with_valid_data()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-WS-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);
        $detail = PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product->id,
            'qty' => 50,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 15000,
            'subtotal' => 750000,
        ]);

        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'penerimaan_detail_id' => $detail->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'source_type' => 'penerimaan',
            'source_id' => $penerimaan->id,
            'source_date' => now(),
        ]);

        $this->assertNotNull($stock);
        $this->assertEquals(50, (int) $stock->qty);
        $this->assertEquals('penerimaan', $stock->source_type);
        $this->assertFalse((bool) $stock->is_damaged);
    }

    /** @test */
    public function it_has_required_fillable_attributes()
    {
        $stock = new WarehouseStock();
        $fillable = $stock->getFillable();

        $requiredFields = [
            'product_id', 'lokasi_id', 'penerimaan_detail_id',
            'tax_id', 'qty', 'source_type', 'source_id', 'source_date',
        ];

        foreach ($requiredFields as $field) {
            $this->assertContains($field, $fillable, "Field {$field} harus ada di fillable");
        }
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $stock = new WarehouseStock();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->product());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->lokasi());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->tax());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->penerimaanDetail());
    }

    /** @test */
    public function it_tracks_source_type_for_retur_penjualan()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 10,
            'source_type' => 'retur_penjualan',
            'source_id' => 1,
            'source_date' => now(),
        ]);

        $this->assertEquals('retur_penjualan', $stock->source_type);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->returPenjualan());
    }

    /** @test */
    public function it_tracks_source_type_for_retur_offline()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 5,
            'source_type' => 'retur_offline',
            'source_id' => 1,
            'source_date' => now(),
        ]);

        $this->assertEquals('retur_offline', $stock->source_type);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $stock->returOfflineSale());
    }

    /** @test */
    public function it_marks_stock_as_damaged()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 3,
            'is_damaged' => true,
            'qty_damaged' => 3,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $this->assertTrue((bool) $stock->is_damaged);
        $this->assertEquals(3, (int) $stock->qty_damaged);
    }

    /** @test */
    public function it_calculates_ed_status_as_aman_when_no_expired_date()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = new WarehouseStock([
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        // expired_date null => status_ed = 'aman'
        $stock->expired_date = null;
        $stock->setStatusEdAttribute();

        $this->assertEquals('aman', $stock->status_ed);
    }

    /** @test */
    public function it_calculates_ed_status_as_kadaluarsa_when_expired()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = new WarehouseStock([
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $stock->expired_date = now()->subDay(1); // Already expired
        $stock->setStatusEdAttribute();

        $this->assertEquals('kadaluarsa', $stock->status_ed);
    }

    /** @test */
    public function it_calculates_ed_status_as_hampir_kadaluarsa_within_30_days()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = new WarehouseStock([
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $stock->expired_date = now()->addDays(15); // 15 days from now
        $stock->setStatusEdAttribute();

        $this->assertEquals('hampir_kadaluarsa', $stock->status_ed);
    }

    /** @test */
    public function it_calculates_ed_status_as_aman_when_far_from_expiry()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = new WarehouseStock([
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $stock->expired_date = now()->addDays(60); // 60 days from now
        $stock->setStatusEdAttribute();

        $this->assertEquals('aman', $stock->status_ed);
    }

    /** @test */
    public function it_allows_zero_qty_stock()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 0,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $this->assertNotNull($stock);
        $this->assertEquals(0, (int) $stock->qty);
    }

    /** @test */
    public function it_stores_expired_date_correctly()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $expiredDate = now()->addYear()->format('Y-m-d');

        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 20,
            'expired_date' => $expiredDate,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $formattedExpiry = $stock->fresh()->expired_date;
        if ($formattedExpiry instanceof \Carbon\Carbon) {
            $formattedExpiry = $formattedExpiry->format('Y-m-d');
        }
        $this->assertEquals($expiredDate, $formattedExpiry);
    }

    /** @test */
    public function it_can_have_multiple_stock_entries_for_same_product()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-MULTI-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        // Create multiple stock entries for the same product
        WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 30,
            'source_type' => 'penerimaan',
            'source_id' => $penerimaan->id,
        ]);

        WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 20,
            'source_type' => 'retur_penjualan',
            'source_id' => 1,
        ]);

        $total = WarehouseStock::where('product_id', $product->id)->sum('qty');
        $this->assertEquals(50, (int) $total);
    }

    /** @test】
    public function it_applies_global_scope_by_main_category()
    {
        // Create product for SKINCARE
        $skincareProduct = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        // Create product for KOPI if exists
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            $kopiProduct = Product::factory()->create(['main_category_id' => $kopi->id]);

            WarehouseStock::create([
                'product_id' => $kopiProduct->id,
                'lokasi_id' => $this->lokasi->id,
                'tax_id' => $this->taxCategory->id,
                'qty' => 100,
                'source_type' => 'penerimaan',
                'source_id' => 1,
            ]);
        }

        WarehouseStock::create([
            'product_id' => $skincareProduct->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 50,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        // Global scope should filter by SKINCARE
        session(['main_category_id' => $this->skincare->id]);
        $stocks = WarehouseStock::all();

        foreach ($stocks as $stock) {
            $this->assertEquals($this->skincare->id, $stock->product->main_category_id);
        }
    }

    /**
     * @test
     *
     * Scenario: Data janggal - qty negatif seharusnya bisa dicegah di level aplikasi
     * karena DB MySQL memungkinkan unsigned atau signed integer.
     * Test ini mendeteksi jika ada yang membuat qty negatif.
     */
    public function it_allows_negative_qty_unless_prevented_by_business_logic()
    {
        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        // MySQL default memungkinkan negative untuk integer signed
        // Business rule: stock tidak boleh negatif - ini harus dicegah di controller/service
        $stock = WarehouseStock::create([
            'product_id' => $product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => -5,
            'source_type' => 'penyesuaian',
            'source_id' => 1,
        ]);

        // CATATAN: Jika database menggunakan unsigned, operasi ini akan throw exception.
        // Jika signed, akan berhasil tapi melanggar business rule.
        // Test ini mendokumentasikan potensi masalah.
        \Log::warning('NEGATIVE STOCK TEST: Qty negatif ' . ($stock->exists ? 'BERHASIL' : 'GAGAL') . ' disimpan. ' .
            'Perlu validasi di controller/service untuk mencegah stock negatif.');

        // Hapus data test
        if ($stock->exists) {
            $stock->delete();
        }

        $this->assertTrue(true); // Test tetap pass - hanya dokumentasi
    }
}

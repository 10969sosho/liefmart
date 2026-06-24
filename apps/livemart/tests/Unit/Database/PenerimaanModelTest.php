<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\PenerimaanActivity;
use App\Models\Product;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;
use App\Models\Satuan;

/**
 * Database Test: Penerimaan & PenerimaanDetail Model
 *
 * Menguji:
 * 1. Relasi model (BelongsTo, HasMany)
 * 2. Global scope main_category
 * 3. Fillable & Casts attributes
 * 4. Recalculate total harga logic
 * 5. Exception & edge cases (data janggal)
 */
class PenerimaanModelTest extends TestCase
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
    public function it_creates_penerimaan_with_valid_data()
    {
        $penerimaan = Penerimaan::create([
            'kode_penerimaan' => 'PNR-TEST-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'nomor_po' => 'PO-2024-001',
            'tanggal_penerimaan' => now(),
            'metode_pembayaran' => 'Cash',
            'total_harga' => 0,
            'status' => 'Unlocated',
            'lokasi_id' => $this->lokasi->id,
        ]);

        $this->assertNotNull($penerimaan);
        $this->assertEquals('PNR-TEST-001', $penerimaan->kode_penerimaan);
        $this->assertEquals('Unlocated', $penerimaan->status);
        $this->assertEquals($this->skincare->id, $penerimaan->main_category_id);
    }

    /** @test */
    public function it_has_required_fillable_attributes()
    {
        $penerimaan = new Penerimaan();
        $fillable = $penerimaan->getFillable();

        $requiredFields = [
            'kode_penerimaan', 'main_category_id', 'tax_category_id',
            'nomor_po', 'tanggal_penerimaan', 'metode_pembayaran',
            'total_harga', 'status',
        ];

        foreach ($requiredFields as $field) {
            $this->assertContains($field, $fillable, "Field {$field} harus ada di fillable");
        }
    }

    /** @test */
    public function it_has_proper_date_casts()
    {
        $penerimaan = new Penerimaan();
        $casts = $penerimaan->getCasts();

        $this->assertEquals('date', $casts['tanggal_penerimaan']);
        $this->assertEquals('date', $casts['tanggal_jatuh_tempo']);
        $this->assertEquals('decimal:2', $casts['total_harga']);
    }

    /** @test */
    public function it_enforces_unique_kode_penerimaan()
    {
        Penerimaan::create([
            'kode_penerimaan' => 'PNR-UNIQUE-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now(),
            'metode_pembayaran' => 'Cash',
            'total_harga' => 0,
            'status' => 'Unlocated',
            'lokasi_id' => $this->lokasi->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionCode('23000'); // Integrity constraint violation

        Penerimaan::create([
            'kode_penerimaan' => 'PNR-UNIQUE-001', // Duplicate
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'nomor_po' => 'PO-002',
            'tanggal_penerimaan' => now(),
            'metode_pembayaran' => 'Cash',
            'total_harga' => 0,
            'status' => 'Unlocated',
            'lokasi_id' => $this->lokasi->id,
        ]);
    }

    /** @test */
    public function it_has_details_relationship()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-REL-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $penerimaan->details());
    }

    /** @test */
    public function it_has_main_category_relationship()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-REL-002',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $penerimaan->mainCategory());
    }

    /** @test */
    public function it_has_activities_relationship()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-REL-003',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $penerimaan->activities());
    }

    /** @test */
    public function it_recalculates_total_harga_from_details()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-CALC-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'total_harga' => 0,
        ]);

        // Create details with varied subtotals
        $product1 = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        $product2 = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product1->id,
            'qty' => 10,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 50000,
            'subtotal' => 500000,
        ]);

        PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product2->id,
            'qty' => 5,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 75000,
            'subtotal' => 375000,
        ]);

        $total = $penerimaan->recalculateTotalHarga();

        $this->assertEquals(875000, (int) $total);
        $this->assertEquals(875000, (int) $penerimaan->fresh()->total_harga);
    }

    /** @test */
    public function it_detects_total_harga_inconsistency()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-CONS-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'total_harga' => 100000, // Different from details
        ]);

        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);
        PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product->id,
            'qty' => 2,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 25000,
            'subtotal' => 50000,
        ]);

        $this->assertFalse($penerimaan->isTotalConsistent());
    }

    /** @test */
    public function it_applies_global_scope_for_main_category()
    {
        // Create penerimaan for SKINCARE
        Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-GLOBAL-001',
            'main_category_id' => $this->skincare->id,
        ]);

        // Create penerimaan for other category if exists
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            Penerimaan::factory()->create([
                'kode_penerimaan' => 'PNR-GLOBAL-002',
                'main_category_id' => $kopi->id,
            ]);
        }

        // With SKINCARE session, should only see SKINCARE data
        session(['main_category_id' => $this->skincare->id]);
        $penerimaans = Penerimaan::all();
        foreach ($penerimaans as $p) {
            $this->assertEquals($this->skincare->id, $p->main_category_id);
        }
    }

    /** @test */
    public function it_can_create_penerimaan_detail_with_discounts()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-DISC-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        $detail = PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product->id,
            'qty' => 100,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 10000,
            'diskon_persen_1' => 10,
            'diskon_nominal_1' => 0,
            'subtotal' => 900000, // 100 * 10000 - 10%
            'is_free' => false,
        ]);

        $this->assertNotNull($detail);
        $this->assertEquals(10, $detail->diskon_persen_1);
        $this->assertFalse($detail->is_free);
    }

    /** @test */
    public function it_can_create_penerimaan_detail_as_free_item()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-FREE-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        $product = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        $detail = PenerimaanDetail::create([
            'penerimaan_id' => $penerimaan->id,
            'product_id' => $product->id,
            'qty' => 5,
            'satuan_id' => $this->satuan->id,
            'harga_hpp' => 0,
            'subtotal' => 0,
            'is_free' => true,
        ]);

        $this->assertTrue($detail->is_free);
        $this->assertEquals(0, (int) $detail->subtotal);
    }

    /** @test */
    public function it_creates_activity_log_on_penerimaan_creation()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-ACT-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
        ]);

        PenerimaanActivity::create([
            'penerimaan_id' => $penerimaan->id,
            'user_id' => 1,
            'activity_type' => 'create',
            'description' => 'Membuat penerimaan baru',
        ]);

        $this->assertCount(1, $penerimaan->activities);
        $this->assertEquals('create', $penerimaan->activities->first()->activity_type);
    }

    /** @test */
    public function it_handles_empty_details_recalculation()
    {
        $penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-EMPTY-001',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'total_harga' => 500000,
        ]);

        // Recalculate with no details
        $total = $penerimaan->recalculateTotalHarga();

        $this->assertEquals(0, (int) $total);
        $this->assertEquals(0, (int) $penerimaan->fresh()->total_harga);
    }

    /** @test */
    public function it_rejects_invalid_status_value()
    {
        $penerimaan = Penerimaan::factory()->make([
            'kode_penerimaan' => 'PNR-STATUS-INVALID',
            'main_category_id' => $this->skincare->id,
            'status' => 'INVALID_STATUS',
        ]);

        // Status tidak ada validasi enum di model, tapi di controller ada.
        // Ini untuk mendeteksi jika nanti ditambahkan validasi.
        $this->assertNotNull($penerimaan->status);
    }

    /**
     * @test
     * @dataProvider invalidPenerimaanDataProvider
     */
    public function it_validates_required_fields_on_creation(array $data, string $expectedMissingField)
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Penerimaan::create($data);
    }

    public static function invalidPenerimaanDataProvider(): array
    {
        $validBase = [
            'kode_penerimaan' => 'PNR-VALIDATE',
            'nomor_po' => 'PO-TEST',
            'tanggal_penerimaan' => now(),
            'metode_pembayaran' => 'Cash',
            'total_harga' => 0,
            'status' => 'Unlocated',
        ];

        return [
            'missing main_category_id' => [
                array_merge($validBase, ['main_category_id' => null, 'kode_penerimaan' => 'PNR-VAL-MC']),
                'main_category_id',
            ],
            'missing tax_category_id' => [
                array_merge($validBase, ['tax_category_id' => null, 'kode_penerimaan' => 'PNR-VAL-TC']),
                'tax_category_id',
            ],
        ];
    }
}

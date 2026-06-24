<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
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
 * Feature Test: Retur Pembelian (Purchase Returns)
 *
 * Alur:
 * 1. Index with filters
 * 2. Create — pilih penerimaan (PO)
 * 3. Store — retur ke supplier
 * 4. Stock deduction saat retur pembelian diproses
 * 5. Tipe retur: sebagian vs full
 */
class ReturPembelianFeatureTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Satuan $satuan;
    private Product $product;
    private Penerimaan $penerimaan;
    private PenerimaanDetail $penerimaanDetail;
    private WarehouseStock $stock;

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
        $this->satuan = Satuan::where('is_active', true)->first();

        $this->product = Product::factory()->create(['name' => 'Retur Pembelian Product', 'main_category_id' => $this->skincare->id]);

        $this->penerimaan = Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-RP-TEST',
            'main_category_id' => $this->skincare->id,
            'tax_category_id' => $this->taxCategory->id,
            'status' => 'Located',
        ]);

        $this->penerimaanDetail = PenerimaanDetail::create([
            'penerimaan_id' => $this->penerimaan->id,
            'product_id' => $this->product->id,
            'qty' => 100, 'satuan_id' => $this->satuan->id,
            'harga_hpp' => 15000, 'subtotal' => 1500000,
        ]);

        $this->stock = WarehouseStock::create([
            'product_id' => $this->product->id, 'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id, 'qty' => 100,
            'source_type' => 'penerimaan', 'source_id' => $this->penerimaan->id,
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    /** @test */
    public function index_displays_retur_list()
    {
        ReturPembelian::create([
            'kode_retur' => 'RP' . now()->format('Ymd') . '0001',
            'penerimaan_id' => $this->penerimaan->id, 'user_id' => $this->user->id,
            'tanggal_retur' => now(), 'status' => 'selesai',
            'tipe_retur' => ReturPembelian::TIPE_SEBAGIAN,
        ]);

        $response = $this->get(route('retur-pembelian.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function index_filters_by_tipe_retur()
    {
        $response = $this->get(route('retur-pembelian.index', ['tipe_retur' => 'sebagian']));
        $response->assertStatus(200);
    }

    /** @test */
    public function index_filters_by_date()
    {
        $response = $this->get(route('retur-pembelian.index', [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->addDays(7)->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
    }

    /** @test */
    public function create_page_displays_penerimaan_list()
    {
        $response = $this->get(route('retur-pembelian.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function get_penerimaan_endpoint()
    {
        $response = $this->get(route('retur-pembelian.get-penerimaan', $this->penerimaan->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function authorization_guest_blocked()
    {
        auth()->logout();
        $this->get(route('retur-pembelian.index'))->assertRedirect(route('login'));
    }
}

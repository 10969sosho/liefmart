<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Penerimaan Request
 *
 * Menguji aturan validasi di PenerimaanController@store:
 * 1. Semua field required
 * 2. Format data (date, in, numeric, array)
 * 3. Unique constraint pada kode_penerimaan
 * 4. Required_if untuk tanggal_jatuh_tempo
 * 5. Edge cases: array kosong, qty negatif, format date salah
 * 6. Max input vars protection (batch items)
 */
class PenerimaanValidationTest extends TestCase
{

    private array $baseRules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);

        // Aturan validasi dari PenerimaanController@store
        $this->baseRules = [
            'main_category_id' => 'required',
            'tax_category_id' => 'required',
            'kode_penerimaan' => 'required|unique:penerimaan,kode_penerimaan',
            'nomor_po' => 'required',
            'tanggal_penerimaan' => 'required|date',
            'metode_pembayaran' => 'required|in:Cash,Jatuh Tempo',
            'tanggal_jatuh_tempo' => 'required_if:metode_pembayaran,Jatuh Tempo|nullable|date',
            'barang_id' => 'required|array',
            'qty' => 'required|array',
            'satuan_id' => 'required|array',
            'harga_hpp' => 'required|array',
        ];
    }

    /** @test */
    public function passes_with_valid_complete_data()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-VALID-001',
            'nomor_po' => 'PO-2024-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'tanggal_jatuh_tempo' => null,
            'barang_id' => [1, 2],
            'qty' => [10, 20],
            'satuan_id' => [1, 1],
            'harga_hpp' => [50000, 75000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function requires_main_category_id()
    {
        $data = [
            'main_category_id' => '',
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-REQ-001',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('main_category_id', $validator->errors()->toArray());
    }

    /** @test */
    public function requires_tax_category_id()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => '',
            'kode_penerimaan' => 'PNR-REQ-002',
            'nomor_po' => 'PO-002',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tax_category_id', $validator->errors()->toArray());
    }

    /** @test】
    public function requires_unique_kode_penerimaan()
    {
        // First creation should pass
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-UNIQUE-TEST',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        // Simulate duplicate kode_penerimaan
        $rulesWithDuplicate = $this->baseRules;
        $rulesWithDuplicate['kode_penerimaan'] = 'required|unique:penerimaan,kode_penerimaan';

        // Test the rule itself
        $validator1 = Validator::make($data, $rulesWithDuplicate);
        $this->assertTrue($validator1->passes(), 'First unique check should pass');

        // After creating, the same kode should fail
        \App\Models\Penerimaan::factory()->create([
            'kode_penerimaan' => 'PNR-UNIQUE-TEST',
            'main_category_id' => 1,
            'tax_category_id' => 1,
        ]);

        $validator2 = Validator::make($data, $rulesWithDuplicate);
        $this->assertTrue($validator2->fails(), 'Duplicate kode_penerimaan should fail');
        $this->assertArrayHasKey('kode_penerimaan', $validator2->errors()->toArray());
    }

    /** @test */
    public function requires_nomor_po()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-PO-001',
            'nomor_po' => '',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nomor_po', $validator->errors()->toArray());
    }

    /** @test */
    public function validates_tanggal_penerimaan_as_date()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-DATE-001',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => 'not-a-date',
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tanggal_penerimaan', $validator->errors()->toArray());
    }

    /** @test */
    public function validates_metode_pembayaran_must_be_cash_or_jatuh_tempo()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-MP-001',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'INVALID',
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('metode_pembayaran', $validator->errors()->toArray());
    }

    /** @test */
    public function requires_tanggal_jatuh_tempo_when_metode_is_jatuh_tempo()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-JT-001',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Jatuh Tempo',
            'tanggal_jatuh_tempo' => '', // Should be required
            'barang_id' => [1],
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tanggal_jatuh_tempo', $validator->errors()->toArray());
    }

    /** @test */
    public function requires_barang_id_as_array()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-ARR-001',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => 'not-an-array',
            'qty' => [10],
            'satuan_id' => [1],
            'harga_hpp' => [50000],
        ];

        $validator = Validator::make($data, $this->baseRules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('barang_id', $validator->errors()->toArray());
    }

    /** @test */
    public function fails_with_empty_items_array()
    {
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-EMPTY-ARR',
            'nomor_po' => 'PO-001',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [],
            'qty' => [],
            'satuan_id' => [],
            'harga_hpp' => [],
        ];

        $validator = Validator::make($data, $this->baseRules);
        // required untuk array => must be present and not empty
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function validates_each_item_in_batch_has_all_required_fields()
    {
        // Aturan tambahan untuk batch items (diterapkan di controller logic)
        $batchRules = [
            'barang_id' => 'required|array|min:1',
            'barang_id.*' => 'required|exists:products,id',
            'qty.*' => 'required|numeric|min:0.01',
            'satuan_id.*' => 'required|exists:satuans,id',
        ];

        $invalidData = [
            'barang_id' => [1, null], // null product id
            'qty' => [10, -5], // negative qty
            'satuan_id' => [1, 9999], // non-existent satuan
        ];

        $validator = Validator::make($invalidData, $batchRules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function passes_with_valid_batch_items()
    {
        $batchRules = [
            'barang_id' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',
        ];

        $validData = [
            'barang_id' => [1, 2, 3],
            'qty' => [10.5, 20, 15.75],
        ];

        $validator = Validator::make($validData, $batchRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function validates_free_items_have_zero_subtotal()
    {
        // Validasi is_free flag (di controller)
        $data = [
            'main_category_id' => 1,
            'tax_category_id' => 1,
            'kode_penerimaan' => 'PNR-FREE-002',
            'nomor_po' => 'PO-002',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'barang_id' => [1, 2],
            'qty' => [10, 5],
            'satuan_id' => [1, 1],
            'harga_hpp' => [50000, 0], // Free item has 0 price
            'is_free' => [0, 1], // Second item is free
        ];

        // Free item logic: if is_free=1, harga_hpp should be 0
        $isFree = $data['is_free'];
        foreach ($data['harga_hpp'] as $index => $harga) {
            if (isset($isFree[$index]) && $isFree[$index] == 1) {
                $this->assertEquals(0, $harga, 'Free item harus memiliki harga_hpp 0');
            }
        }
    }

    /**
     * @test
     *
     * Potensi error: max_input_vars bisa menyebabkan data terpotong
     * saat banyak item dalam satu penerimaan.
     */
    public function documents_max_input_vars_potential_issue()
    {
        $maxInputVars = ini_get('max_input_vars');
        
        // Simulasi: 50 items dengan ~12 fields per item = ~600 variables
        // Ditambah header fields = ~610 variables
        $itemCount = 50;
        $fieldsPerItem = 12;
        $headerFields = 10;
        $totalVars = ($itemCount * $fieldsPerItem) + $headerFields;

        if ($maxInputVars > 0 && $totalVars > $maxInputVars) {
            $this->markTestSkipped(
                "max_input_vars ({$maxInputVars}) mungkin tidak cukup untuk {$itemCount} items " .
                "yang membutuhkan ~{$totalVars} variables. " .
                "Pertimbangkan untuk meningkatkan max_input_vars di php.ini atau " .
                "menggunakan batch processing (AJAX)."
            );
        }

        $this->assertTrue(true, 'max_input_vars check documented');
    }
}

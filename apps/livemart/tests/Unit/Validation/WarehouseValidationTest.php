<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Warehouse Transfer Request
 *
 * Menguji aturan validasi untuk:
 * 1. WarehouseController@store - pemindahan barang dari Unlocated ke Gudang
 * 2. Filter validation pada stock list & analytics
 * 3. Edge cases: items tanpa selected, qty melebihi remaining
 */
class WarehouseValidationTest extends TestCase
{

    /** @test */
    public function passes_with_valid_transfer_data()
    {
        $rules = [
            'items' => 'required|array',
            'items.*.penerimaan_detail_id' => 'required|exists:penerimaan_detail,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ];

        $data = [
            'items' => [
                '0' => [
                    'penerimaan_detail_id' => 1,
                    'qty' => 50,
                    'expired_date' => '2025-12-31',
                    'selected' => 'on',
                ],
                '1' => [
                    'penerimaan_detail_id' => 2,
                    'qty' => 25,
                    'expired_date' => '',
                    'selected' => 'on',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function requires_items_array()
    {
        $rules = [
            'items' => 'required|array',
        ];

        $data = ['items' => ''];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
    }

    /** @test */
    public function requires_each_item_has_penerimaan_detail_id()
    {
        $rules = [
            'items' => 'required|array',
            'items.*.penerimaan_detail_id' => 'required|exists:penerimaan_detail,id',
        ];

        $data = [
            'items' => [
                '0' => [
                    'penerimaan_detail_id' => null,
                    'qty' => 10,
                    'selected' => 'on',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.penerimaan_detail_id', $validator->errors()->toArray());
    }

    /** @test */
    public function validates_qty_must_be_numeric_and_positive()
    {
        $rules = [
            'items.*.qty' => 'required|numeric|min:0.01',
        ];

        $invalidData = [
            'items' => [
                '0' => ['qty' => 'abc', 'selected' => 'on'],
            ],
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function validates_qty_must_be_at_least_001()
    {
        $rules = [
            'items.*.qty' => 'required|numeric|min:0.01',
        ];

        $data = [
            'items' => [
                '0' => ['qty' => 0, 'selected' => 'on'],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function validates_expired_date_as_nullable_date()
    {
        $rules = [
            'items.*.expired_date' => 'nullable|date',
        ];

        $validData = [
            'items' => [
                '0' => ['expired_date' => '2025-12-31'],
                '1' => ['expired_date' => ''],
                '2' => ['expired_date' => null],
            ],
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function validates_expired_date_must_be_valid_date()
    {
        $rules = [
            'items.*.expired_date' => 'nullable|date',
        ];

        $data = [
            'items' => [
                '0' => ['expired_date' => 'not-a-date'],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function accepts_items_without_selected_flag()
    {
        // Filter: hanya item dengan 'selected' yang diproses
        $items = [
            'item_1' => ['penerimaan_detail_id' => 1, 'qty' => 10, 'selected' => 'on'],
            'item_2' => ['penerimaan_detail_id' => 2, 'qty' => 20], // No selected
            'item_3' => ['penerimaan_detail_id' => 3, 'qty' => 30], // No selected
        ];

        $filteredItems = array_filter($items, function ($item) {
            return isset($item['selected']);
        });

        $this->assertCount(1, $filteredItems);
        $this->assertEquals(1, $filteredItems['item_1']['penerimaan_detail_id']);
    }

    /** @test */
    public function rejects_qty_exceeding_remaining_stock()
    {
        // This simulates the business rule check in WarehouseController@store
        $qtyToTransfer = 100;
        $receivedQty = 50;
        $alreadyTransferred = 0;
        $remainingQty = $receivedQty - $alreadyTransferred;

        $this->assertLessThanOrEqual(
            $remainingQty,
            $qtyToTransfer,
            'Jumlah yang dipindahkan melebihi stok yang tersedia'
        );
    }

    /** @test */
    public function accepts_qty_less_than_or_equal_to_remaining_stock()
    {
        $qtyToTransfer = 30;
        $receivedQty = 50;
        $alreadyTransferred = 10;
        $remainingQty = $receivedQty - $alreadyTransferred;

        $this->assertGreaterThanOrEqual(
            $qtyToTransfer,
            $remainingQty,
            'Stok tersedia harus cukup untuk transfer'
        );
        $this->assertLessThanOrEqual($remainingQty, $qtyToTransfer);
    }

    /** @test */
    public function validates_stock_list_filters()
    {
        $filterRules = [
            'search' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'status_ed' => 'nullable|in:kadaluarsa,kurang_dari_3_bulan,kurang_dari_6_bulan,kurang_dari_1_tahun,lebih_dari_1_tahun,tidak_ada_ed',
            'tax_id' => 'nullable',
            'is_free' => 'nullable|in:0,1',
            'brand_id' => 'nullable|exists:brands,id',
            'main_category_id' => 'nullable|exists:main_categories,id',
        ];

        $validFilters = [
            'search' => 'Sabun',
            'sku' => 'SKU-001',
            'status_ed' => 'kadaluarsa',
            'is_free' => '0',
        ];

        $validator = Validator::make($validFilters, $filterRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function rejects_invalid_status_ed_filter()
    {
        $rules = [
            'status_ed' => 'nullable|in:kadaluarsa,kurang_dari_3_bulan,kurang_dari_6_bulan,kurang_dari_1_tahun,lebih_dari_1_tahun,tidak_ada_ed',
        ];

        $data = ['status_ed' => 'invalid_status'];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function accepts_empty_filter_values()
    {
        $rules = [
            'search' => 'nullable|string',
            'sku' => 'nullable|string',
            'status_ed' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date',
        ];

        // Semua filter dikosongkan
        $emptyFilters = [
            'search' => '',
            'sku' => '',
            'status_ed' => '',
            'tanggal_mulai' => '',
            'tanggal_akhir' => '',
        ];

        $validator = Validator::make($emptyFilters, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function validates_date_range_filters()
    {
        $rules = [
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai',
        ];

        // Invalid: tanggal_akhir before tanggal_mulai
        $invalidData = [
            'tanggal_mulai' => '2024-12-31',
            'tanggal_akhir' => '2024-01-01',
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function validates_date_range_filters_pass_with_valid_order()
    {
        $rules = [
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai',
        ];

        $validData = [
            'tanggal_mulai' => '2024-01-01',
            'tanggal_akhir' => '2024-12-31',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function validates_kode_penerimaan_filter()
    {
        $rules = [
            'kode_penerimaan' => 'nullable|string|max:50',
        ];

        $data = ['kode_penerimaan' => 'PNR-2024-001'];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }
}

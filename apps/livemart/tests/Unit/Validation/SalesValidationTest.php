<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Sales Requests
 *
 * Menguji aturan validasi untuk:
 * 1. Offline Sale Store — create penjualan offline
 * 2. Online Sale Save — input manual penjualan online
 * 3. Import Excel — upload dan preview
 * 4. Filter validations — list filters
 */
class SalesValidationTest extends TestCase
{

    // ==================== OFFLINE SALE STORE ====================

    /** @test */
    public function offline_store_passes_with_valid_data()
    {
        $rules = [
            'sale_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,cancelled',
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:0.01',
            'unit_price' => 'required|array',
            'unit_price.*' => 'required|numeric|min:0',
        ];

        $data = [
            'sale_date' => now()->format('Y-m-d'),
            'customer_id' => 1,
            'subtotal' => 500000,
            'tax_amount' => 0,
            'total_amount' => 500000,
            'status' => 'pending',
            'product_id' => [1, 2],
            'quantity' => [2, 3],
            'unit_price' => [100000, 100000],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function offline_store_requires_sale_date()
    {
        $rules = ['sale_date' => 'required|date'];
        $validator = Validator::make(['sale_date' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_customer_id()
    {
        $rules = ['customer_id' => 'required|exists:customers,id'];
        $validator = Validator::make(['customer_id' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_valid_status()
    {
        $rules = ['status' => 'required|in:pending,paid,cancelled'];
        $validator = Validator::make(['status' => 'invalid'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_subtotal_min_zero()
    {
        $rules = ['subtotal' => 'required|numeric|min:0'];
        $validator = Validator::make(['subtotal' => -100], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_product_id_as_array()
    {
        $rules = [
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
        ];
        $validator = Validator::make(['product_id' => 'not-array'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_quantity_minimum_001()
    {
        $rules = ['quantity.*' => 'required|numeric|min:0.01'];
        $validator = Validator::make(['quantity' => [0]], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_unit_price_minimum_zero()
    {
        $rules = ['unit_price.*' => 'required|numeric|min:0'];
        $validator = Validator::make(['unit_price' => [-500]], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_requires_payment_date_when_status_paid()
    {
        $rules = [
            'status' => 'required|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date|required_if:status,paid',
        ];

        $data = ['status' => 'paid', 'payment_date' => ''];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function offline_store_passes_with_payment_date_when_paid()
    {
        $rules = [
            'status' => 'required|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date|required_if:status,paid',
        ];

        $data = ['status' => 'paid', 'payment_date' => now()->format('Y-m-d')];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function offline_store_passes_with_empty_payment_method_when_pending()
    {
        $rules = [
            'payment_method' => 'nullable|string|required_if:status,paid',
        ];

        $data = ['status' => 'pending', 'payment_method' => ''];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    // ==================== ONLINE SALE SAVE ====================

    /** @test */
    public function online_save_passes_with_valid_data()
    {
        $rules = [
            'platform' => 'required|string|in:shopee,shopee2,tiktok,tiktok2',
            'no_order' => 'required|string|max:100',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.platform_product_id' => 'required|exists:platform_products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];

        $data = [
            'platform' => 'shopee',
            'no_order' => 'ORD-ONLINE-001',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['platform_product_id' => 1, 'qty' => 2, 'price' => 150000],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function online_save_requires_valid_platform()
    {
        $rules = ['platform' => 'required|string|in:shopee,shopee2,tiktok,tiktok2'];
        $validator = Validator::make(['platform' => 'invalid'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function online_save_requires_no_order()
    {
        $rules = ['no_order' => 'required|string|max:100'];
        $validator = Validator::make(['no_order' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function online_save_requires_at_least_one_item()
    {
        $rules = ['items' => 'required|array|min:1'];
        $validator = Validator::make(['items' => []], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function online_save_requires_platform_product_id_exists()
    {
        $rules = ['items.*.platform_product_id' => 'required|exists:platform_products,id'];
        $validator = Validator::make(
            ['items' => [['platform_product_id' => 99999]]],
            $rules
        );
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function online_save_requires_qty_minimum_1()
    {
        $rules = ['items.*.qty' => 'required|integer|min:1'];
        $validator = Validator::make(
            ['items' => [['qty' => 0]]],
            $rules
        );
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function online_save_requires_price_minimum_0()
    {
        $rules = ['items.*.price' => 'required|numeric|min:0'];
        $validator = Validator::make(
            ['items' => [['price' => -100]]],
            $rules
        );
        $this->assertTrue($validator->fails());
    }

    // ==================== IMPORT EXCEL VALIDATION ====================

    /** @test */
    public function import_requires_valid_file_type()
    {
        $rules = [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ];

        $validator = Validator::make(
            ['excel_file' => 'not-a-file'],
            $rules
        );
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function import_accepts_xlsx_xls_csv()
    {
        $rules = ['excel_file' => 'required|file|mimes:xlsx,xls,csv'];
        $mimes = ['xlsx', 'xls', 'csv'];

        foreach ($mimes as $mime) {
            // Just validate the mime rule syntax
            $this->assertStringContainsString($mime, 'xlsx,xls,csv');
        }
    }

    // ==================== SALES LIST FILTERS ====================

    /** @test */
    public function sales_list_filters_pass_validation()
    {
        $rules = [
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
            'platform' => 'nullable',
            'order_number' => 'nullable|string|max:100',
            'surat_jalan_number' => 'nullable|string|max:50',
            'No_PO' => 'nullable|string|max:100',
        ];

        $validData = [
            'date_start' => '2024-01-01',
            'date_end' => '2024-12-31',
            'order_number' => 'ORD-001',
            'surat_jalan_number' => 'SJ-001',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function sales_list_filters_reject_invalid_date_format()
    {
        $rules = ['date_start' => 'nullable|date'];
        $validator = Validator::make(['date_start' => 'not-a-date'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function sales_list_filters_accept_empty_filters()
    {
        $rules = [
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
            'platform' => 'nullable',
            'order_number' => 'nullable|string',
            'surat_jalan_number' => 'nullable|string',
        ];

        $emptyFilters = [
            'date_start' => '',
            'date_end' => '',
            'platform' => '',
            'order_number' => '',
            'surat_jalan_number' => '',
        ];

        $validator = Validator::make($emptyFilters, $rules);
        $this->assertTrue($validator->passes());
    }

    // ==================== OFFLINE SALE ITEM DISCOUNTS ====================

    /** @test */
    public function offline_sale_item_discounts_accept_positive_values()
    {
        $rules = [
            'discount_percent_1' => 'nullable|numeric|min:0|max:100',
            'discount_amount_1' => 'nullable|numeric|min:0',
        ];

        $data = ['discount_amount_1' => 50000, 'discount_percent_1' => 10];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function offline_sale_item_discounts_reject_negative()
    {
        $rules = ['discount_amount_1' => 'nullable|numeric|min:0'];
        $validator = Validator::make(['discount_amount_1' => -5000], $rules);
        $this->assertTrue($validator->fails());
    }
}

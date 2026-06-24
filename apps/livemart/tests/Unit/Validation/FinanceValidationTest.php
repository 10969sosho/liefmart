<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Finance Requests
 *
 * Menguji validasi untuk:
 * 1. Finance Offline — generate invoice, pay, adjust, print limit
 * 2. Finance Online — import filter params, adjustment
 * 3. Arus Kas — filter validations
 */
class FinanceValidationTest extends TestCase
{

    // ==================== FINANCE OFFLINE PAY ====================

    /** @test */
    public function pay_requires_payment_date_and_amount()
    {
        $rules = [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
        ];

        $validator = Validator::make([
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_method' => 'Transfer Bank',
        ], $rules);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function pay_fails_without_payment_date()
    {
        $rules = ['payment_date' => 'required|date'];
        $validator = Validator::make(['payment_date' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function pay_requires_amount_minimum()
    {
        $rules = ['amount' => 'required|numeric|min:0.01'];
        $validator = Validator::make(['amount' => 0], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function pay_rejects_negative_amount()
    {
        $rules = ['amount' => 'required|numeric|min:0.01'];
        $validator = Validator::make(['amount' => -100000], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== FINANCE OFFLINE ADJUSTMENT ====================

    /** @test */
    public function adjust_requires_nominal_and_reason()
    {
        $rules = [
            'nominal' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ];

        $validator = Validator::make([
            'nominal' => 50000,
            'reason' => 'Koreksi kesalahan input',
        ], $rules);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function adjust_allows_negative_nominal()
    {
        $rules = ['nominal' => 'required|numeric'];
        $validator = Validator::make(['nominal' => -50000], $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function adjust_requires_reason()
    {
        $rules = ['reason' => 'required|string|max:500'];
        $validator = Validator::make(['reason' => ''], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== FINANCE IMPORT FILTERS ====================

    /** @test */
    public function finance_online_filters_pass_validation()
    {
        $rules = [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'from_order_date' => 'nullable|date',
            'to_order_date' => 'nullable|date',
            'order_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:50',
            'min_nominal' => 'nullable|numeric|min:0',
            'max_nominal' => 'nullable|numeric|min:0',
            'outstanding_status' => 'nullable|in:0,1',
            'payment_date' => 'nullable|date',
        ];

        $validData = [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'from_order_date' => '2024-01-01',
            'to_order_date' => '2024-12-31',
            'order_number' => 'ORD-001',
            'outstanding_status' => '0',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function finance_online_filters_accept_empty()
    {
        $rules = [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'order_number' => 'nullable|string',
            'invoice_number' => 'nullable|string',
        ];

        $empty = ['from_date' => '', 'to_date' => '', 'order_number' => '', 'invoice_number' => ''];
        $validator = Validator::make($empty, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function finance_online_filters_reject_invalid_outstanding_status()
    {
        $rules = ['outstanding_status' => 'nullable|in:0,1'];
        $validator = Validator::make(['outstanding_status' => 'invalid'], $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function finance_online_filters_reject_negative_min_nominal()
    {
        $rules = ['min_nominal' => 'nullable|numeric|min:0'];
        $validator = Validator::make(['min_nominal' => -100], $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== TO_DATE AFTER FROM_DATE ====================

    /** @test */
    public function finance_date_range_must_be_valid()
    {
        $rules = [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];

        $invalid = ['from_date' => '2024-12-31', 'to_date' => '2024-01-01'];
        $validator = Validator::make($invalid, $rules);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function finance_date_range_passes_with_valid_order()
    {
        $rules = [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ];

        $valid = ['from_date' => '2024-01-01', 'to_date' => '2024-12-31'];
        $validator = Validator::make($valid, $rules);
        $this->assertTrue($validator->passes());
    }

    // ==================== INVOICE NUMBER FORMAT ====================

    /** @test */
    public function validates_invoice_number_format()
    {
        $pattern = '/^\d{4}\/\d{4}\/[A-Z0-9-]+\/\d{2}$/';

        $validNumbers = [
            '0001/2606/AMP/01',
            '0123/2508/AMP-KOS/02',
            '9999/2606/AMP-OL/03',
        ];

        foreach ($validNumbers as $num) {
            $this->assertMatchesRegularExpression($pattern, $num, "Format {$num} harus valid");
        }
    }

    /** @test */
    public function rejects_invalid_invoice_number_format()
    {
        $pattern = '/^\d{4}\/\d{4}\/[A-Z0-9-]+\/\d{2}$/';

        $invalidNumbers = [
            'INV-001',
            '123/2606/AMP/01', // 3 digit counter
            '0001/260/AMP/01', // 3 digit yearmonth
            '0001/2606/AMP/1', // 1 digit taxcode
            '',
        ];

        foreach ($invalidNumbers as $num) {
            $this->assertDoesNotMatchRegularExpression($pattern, $num, "Format {$num} harus ditolak");
        }
    }

    // ==================== PRINT LIMIT VALIDATION ====================

    /** @test */
    public function print_limit_validation()
    {
        $rules = [
            'finance_offline_id' => 'required|exists:finance_offlines,id',
        ];

        $data = ['finance_offline_id' => 1];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());

        $invalid = ['finance_offline_id' => 99999];
        $validator = Validator::make($invalid, $rules);
        $this->assertTrue($validator->fails());
    }

    // ==================== LOCK/UNLOCK ====================

    /** @test */
    public function lock_accepts_boolean()
    {
        $rules = [
            'is_locked' => 'required|boolean',
            'lock_reason' => 'nullable|string|max:500',
        ];

        $validator = Validator::make(['is_locked' => true, 'lock_reason' => 'Audit'], $rules);
        $this->assertTrue($validator->passes());

        $validator2 = Validator::make(['is_locked' => 'not-boolean'], $rules);
        $this->assertTrue($validator2->fails());
    }
}

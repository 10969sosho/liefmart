<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\FinanceOffline;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\InvoiceSequence;
use App\Models\InvoicePayment;
use App\Models\BarangKeluar;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\Order;
use App\Models\Platform;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Lokasi;

/**
 * Database Test: Finance Models
 *
 * Menguji:
 * 1. FinanceOffline — invoice number generation, casts, relationships, print tracking
 * 2. ShopeeFinancialTransaction — 12-level discounts, nominal_fix calculation, invoice generation
 * 3. TiktokFinancialTransaction — same structure as Shopee
 * 4. InvoiceSequence — counter, format, cross-table dedup
 * 5. InvoicePayment — partial payment tracking
 * 6. Retur -> finance impact via outstanding calculation
 */
class FinanceModelTest extends TestCase
{

    private MainCategory $skincare;
    private TaxCategory $taxCategory;
    private Lokasi $lokasi;
    private Product $product;
    private OfflineSale $offlineSale;
    private WarehouseStock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\TaxCategorySeeder::class);
        $this->seed(\Database\Seeders\LokasiSeeder::class);
        $this->seed(\Database\Seeders\SatuanSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSequenceSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->taxCategory = TaxCategory::where('main_category_id', $this->skincare->id)->first();
        $this->lokasi = Lokasi::first();
        $this->product = Product::factory()->create(['main_category_id' => $this->skincare->id]);

        $this->stock = WarehouseStock::create([
            'product_id' => $this->product->id,
            'lokasi_id' => $this->lokasi->id,
            'tax_id' => $this->taxCategory->id,
            'qty' => 100,
            'source_type' => 'penerimaan',
            'source_id' => 1,
        ]);

        $this->offlineSale = OfflineSale::create([
            'surat_jalan_number' => 'SJ-FIN-TEST',
            'sale_date' => now(),
            'customer_name' => 'Finance Test',
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'status' => 'pending',
            'main_category_id' => $this->skincare->id,
            'created_by' => 1,
        ]);

        session(['main_category_id' => $this->skincare->id]);
    }

    // ==================== FINANCE OFFLINE ====================

    /** @test */
    public function creates_finance_offline_with_valid_data()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0001/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertNotNull($inv);
        $this->assertEquals('0001/2606/AMP/01', $inv->invoice_number);
        $this->assertEquals('unpaid', $inv->status);
        $this->assertEquals(0, $inv->print_count);
    }

    /** @test */
    public function finance_offline_generates_invoice_number()
    {
        $invNumber = FinanceOffline::generateInvoiceNumber($this->taxCategory->id, now()->format('Y-m-d'));
        $this->assertNotNull($invNumber);
        $this->assertStringContainsString('/', $invNumber);
    }

    /** @test */
    public function finance_offline_has_barang_keluar_items_relationship()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0002/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        $item = OfflineSaleItem::create([
            'offline_sale_id' => $this->offlineSale->id,
            'product_id' => $this->product->id,
            'warehouse_stock_id' => $this->stock->id,
            'quantity' => 2,
            'unit_price' => 250000,
            'subtotal' => 500000,
        ]);

        BarangKeluar::create([
            'kode_barang_keluar' => 'BK-FIN-001',
            'offline_sale_item_id' => $item->id,
            'warehouse_stock_id' => $this->stock->id,
            'qty' => 2,
            'tanggal_keluar' => now(),
            'finance_offline_id' => $inv->id,
        ]);

        $this->assertCount(1, $inv->barangKeluarItems);
    }

    /** @test */
    public function finance_offline_tracks_print_count()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0003/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals(0, $inv->print_count);
        $this->assertFalse($inv->reprint_requested);
        $this->assertFalse($inv->reprint_approved);
    }

    /** @test】
    public function finance_offline_requests_reprint()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0004/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
            'print_count' => 1,
        ]);

        $result = $inv->requestReprint();
        $this->assertTrue($result);
        $this->assertTrue($inv->fresh()->reprint_requested);
    }

    /** @test */
    public function finance_offline_has_payments_relationship()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0005/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        InvoicePayment::create([
            'finance_offline_id' => $inv->id,
            'payment_date' => now(),
            'amount' => 500000,
        ]);

        $this->assertCount(1, $inv->payments);
        $this->assertEquals(500000, (float) $inv->payments->first()->amount);
    }

    /** @test */
    public function finance_offline_scope_unpaid()
    {
        FinanceOffline::create([
            'invoice_number' => '0006/2606/AMP/01',
            'nominal' => 100000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);
        FinanceOffline::create([
            'invoice_number' => '0007/2606/AMP/01',
            'nominal' => 200000,
            'tanggal_invoice' => now(),
            'status' => 'paid',
            'main_category_id' => $this->skincare->id,
        ]);

        $unpaid = FinanceOffline::unpaid()->get();
        $this->assertCount(1, $unpaid);
        $this->assertEquals('unpaid', $unpaid->first()->status);
    }

    /** @test */
    public function finance_offline_scope_paid()
    {
        FinanceOffline::create([
            'invoice_number' => '0008/2606/AMP/01',
            'nominal' => 100000,
            'tanggal_invoice' => now(),
            'status' => 'paid',
            'main_category_id' => $this->skincare->id,
        ]);

        $paid = FinanceOffline::paid()->get();
        $this->assertCount(1, $paid);
    }

    // ==================== SHOPEE FINANCIAL TRANSACTION ====================

    /** @test */
    public function creates_shopee_financial_transaction()
    {
        $trans = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-SHOPEE-FIN',
            'nominal_harga' => 150000,
            'nominal_fix' => 150000,
            'saldo_masuk' => 150000,
            'outstanding' => 0,
            'qty' => 2,
            'tanggal_order' => now(),
            'hari_order' => 'Monday',
        ]);

        $this->assertNotNull($trans);
        $this->assertEquals(150000, (float) $trans->nominal_harga);
    }

    /** @test */
    public function shopee_financial_transaction_has_12_discount_levels()
    {
        $trans = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-SHOPEE-DISC',
            'nominal_harga' => 500000,
            'nominal_diskon1' => -50000,
            'nominal_diskon2' => -25000,
            'nominal_diskon3' => -10000,
            'nominal_fix' => 415000,
            'saldo_masuk' => 415000,
            'outstanding' => 0,
            'qty' => 1,
            'tanggal_order' => now(),
        ]);

        $this->assertNotNull($trans);
        $this->assertEquals(-50000, (float) $trans->nominal_diskon1);
        $this->assertEquals(-25000, (float) $trans->nominal_diskon2);
    }

    /** @test */
    public function shopee_calculates_nominal_fix()
    {
        $trans = new ShopeeFinancialTransaction([
            'no_order' => 'ORD-SHOPEE-CALC',
            'nominal_harga' => 500000,
            'nominal_diskon1' => -50000,
            'nominal_diskon2' => -25000,
            'adjustment' => 10000,
        ]);

        $trans->calculateNominalFix();

        // 500000 + (-50000) + (-25000) + 10000 = 435000
        $this->assertEquals(435000, (float) $trans->nominal_fix);
    }

    /** @test */
    public function shopee_has_order_relationship()
    {
        $platform = Platform::first();
        $order = Order::create([
            'platform_id' => $platform->id,
            'order_number' => 'ORD-SHOPEE-REL',
            'status' => 'completed',
            'main_category_id' => $this->skincare->id,
        ]);

        $trans = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-SHOPEE-REL',
            'order_id' => $order->id,
            'nominal_harga' => 150000,
            'nominal_fix' => 150000,
            'qty' => 1,
            'tanggal_order' => now(),
        ]);

        $this->assertNotNull($trans->order);
        $this->assertEquals($order->id, $trans->order->id);
    }

    /** @test */
    public function shopee_tracks_lock_status()
    {
        $trans = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-SHOPEE-LOCK',
            'nominal_harga' => 100000,
            'nominal_fix' => 100000,
            'qty' => 1,
            'tanggal_order' => now(),
            'is_locked' => true,
            'locked_by' => 1,
            'locked_at' => now(),
        ]);

        $this->assertTrue($trans->is_locked);
        $this->assertNotNull($trans->locked_at);
    }

    // ==================== TIKTOK FINANCIAL TRANSACTION ====================

    /** @test */
    public function creates_tiktok_financial_transaction()
    {
        $trans = TiktokFinancialTransaction::create([
            'no_order' => 'ORD-TIKTOK-FIN',
            'nominal_harga' => 200000,
            'nominal_fix' => 200000,
            'saldo_masuk' => 200000,
            'outstanding' => 0,
            'qty' => 3,
            'tanggal_order' => now(),
        ]);

        $this->assertNotNull($trans);
        $this->assertEquals(200000, (float) $trans->nominal_harga);
    }

    // ==================== INVOICE SEQUENCE ====================

    /** @test */
    public function invoice_sequence_generates_cross_table_unique_number()
    {
        $invNum1 = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            now()->format('Y-m-d')
        );

        $invNum2 = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            now()->format('Y-m-d')
        );

        $this->assertNotNull($invNum1['invoice_number']);
        $this->assertNotNull($invNum2['invoice_number']);
        $this->assertNotEquals($invNum1['invoice_number'], $invNum2['invoice_number'], 'Invoice numbers must be unique');
    }

    /** @test */
    public function invoice_sequence_respects_different_categories()
    {
        $kopi = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_KOPI,
            InvoiceSequence::SALES_OFFLINE,
            InvoiceSequence::TAX_NON_PKP,
            now()->format('Y-m-d')
        );

        $skincare = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_OFFLINE,
            InvoiceSequence::TAX_NON_PKP,
            now()->format('Y-m-d')
        );

        $this->assertNotNull($kopi['invoice_number']);
        $this->assertNotNull($skincare['invoice_number']);
    }

    /** @test */
    public function invoice_sequence_throws_exception_without_order_date()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tanggal order wajib ada');

        InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            null
        );
    }

    /** @test */
    public function invoice_sequence_counter_increments()
    {
        $first = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_OFFLINE,
            InvoiceSequence::TAX_NON_PKP,
            now()->format('Y-m-d')
        );

        $second = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_OFFLINE,
            InvoiceSequence::TAX_NON_PKP,
            now()->format('Y-m-d')
        );

        $this->assertEquals($first['counter'] + 1, $second['counter']);
    }

    /** @test */
    public function invoice_sequence_different_months_have_separate_counters()
    {
        $lastMonth = now()->subMonth()->format('Y-m-d');
        $thisMonth = now()->format('Y-m-d');

        $lastMonthInv = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            $lastMonth
        );

        $thisMonthInv = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            $thisMonth
        );

        // Different months should start from 1
        $this->assertEquals(1, $lastMonthInv['counter']);
        $this->assertEquals(1, $thisMonthInv['counter']);
    }

    // ==================== INVOICE PAYMENT ====================

    /** @test */
    public function invoice_payment_tracks_partial_payments()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0009/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        InvoicePayment::create([
            'finance_offline_id' => $inv->id,
            'payment_date' => now(),
            'amount' => 300000,
        ]);

        $inv->refresh();
        $this->assertCount(1, $inv->payments);
        $this->assertEquals(300000, (float) $inv->payments->first()->amount);
    }

    /** @test */
    public function invoice_payment_allows_multiple_payments()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0010/2606/AMP/01',
            'nominal' => 1000000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        InvoicePayment::create([
            'finance_offline_id' => $inv->id,
            'payment_date' => now(),
            'amount' => 500000,
        ]);
        InvoicePayment::create([
            'finance_offline_id' => $inv->id,
            'payment_date' => now(),
            'amount' => 500000,
        ]);

        $this->assertCount(2, $inv->payments);
    }

    // ==================== EDGE CASES ====================

    /** @test */
    public function finance_offline_handles_zero_nominal()
    {
        $inv = FinanceOffline::create([
            'invoice_number' => '0011/2606/AMP/01',
            'nominal' => 0,
            'tanggal_invoice' => now(),
            'status' => 'paid',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals(0, (float) $inv->nominal);
    }

    /** @test */
    public function shopee_transaction_handles_adjustment()
    {
        $trans = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-ADJUST',
            'nominal_harga' => 300000,
            'adjustment' => -50000,
            'adjustment_description' => 'Koreksi biaya admin',
            'nominal_fix' => 250000,
            'qty' => 1,
            'tanggal_order' => now(),
        ]);

        $this->assertEquals(-50000, (float) $trans->adjustment);
        $this->assertEquals('Koreksi biaya admin', $trans->adjustment_description);
    }

    /** @test */
    public function documents_shared_invoice_sequence_across_platforms()
    {
        // Create Shopee invoice
        $shopeeInv = ShopeeFinancialTransaction::create([
            'no_order' => 'ORD-SEQ-SHOPEE',
            'no_invoice' => '9000/2606/AMP-OL/01',
            'nominal_harga' => 100000,
            'nominal_fix' => 100000,
            'qty' => 1,
            'tanggal_order' => now(),
        ]);

        // InvoiceSequence should detect existing "9000"
        // and generate "9001" instead
        $nextInv = InvoiceSequence::getNextInvoiceNumber(
            InvoiceSequence::CATEGORY_SKINCARE,
            InvoiceSequence::SALES_ONLINE,
            InvoiceSequence::TAX_PKP,
            now()->format('Y-m-d')
        );

        // The counter should be > 9000 because Shopee already used those numbers
        $this->assertGreaterThan(9000, $nextInv['counter'],
            'InvoiceSequence harus detect existing numbers from Shopee table');
    }

    /**
     * @test
     *
     * Potensi masalah: FinanceOffline calcNominalFix tidak ada method seperti Shopee
     */
    public function documents_finance_offline_nominal_calculation()
    {
        // FinanceOffline tidak memiliki method calculateNominalFix()
        // Nominal di-set manual saat create invoice
        $inv = FinanceOffline::create([
            'invoice_number' => '0012/2606/AMP/01',
            'nominal' => 500000,
            'tanggal_invoice' => now(),
            'status' => 'unpaid',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals(500000, (float) $inv->nominal);
    }
}

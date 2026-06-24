<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Finance Offline & Online
 *
 * Menguji tampilan finance dari browser:
 * 1. Halaman finance offline — grouped items, filters
 * 2. Halaman invoice list
 * 3. Halaman finance shopee — transactions, filters
 * 4. Halaman finance tiktok
 * 5. Generate invoice
 * 6. Print invoice
 */
class FinanceBrowserTest extends DuskTestCase
{
    /** @test */
    public function finance_offline_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/offline')
                ->assertSee('Keuangan')
                ->assertPresent('input[name="date_start"]')
                ->assertPresent('input[name="date_end"]');
        });
    }

    /** @test */
    public function finance_invoice_list_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/offline/invoices')
                ->assertSee('Invoice')
                ->assertPresent('input[name="invoice_number"]');
        });
    }

    /** @test */
    public function finance_shopee_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/shopee')
                ->assertSee('Shopee')
                ->assertPresent('input[name="order_number"]');
        });
    }

    /** @test */
    public function finance_shopee_has_filter_controls()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/shopee')
                ->assertPresent('input[name="from_date"]')
                ->assertPresent('input[name="to_date"]')
                ->assertPresent('select[name="outstanding_status"]');
        });
    }

    /** @test */
    public function finance_tiktok_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/tiktok')
                ->assertSee('TikTok');
        });
    }

    /** @test */
    public function offline_invoice_print_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/offline/invoices')
                ->assertPresent('a[href*="/print-invoice/"]');
        });
    }

    /** @test */
    public function finance_offline_has_customer_filter()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/offline')
                ->assertPresent('input[name="customer"]');
        });
    }

    /** @test */
    public function finance_offline_export_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/offline')
                ->assertPresent('a[href*="/export"]');
        });
    }
}

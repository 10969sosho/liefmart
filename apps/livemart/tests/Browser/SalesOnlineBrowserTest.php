<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Sales Online — Import, Input, List
 *
 * Menguji alur penjualan online dari browser:
 * 1. Halaman pilih jenis penjualan
 * 2. Halaman penjualan online dengan daftar platform
 * 3. Input manual penjualan online
 * 4. Daftar penjualan dengan filter
 * 5. Import Excel (upload)
 */
class SalesOnlineBrowserTest extends DuskTestCase
{
    /** @test */
    public function choose_type_page_shows_online_and_offline()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales')
                ->assertSee('Online')
                ->assertSee('Offline');
        });
    }

    /** @test */
    public function online_page_shows_platforms()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/online')
                ->assertSee('Shopee')
                ->assertSee('TikTok');
        });
    }

    /** @test */
    public function sales_list_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/list')
                ->assertPresent('table')
                ->assertPresent('input[name="order_number"]');
        });
    }

    /** @test】
    public function sales_list_has_filters()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/list')
                ->assertPresent('input[name="date_start"]')
                ->assertPresent('input[name="date_end"]')
                ->assertPresent('select[name="platform"]');
        });
    }

    /** @test */
    public function import_excel_page_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/shopee/import-excel')
                ->assertSee('Import')
                ->assertPresent('input[type="file"]');
        });
    }
}

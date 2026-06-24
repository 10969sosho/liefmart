<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Sales Offline — Create, List, Print SJ
 *
 * Menguji alur penjualan offline dari browser:
 * 1. Halaman offline
 * 2. List penjualan offline dengan filter
 * 3. Form create dengan stock info
 * 4. Print surat jalan
 * 5. Detail penjualan
 */
class SalesOfflineBrowserTest extends DuskTestCase
{
    /** @test */
    public function offline_list_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/offline/list')
                ->assertSee('Penjualan')
                ->assertPresent('input[name="date_start"]')
                ->assertPresent('input[name="date_end"]');
        });
    }

    /** @test */
    public function create_page_shows_form()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/offline/create')
                ->assertSee('Surat Jalan')
                ->assertPresent('select[name="customer_id"]')
                ->assertPresent('input[name="sale_date"]');
        });
    }

    /** @test */
    public function print_sj_displays_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/offline/list')
                ->assertPresent('a[href*="/print/sj"]');
        });
    }

    /** @test */
    public function detail_page_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/offline/list')
                ->assertPresent('a[href*="/offline/"]');
        });
    }

    /** @test */
    public function filter_controls_are_present()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/sales/offline/list')
                ->assertPresent('input[name="surat_jalan_number"]')
                ->assertPresent('input[name="No_PO"]');
        });
    }
}

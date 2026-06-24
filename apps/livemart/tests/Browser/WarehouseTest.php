<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Warehouse & Stock Flow
 *
 * Menguji alur warehouse dari perspektif browser:
 * 1. Halaman warehouse index (unlocated items)
 * 2. Filter unlocated items
 * 3. Transfer barang dari Unlocated ke Gudang A
 * 4. Stock list dengan filter
 * 5. Stock analytics page
 * 6. Damaged items page
 * 7. Export stock
 *
 * PRASYARAT:
 * - Database sudah di-seed dengan data test
 * - Ada penerimaan dengan status Unlocated
 * - User superadmin tersedia
 */
class WarehouseTest extends DuskTestCase
{
    /** @test */
    public function warehouse_index_displays_unlocated_items()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse')
                ->assertSee('Pemindahan Barang')
                ->assertSee('Unlocated');
        });
    }

    /** @test */
    public function warehouse_create_page_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse')
                ->assertPresent('a[href*="/warehouse/create"]');
        });
    }

    /** @test */
    public function stock_list_displays_data()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/list')
                ->assertSee('Stock')
                ->assertPresent('input[name="search"]')
                ->assertPresent('select[name="status_ed"]');
        });
    }

    /** @test */
    public function stock_list_can_filter_by_search()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/list')
                ->type('search', 'Sabun')
                ->press('Cari')
                ->assertPathIs('/warehouse/stock/list');
        });
    }

    /** @test */
    public function stock_list_can_filter_by_expiry_status()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/list')
                ->select('status_ed', 'kadaluarsa')
                ->press('Cari')
                ->assertPathIs('/warehouse/stock/list');
        });
    }

    /** @test */
    public function stock_analytics_displays_consolidated_view()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/analytics')
                ->assertSee('Analytics')
                ->assertSee('Total Item')
                ->assertSee('Total Quantity');
        });
    }

    /** @test */
    public function stock_analytics_has_filter_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/analytics')
                ->assertPresent('input[name="search"]')
                ->assertPresent('select[name="status_ed"]')
                ->assertPresent('select[name="tax_id"]');
        });
    }

    /** @test */
    public function damaged_items_page_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/damaged')
                ->assertSee('Rusak');
        });
    }

    /** @test */
    public function stock_export_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/stock/list')
                ->assertPresent('a[href*="/warehouse/stock/export"]');
        });
    }

    /** @test】
    public function transfer_from_unlocated_to_gudang()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse/create')
                ->assertSee('Pindahkan ke Gudang A')
                ->assertPresent('input[type="number"][name*="qty"]');
        });
    }

    /** @test */
    public function navigates_between_warehouse_pages()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/warehouse')
                ->assertSee('Unlocated');

            $browser->visit('/warehouse/stock/list')
                ->assertDontSee('Unlocated')
                ->assertSee('Stock');

            $browser->visit('/warehouse/stock/analytics')
                ->assertSee('Analytics');
        });
    }
}

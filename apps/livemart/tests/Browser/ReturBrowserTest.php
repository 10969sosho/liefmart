<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Retur Flows
 *
 * Menguji tampilan retur dari browser:
 * 1. Retur Penjualan — index, create
 * 2. Retur Offline — index, create, process
 * 3. Retur Pembelian — index, create
 */
class ReturBrowserTest extends DuskTestCase
{
    /** @test */
    public function retur_penjualan_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/penjualan')
                ->assertSee('Retur')
                ->assertPresent('input[name="search"]');
        });
    }

    /** @test */
    public function retur_penjualan_create_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/penjualan/create')
                ->assertSee('Pilih Pesanan');
        });
    }

    /** @test */
    public function retur_offline_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/offline')
                ->assertSee('Retur')
                ->assertPresent('select[name="status"]');
        });
    }

    /** @test */
    public function retur_offline_create_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/offline/create')
                ->assertSee('Penjualan');
        });
    }

    /** @test */
    public function retur_pembelian_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/pembelian')
                ->assertSee('Retur')
                ->assertPresent('select[name="tipe_retur"]');
        });
    }

    /** @test */
    public function retur_pembelian_create_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/retur/pembelian/create')
                ->assertSee('Penerimaan');
        });
    }
}

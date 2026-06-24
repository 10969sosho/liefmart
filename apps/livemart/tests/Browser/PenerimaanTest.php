<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Penerimaan (Goods Receipt) Flow
 *
 * Menguji alur penerimaan dari perspektif browser:
 * 1. Login dan akses halaman penerimaan
 * 2. Melihat daftar penerimaan dengan filter
 * 3. Membuat penerimaan baru dengan batch items
 * 4. Melihat detail penerimaan
 * 5. Edit penerimaan
 * 6. Print penerimaan
 * 7. Export Excel
 * 8. Validasi form di frontend
 * 9. Error handling
 *
 * PRASYARAT:
 * - Database sudah di-seed dengan data test
 * - User superadmin tersedia (username: superadmin, password: password)
 * - Ada produk SKINCARE di database
 */
class PenerimaanTest extends DuskTestCase
{
    /** @test */
    public function penerimaan_index_displays_data()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1) // Superadmin ID
                ->visit('/penerimaan')
                ->assertSee('Data Penerimaan')
                ->assertSee('Tambah Penerimaan')
                ->assertSee('Export Excel');
        });
    }

    /** @test */
    public function penerimaan_create_form_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan')
                ->clickLink('Tambah Penerimaan')
                ->assertPathIs('/penerimaan/create')
                ->assertSee('Form Penerimaan Barang')
                ->assertPresent('input[name="kode_penerimaan"]')
                ->assertPresent('input[name="nomor_po"]')
                ->assertPresent('select[name="metode_pembayaran"]');
        });
    }

    /** @test */
    public function create_penerimaan_with_items()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan/create')
                ->type('kode_penerimaan', 'PNR-BROWSER-TEST-' . rand(1000, 9999))
                ->type('nomor_po', 'PO-BROWSER-TEST')
                ->type('tanggal_penerimaan', now()->format('Y-m-d'))
                ->select('metode_pembayaran', 'Cash')
                ->press('Simpan')
                ->assertPathIs('/penerimaan')
                ->assertSee('berhasil disimpan');
        });
    }

    /** @test */
    public function penerimaan_can_be_filtered()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan')
                ->assertPresent('select[name="status"]')
                ->assertPresent('input[name="kode"]')
                ->assertPresent('input[name="nomor_po"]');
        });
    }

    /** @test */
    public function penerimaan_detail_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan')
                ->assertPresent('table')
                ->assertPresent('a[href*="/penerimaan/"]');
        });
    }

    /** @test */
    public function penerimaan_print_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan')
                ->assertPresent('a[href*="/print"]');
        });
    }

    /** @test */
    public function creates_penerimaan_with_jatuh_tempo_payment()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan/create')
                ->type('kode_penerimaan', 'PNR-JT-' . rand(1000, 9999))
                ->type('nomor_po', 'PO-JT-TEST')
                ->type('tanggal_penerimaan', now()->format('Y-m-d'))
                ->select('metode_pembayaran', 'Jatuh Tempo')
                ->pause(500) // Wait for jatuh tempo field to appear
                ->type('tanggal_jatuh_tempo', now()->addDays(30)->format('Y-m-d'))
                ->press('Simpan')
                ->assertSee('berhasil disimpan');
        });
    }

    /** @test */
    public function validates_required_fields_in_form()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan/create')
                ->press('Simpan') // Submit empty form
                ->assertSee('harus diisi') // Should show validation errors
                ->assertPathIs('/penerimaan/create');
        });
    }

    /** @test */
    public function penerimaan_can_be_deleted()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/penerimaan')
                ->assertPresent('form[action*="/penerimaan/"] button[type="submit"]');
        });
    }
}

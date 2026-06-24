<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Master Data Pages
 *
 * Menguji halaman master data dari browser:
 * 1. Products list & Create
 * 2. Brands list & CRUD
 * 3. Customers list
 * 4. Initial price index
 */
class MasterDataBrowserTest extends DuskTestCase
{
    /** @test */
    public function products_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/products')
                ->assertSee('Produk')
                ->assertPresent('input[name="search"]');
        });
    }

    /** @test */
    public function product_create_form_has_all_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/products/create')
                ->assertPresent('select[name="brand_id"]')
                ->assertPresent('select[name="sub_brand_id"]')
                ->assertPresent('input[name="sku"]')
                ->assertPresent('input[name="name"]');
        });
    }

    /** @test */
    public function brands_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/brands')
                ->assertSee('Brand');
        });
    }

    /** @test */
    public function brand_create_form_has_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/brands/create')
                ->assertPresent('input[name="name"]')
                ->assertPresent('select[name="main_category_id"]');
        });
    }

    /** @test */
    public function customers_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/customers')
                ->assertSee('Pelanggan')
                ->assertPresent('input[name="search"]');
        });
    }

    /** @test */
    public function customer_create_form_has_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/customers/create')
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="phone"]')
                ->assertPresent('input[name="pic_name"]')
                ->assertPresent('select[name="status"]');
        });
    }

    /** @test */
    public function initial_price_index_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/products/initial-price')
                ->assertSee('Initial Price');
        });
    }

    /** @test */
    public function product_has_export_buttons()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/products')
                ->assertPresent('a[href*="/products/export/"]');
        });
    }
}

<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Login Flow (SKINCARE)
 *
 * Menguji alur login dari perspektif browser:
 * 1. Halaman login menampilkan form dan pilihan kategori
 * 2. Login sukses dengan username + kategori SKINCARE
 * 3. Login gagal dengan password salah
 * 4. Login gagal tanpa memilih kategori
 * 5. Setelah login sukses, redirect ke dashboard
 * 6. Session main_category tersimpan
 * 7. Logout dan redirect ke login
 *
 * PRASYARAT:
 * - Laravel Dusk terinstall (laravel/dusk)
 * - ChromeDriver atau Chromium untuk browser automation
 * - Database test sudah di-seed dengan MainCategory, User, dll
 */
class LoginTest extends DuskTestCase
{
    /** @test */
    public function login_page_has_required_elements()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->assertSee('SKINCARE')
                ->assertSee('KOPI')
                ->assertPresent('input[name="login"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('select[name="main_category_id"]')
                ->assertPresent('button[type="submit"]');
        });
    }

    /** @test */
    public function user_can_login_with_username_and_skincare()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'superadmin')
                ->type('password', 'password')
                ->select('main_category_id', '2') // SKINCARE
                ->press('Login')
                ->assertPathIs('/dashboard')
                ->assertSee('Dashboard');
        });
    }

    /** @test */
    public function login_fails_with_wrong_password()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'superadmin')
                ->type('password', 'wrongpassword')
                ->select('main_category_id', '2')
                ->press('Login')
                ->assertPathIs('/login')
                ->assertSee('Login gagal');
        });
    }

    /** @test */
    public function login_fails_without_main_category()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'superadmin')
                ->type('password', 'password')
                ->press('Login')
                ->assertPathIs('/login')
                ->assertSee('Kategori utama harus dipilih');
        });
    }

    /** @test */
    public function login_fails_with_nonexistent_user()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'nonexistent_user')
                ->type('password', 'password')
                ->select('main_category_id', '2')
                ->press('Login')
                ->assertPathIs('/login')
                ->assertSee('Login gagal');
        });
    }

    /** @test */
    public function user_can_logout()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'superadmin')
                ->type('password', 'password')
                ->select('main_category_id', '2')
                ->press('Login')
                ->assertPathIs('/dashboard');

            // Click logout (adjust selector based on actual UI)
            $browser->clickLink('Logout')
                ->assertPathIs('/login');
        });
    }

    /** @test */
    public function user_sees_skincare_data_after_skincare_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'superadmin')
                ->type('password', 'password')
                ->select('main_category_id', '2') // SKINCARE
                ->press('Login')
                ->assertPathIs('/dashboard')
                ->assertSee('SKINCARE'); // Should see SKINCARE context
        });
    }

    /** @test */
    public function unauthenticated_user_redirected_to_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/warehouse')
                ->assertPathIs('/login');
        });
    }
}

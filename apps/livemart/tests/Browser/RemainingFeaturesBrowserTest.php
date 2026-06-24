<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser Test: Remaining feature pages
 */
class RemainingFeaturesBrowserTest extends DuskTestCase
{
    /** @test */
    public function dashboard_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }

    /** @test */
    public function admin_roles_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/admin/roles')
                ->assertSee('Role');
        });
    }

    /** @test */
    public function admin_users_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/admin/users')
                ->assertSee('User');
        });
    }

    /** @test */
    public function mapping_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/master/mapping')
                ->assertSee('Mapping');
        });
    }

    /** @test */
    public function bank_accounts_index_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/master/bank-accounts')
                ->assertSee('Bank');
        });
    }

    /** @test */
    public function profile_page_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/profile')
                ->assertSee('Profile');
        });
    }

    /** @test */
    public function finance_choose_page_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit('/finance/choose')
                ->assertSee('Keuangan');
        });
    }
}

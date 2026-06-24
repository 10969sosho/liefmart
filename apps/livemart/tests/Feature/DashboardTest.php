<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MainCategory;
use App\Models\User;

/**
 * Feature Test: Dashboard & Home
 *
 * Halaman utama aplikasi setelah login.
 */
class DashboardTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $skincare = MainCategory::where('name', 'SKINCARE')->first();
        session(['main_category_id' => $skincare->id]);
        $this->loginAsSuperadmin();
    }

    /** @test */
    public function dashboard_page_accessible()
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    /** @test */
    public function home_page_accessible()
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_redirected_from_dashboard()
    {
        auth()->logout();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** @test */
    public function dashboard_requires_main_category()
    {
        session()->forget('main_category_id');
        $response = $this->get(route('dashboard'));
        $response->assertStatus(302); // Redirected by middleware
    }

    /** @test */
    public function root_redirects_to_login()
    {
        auth()->logout();
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }
}

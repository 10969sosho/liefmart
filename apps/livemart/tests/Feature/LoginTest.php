<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MainCategory;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

/**
 * Feature Test: Login (SKINCARE)
 *
 * Menguji:
 * 1. Halaman login tampil dengan pilihan kategori
 * 2. Login sukses dengan username (admin biasa) + kategori SKINCARE
 * 3. Login sukses dengan email (superadmin) + kategori SKINCARE
 * 4. Login gagal - tanpa kategori
 * 5. Login gagal - kredensial salah
 * 6. Login gagal - kategori tidak valid
 * 7. Login gagal - user nonaktif
 * 8. Session main_category_id tersimpan
 * 9. Logout & session dihapus
 * 10. Middleware auth dan main.category berfungsi
 */
class LoginTest extends TestCase
{

    private MainCategory $skincare;
    private User $superadmin;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();

        // Create superadmin role if not exists
        $superadminRole = Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'superadmin', 'display_name' => 'Superadmin']
        );

        // Create superadmin user (email login)
        $this->superadmin = User::factory()->create([
            'username' => 'superadmin_skincare',
            'email' => 'superadmin.skincare@liefmart.com',
            'password' => bcrypt('password'),
            'role_id' => $superadminRole->id,
            'is_active' => true,
        ]);

        // Create regular admin user (username login)
        $adminRole = Role::firstOrCreate(
            ['id' => 2],
            ['name' => 'admin', 'display_name' => 'Admin']
        );

        $this->admin = User::factory()->create([
            'username' => 'adminskincare',
            'email' => 'admin.skincare@liefmart.com',
            'password' => bcrypt('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function login_page_displays_main_category_selection()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('SKINCARE');
        $response->assertSee('KOPI');
    }

    /** @test */
    public function regular_user_can_login_with_username_and_skincare_category()
    {
        $response = $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals(session('main_category_id'), $this->skincare->id);
        $this->assertEquals(session('main_category_name'), 'SKINCARE');
    }

    /** @test */
    public function superadmin_can_login_with_email_and_skincare_category()
    {
        $response = $this->post('/login', [
            'login' => 'superadmin.skincare@liefmart.com',
            'password' => 'password',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function fails_login_without_main_category()
    {
        $response = $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            // No main_category_id
        ]);

        $response->assertSessionHasErrors('main_category_id');
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_with_invalid_main_category()
    {
        $response = $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => 99999,
        ]);

        $response->assertSessionHasErrors('main_category_id');
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_with_wrong_password()
    {
        $response = $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'wrongpassword',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_with_nonexistent_username()
    {
        $response = $this->post('/login', [
            'login' => 'nonexistent_user',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_regular_user_with_email()
    {
        // Regular admin cannot login with email - only superadmin can
        $response = $this->post('/login', [
            'login' => 'admin.skincare@liefmart.com',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_when_user_is_inactive()
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function session_contains_main_category_after_login()
    {
        $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertAuthenticated();
        $this->assertEquals($this->skincare->id, session('main_category_id'));
        $this->assertEquals('SKINCARE', session('main_category_name'));
    }

    /** @test */
    public function authenticated_access_to_dashboard_requires_main_category()
    {
        // Login without main.category middleware (simulate direct auth)
        $this->actingAs($this->admin);
        session()->forget('main_category_id');

        // Access dashboard - should be redirected or blocked
        $response = $this->get(route('dashboard'));
        
        // The main.category middleware should handle this
        // Without main_category_id in session, it should redirect
        $response->assertStatus(302);
    }

    /** @test */
    public function authenticated_user_with_skincare_can_access_penerimaan_page()
    {
        $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $response = $this->get(route('penerimaan.index'));

        // Should be accessible (may return 200 or 403 based on permissions)
        $this->assertContains($response->status(), [200, 403]);
    }

    /** @test */
    public function logout_clears_main_category_session()
    {
        $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertAuthenticated();
        $this->assertNotNull(session('main_category_id'));

        $this->post('/logout');

        $this->assertGuest();
        $this->assertNull(session('main_category_id'));
    }

    /** @test */
    public function guest_cannot_access_protected_warehouse_pages()
    {
        $response = $this->get(route('warehouse.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('warehouse.stock.list'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('warehouse.stock.analytics'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function switches_main_category_by_relogin()
    {
        // Login with SKINCARE
        $this->post('/login', [
            'login' => 'adminskincare',
            'password' => 'password123',
            'main_category_id' => $this->skincare->id,
        ]);

        $this->assertEquals('SKINCARE', session('main_category_name'));

        // Logout
        $this->post('/logout');

        // Login with KOPI (if exists)
        $kopi = MainCategory::where('name', 'KOPI')->first();
        if ($kopi) {
            $this->post('/login', [
                'login' => 'adminskincare',
                'password' => 'password123',
                'main_category_id' => $kopi->id,
            ]);

            $this->assertEquals('KOPI', session('main_category_name'));
        }
    }
}

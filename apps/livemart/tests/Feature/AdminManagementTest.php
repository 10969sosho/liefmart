<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\MainCategory;

/**
 * Feature Test: Admin Management (Roles, Users, Permissions)
 *
 * Hanya superadmin yang bisa akses.
 */
class AdminManagementTest extends TestCase
{

    private User $superadmin;
    private MainCategory $skincare;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        session(['main_category_id' => $this->skincare->id]);

        $this->superadmin = User::factory()->create([
            'username' => 'superadmin_test',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);
    }

    // ==================== ROLES ====================

    /** @test */
    public function roles_index_accessible_by_superadmin()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function roles_create_form_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.roles.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function roles_store_creates_new_role()
    {
        $this->actingAs($this->superadmin);
        $response = $this->post(route('admin.roles.store'), [
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    }

    /** @test */
    public function roles_edit_form_accessible()
    {
        $this->actingAs($this->superadmin);
        $role = Role::first();
        $response = $this->get(route('admin.roles.edit', $role->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function roles_update_changes_data()
    {
        $this->actingAs($this->superadmin);
        $role = Role::first();
        $response = $this->put(route('admin.roles.update', $role->id), [
            'name' => 'superadmin-updated',
            'display_name' => 'Superadmin Updated',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'superadmin-updated']);
    }

    /** @test */
    public function roles_toggle_status()
    {
        $this->actingAs($this->superadmin);
        $permission = Permission::first();
        if ($permission) {
            $response = $this->post(route('admin.roles.toggle-status', $permission->id));
            $this->assertContains($response->status(), [200, 302]);
        }
    }

    // ==================== USERS ====================

    /** @test */
    public function users_index_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function users_create_form_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.users.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function users_store_creates_new_user()
    {
        $this->actingAs($this->superadmin);
        $response = $this->post(route('admin.users.store'), [
            'username' => 'newuser',
            'email' => 'newuser@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'name' => 'New User',
            'role_id' => 1,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'newuser']);
    }

    /** @test */
    public function users_edit_form_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.users.edit', $this->superadmin->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function users_toggle_status()
    {
        $this->actingAs($this->superadmin);
        $response = $this->post(route('admin.users.toggle-status', $this->superadmin->id));
        $this->assertContains($response->status(), [200, 302]);
    }

    // ==================== PERMISSIONS ====================

    /** @test */
    public function permissions_index_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('admin.permissions.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function permissions_store_creates_new()
    {
        $this->actingAs($this->superadmin);
        $response = $this->post(route('admin.permissions.store'), [
            'name' => 'reports.view',
            'display_name' => 'View Reports',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('permissions', ['name' => 'reports.view']);
    }

    // ==================== REGULAR USER BLOCKED ====================

    /** @test */
    public function regular_user_blocked_from_admin()
    {
        $user = User::factory()->create([
            'username' => 'regular',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'is_active' => true,
        ]);
        $this->actingAs($user);
        $this->get(route('admin.roles.index'))->assertStatus(403);
    }

    /** @test */
    public function guest_blocked_from_admin()
    {
        auth()->logout();
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    }

    // ==================== PROFILE ====================

    /** @test */
    public function profile_page_accessible()
    {
        $this->actingAs($this->superadmin);
        $response = $this->get(route('users.profile'));
        $response->assertStatus(200);
    }

    /** @test */
    public function profile_update_changes_data()
    {
        $this->actingAs($this->superadmin);
        $response = $this->put(route('users.profile.update'), [
            'name' => 'Updated Profile Name',
            'email' => $this->superadmin->email,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->superadmin->id, 'name' => 'Updated Profile Name']);
    }
}

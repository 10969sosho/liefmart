<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

/**
 * Test: User Model
 */
class UserModelTest extends TestCase
{

    /** @test */
    public function creates_user()
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'name' => 'Test User',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->assertNotNull($user);
        $this->assertTrue($user->isActive());
    }

    /** @test */
    public function superadmin_check()
    {
        $role = Role::create(['name' => 'superadmin', 'display_name' => 'Superadmin']);
        $user = User::create([
            'username' => 'sa',
            'email' => 'sa@test.com',
            'password' => bcrypt('password'),
            'name' => 'SA',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->assertTrue($user->isSuperAdmin());
    }

    /** @test */
    public function inactive_user_check()
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::create([
            'username' => 'inactive',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'name' => 'Inactive',
            'role_id' => $role->id,
            'is_active' => false,
        ]);
        $this->assertFalse($user->isActive());
    }

    /** @test */
    public function user_belongs_to_role()
    {
        $role = Role::create(['name' => 'manager', 'display_name' => 'Manager']);
        $user = User::create([
            'username' => 'manager1',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'name' => 'Manager',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $user->role());
    }

    /** @test */
    public function user_factory_works()
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->assertNotNull($user->username);
        $this->assertNotNull($user->email);
    }
}

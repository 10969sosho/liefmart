<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class SuperadminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create superadmin role with ALL permissions
        $superadminRole = Role::updateOrCreate(
            ['name' => 'superadmin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Super Administrator dengan akses penuh ke seluruh sistem',
                'is_active' => true
            ]
        );

        // Give superadmin ALL permissions
        $allPermissions = Permission::all()->pluck('id');
        $superadminRole->permissions()->sync($allPermissions);

        $this->command->info("Superadmin role created with " . $allPermissions->count() . " permissions");
        
        // Also create a default admin role (with limited permissions)
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Administrator dengan akses terbatas',
                'is_active' => true
            ]
        );

        // Give admin basic permissions (not user management)
        $adminPermissions = Permission::where('category', '!=', 'user-management')->pluck('id');
        $adminRole->permissions()->sync($adminPermissions);

        $this->command->info("Admin role created with " . $adminPermissions->count() . " permissions");

        // Create staff role with minimal permissions
        $staffRole = Role::updateOrCreate(
            ['name' => 'staff'],
            [
                'display_name' => 'Staff',
                'description' => 'Staff dengan akses terbatas untuk operasional harian',
                'is_active' => true
            ]
        );

        // Give staff basic view permissions
        $staffPermissions = Permission::whereIn('name', [
            'sales.view', 'sales.create', 
            'warehouse.view', 
            'master.view',
            'analytics.view'
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        $this->command->info("Staff role created with " . $staffPermissions->count() . " permissions");
    }
}
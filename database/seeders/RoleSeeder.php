<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create default roles
        $roles = [
            [
                'name' => 'sales_staff',
                'display_name' => 'Staff Penjualan',
                'description' => 'Staff yang menangani penjualan online dan offline',
                'permissions' => [
                    'sales.view', 'sales.create', 'sales.edit', 'sales.offline', 'sales.online', 'sales.import', 'sales.export'
                ]
            ],
            [
                'name' => 'finance_staff',
                'display_name' => 'Staff Keuangan',
                'description' => 'Staff yang menangani keuangan semua platform',
                'permissions' => [
                    'finance.view', 'finance.create', 'finance.edit', 'finance.offline', 
                    'finance.shopee', 'finance.tokopedia', 'finance.tiktok', 'finance.blibli'
                ]
            ],
            [
                'name' => 'warehouse_staff',
                'display_name' => 'Staff Gudang',
                'description' => 'Staff yang menangani gudang dan inventory',
                'permissions' => [
                    'warehouse.view', 'warehouse.create', 'warehouse.edit', 
                    'master.view', 'master.create', 'master.edit'
                ]
            ],
            [
                'name' => 'analytics_viewer',
                'display_name' => 'Viewer Analitik',
                'description' => 'Role yang hanya bisa melihat analytics',
                'permissions' => [
                    'analytics.view', 'analytics.sales', 'analytics.finance'
                ]
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manager yang memiliki akses luas',
                'permissions' => [
                    'sales.view', 'sales.create', 'sales.edit', 'sales.offline', 'sales.online', 'sales.export',
                    'finance.view', 'finance.create', 'finance.edit', 'finance.offline', 
                    'finance.shopee', 'finance.tokopedia', 'finance.tiktok', 'finance.blibli',
                    'warehouse.view', 'warehouse.create', 'warehouse.edit',
                    'analytics.view', 'analytics.sales', 'analytics.finance',
                    'master.view', 'master.create', 'master.edit'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign permissions to role
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
            $role->permissions()->sync($permissionIds);

            $this->command->info("Role '{$role->display_name}' created with " . count($permissions) . " permissions");
        }

        $this->command->info('Roles seeded successfully!');
    }
}
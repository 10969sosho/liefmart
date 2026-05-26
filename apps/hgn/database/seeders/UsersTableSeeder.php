<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get role IDs
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole = Role::where('name', 'admin')->first();

        // Create Superadmin user (using email)
        User::create([
            'name' => 'Super Admin',
            'username' => null, // No username for superadmin
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superadminRole->id,
            'is_active' => true,
        ]);

        // Create Admin user (using username, no email)
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => null, // No email for admin
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->command->info('Users seeded successfully!');
        $this->command->info('Superadmin: superadmin@example.com / password123');
        $this->command->info('Admin: admin / password123');
    }
}

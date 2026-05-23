<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            SatuanSeeder::class,
            LokasiSeeder::class,
            MainCategorySeeder::class,
            PaymentMethodSeeder::class,
            TaxCategorySeeder::class,
            PlatformSeeder::class,
            
            // Role and Permission seeders must come before UsersTableSeeder
            PermissionSeeder::class,
            SuperadminRoleSeeder::class,
            UsersTableSeeder::class,
            
            // CustomerSeeder::class,
            // ProductSeeder::class,
            // PlatformProductSeeder::class,
            // TiktokMappingSeeder::class,
            // ShopeeMappingSeeder::class,
            // PenerimaanSeeder::class,
            
        ]);

        // Add invoice sequence seeder
        $this->call(InvoiceSequenceSeeder::class);
    }
}

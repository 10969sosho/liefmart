<?php

namespace Database\Seeders;

use Shared\Models\Customer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        
        // Contoh data customer dengan informasi lebih realistis untuk Indonesia
        $customCustomers = [
            [
                'name' => 'PT Maju Bersama Sejahtera',
                'address' => 'Jl. Gatot Subroto No. 123, Jakarta Selatan',
                'phone' => '021-5551234',
                'email' => 'info@majubersama.co.id',
                'npwp' => '01.234.567.8-123.000',
                'pic_name' => 'Budi Santoso',
                'status' => 'active',
            ],
            [
                'name' => 'CV Abadi Makmur',
                'address' => 'Jl. Raya Bogor Km. 30, Bogor',
                'phone' => '0251-8765432',
                'email' => 'contact@abadimakmur.com',
                'npwp' => '02.345.678.9-456.000',
                'pic_name' => 'Dewi Anggraini',
                'status' => 'active',
            ],
            [
                'name' => 'PT Sentosa Jaya',
                'address' => 'Jl. Diponegoro No. 45, Bandung',
                'phone' => '022-4567890',
                'email' => 'admin@sentosajaya.id',
                'npwp' => '03.456.789.0-789.000',
                'pic_name' => 'Ahmad Hidayat',
                'status' => 'active',
            ],
            [
                'name' => 'Toko Sejahtera Mandiri',
                'address' => 'Jl. Pahlawan No. 56, Surabaya',
                'phone' => '031-9876543',
                'email' => 'toko@sejahtera.id',
                'npwp' => '04.567.890.1-012.000',
                'pic_name' => 'Rini Wijaya',
                'status' => 'active',
            ],
            [
                'name' => 'PT Global Teknologi Indonesia',
                'address' => 'Jl. Sudirman Kav. 52-53, Jakarta Pusat',
                'phone' => '021-12345678',
                'email' => 'sales@globalteknologi.co.id',
                'npwp' => '05.678.901.2-345.000',
                'pic_name' => 'Hendry Gunawan',
                'status' => 'active',
            ],
        ];
        
        // Insert custom customers
        foreach ($customCustomers as $customer) {
            Customer::create($customer);
        }
        
        // Generate 45 more random customers
        for ($i = 0; $i < 45; $i++) {
            $companyTypes = ['PT', 'CV', 'Toko', 'UD', 'PD', 'Koperasi'];
            $companyType = $faker->randomElement($companyTypes);
            $companyName = $faker->company();
            
            if ($companyType != 'Toko' && $companyType != 'UD') {
                $name = $companyType . ' ' . $companyName;
            } else {
                $name = $companyType . ' ' . $faker->words(2, true);
            }
            
            // Format NPWP: XX.XXX.XXX.X-XXX.XXX
            $npwp = sprintf(
                "%02d.%03d.%03d.%01d-%03d.%03d",
                $faker->numberBetween(0, 99),
                $faker->numberBetween(0, 999),
                $faker->numberBetween(0, 999),
                $faker->numberBetween(0, 9),
                $faker->numberBetween(0, 999),
                $faker->numberBetween(0, 999)
            );
            
            $cities = ['Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Makassar', 'Palembang', 'Balikpapan', 'Yogyakarta', 'Malang'];
            $streets = ['Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gajah Mada', 'Jl. Asia Afrika', 'Jl. Diponegoro', 'Jl. Ahmad Yani', 'Jl. Pahlawan', 'Jl. Pemuda', 'Jl. Gatot Subroto', 'Jl. Hayam Wuruk'];
            
            Customer::create([
                'name' => $name,
                'address' => $faker->randomElement($streets) . ' No. ' . $faker->buildingNumber() . ', ' . $faker->randomElement($cities),
                'phone' => $faker->numerify(($faker->boolean(70) ? '0##-########' : '08##########')),
                'email' => $faker->companyEmail(),
                'npwp' => $faker->boolean(70) ? $npwp : null,
                'pic_name' => $faker->name(),
                'status' => $faker->boolean(90) ? 'active' : 'inactive',
            ]);
        }
    }
} 
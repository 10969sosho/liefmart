<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat data platform e-commerce
        $platforms = [
            ['name' => 'Shopee Lamourad'],
            ['name' => 'Shopee Liefmarket'],
            ['name' => 'Tiktok Lamourad'],
            ['name' => 'Tiktok Liefmarket'],
            ['name' => 'offline'],
        ];

        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
}

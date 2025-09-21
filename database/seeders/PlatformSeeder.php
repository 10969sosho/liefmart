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
            ['name' => 'shopee'],
            ['name' => 'tokopedia'],
            ['name' => 'tiktok'],
            ['name' => 'blibli'],
        ];

        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
}

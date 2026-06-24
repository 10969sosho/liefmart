<?php

namespace Database\Seeders;

use App\Models\MainCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MainCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data
        MainCategory::query()->delete();

        $categories = [
            [
                'name' => 'KOPI',
                'description' => 'Kategori untuk produk kopi',
                'is_active' => true,
            ],
            [
                'name' => 'SKINCARE',
                'description' => 'Kategori untuk produk skincare / kosmetik',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            MainCategory::create($category);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

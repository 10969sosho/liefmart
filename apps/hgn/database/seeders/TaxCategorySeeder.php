<?php

namespace Database\Seeders;

use App\Models\MainCategory;
use App\Models\TaxCategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TaxCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete related records first
        Product::query()->delete();

        // Clear tax categories
        TaxCategory::query()->delete();
        
        // Dapatkan ID kategori utama
        $kopiId = MainCategory::where('name', 'KOPI')->first()->id;
        $kosmetikId = MainCategory::where('name', 'KOSMETIK')->first()->id;

        // Kategori pajak untuk KOPI
        TaxCategory::create([
            'name' => 'PKP',
            'main_category_id' => $kopiId,
            'tax_percentage' => 11.00, // 11% PPN
            'description' => 'Pengusaha Kena Pajak',
            'is_active' => true,
        ]);

        TaxCategory::create([
            'name' => 'Non-PKP',
            'main_category_id' => $kopiId,
            'tax_percentage' => 0.00,
            'description' => 'Bukan Pengusaha Kena Pajak',
            'is_active' => true,
        ]);

        // Kategori pajak untuk KOSMETIK
        TaxCategory::create([
            'name' => 'HGN',
            'main_category_id' => $kosmetikId,
            'tax_percentage' => 10.00, // Contoh persentase
            'description' => 'Harvest Niaga Global',
            'is_active' => true,
        ]);

        TaxCategory::create([
            'name' => 'LM',
            'main_category_id' => $kosmetikId,
            'tax_percentage' => 0.00, // Contoh persentase
            'description' => 'LM',
            'is_active' => true,
        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
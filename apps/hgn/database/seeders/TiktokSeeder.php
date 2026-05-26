<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiktokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Pastikan platform TikTok ada
        $tiktok = Platform::where('name', 'tiktok')->first();
        if (!$tiktok) {
            $tiktok = Platform::create(['name' => 'tiktok']);
        }

        // Data produk TikTok berdasarkan gambar
        $tiktokProducts = [
            ['platform_product_name' => 'BIOAQUA - Sunscreen SPF 50 PA ++++ Sunscreen Gel 50g', 'variant' => 'UV SUNSCREEN GEL'],
            ['platform_product_name' => 'Bioaqua Tone Up UV Mist 150ml - UV Mist 150ml', 'variant' => 'UV Mist'],
            ['platform_product_name' => 'Garnier Bright Complete Vitamin C | Serum Day Cream SPF36 Skin Care -20ml | Yoghurt Sleeping Mask Night Skincare -20ml Wajah Mencerahkan Bersihkan Pencerah Yogurt', 'variant' => 'VIT C YOGHURT SLEEPING MASK NIGHT'],
            ['platform_product_name' => 'Bioaqua Tone Up UV Mist 150ml - UV Mist 150ml', 'variant' => 'UV Mist'],
            ['platform_product_name' => '[PAKET 12 PCS] BIOAQUA Sheet Mask 25gr - 12 varian Acid Arbutin Extract Hyaluronic Vitamin', 'variant' => 'PAKET 12 PCS'],
            ['platform_product_name' => '[PAKET 12 PCS] VARIAN BARU - BIOAQUA Sheet Mask 25gr Seri Buah dan Sayuran - 12 varian', 'variant' => 'PAKET 12 VARIAN BARU'],
            ['platform_product_name' => '[PAKET 12 PCS] BIOAQUA Sheet Mask 25gr - 12 varian Acid Arbutin Extract Hyaluronic Vitamin', 'variant' => 'PAKET 12 PCS'],
            ['platform_product_name' => 'BIOAQUA Hair Removal Moisturizing Krim Perontok Bulu Ketiak Non-irritating Perontok Bulu Permanen Penghilang Bulu Ketiak / Waxing Ketiak', 'variant' => 'HAIR REMOVAL'],
            ['platform_product_name' => '[FREE GIFT] BIOAQUA Paket 2 pcs 24K Gold Facial Cleanser pembersih wajah FREE 24k Forehead Mask 5g', 'variant' => 'PAKET 2 PCS 24k facial cleanser'],
            ['platform_product_name' => 'Viva White Moisturizer 30ml - Viva Pelembab 30ml', 'variant' => 'Moisturizer Mullberry'],
            ['platform_product_name' => 'VIVA Triple Face Serum Series | Anti Aging Serum | Glowing White Serum | Peeling Serum | Acne Serum | BPOM', 'variant' => 'Glow Serum'],
            ['platform_product_name' => 'VIVA Skin Treatment Cream - ANTI WRINKLE CREAM / COLLAGEN NIGHT CREAM / PEELING CREAM / MOIST CREAM - 22g', 'variant' => 'COLLAGEN NIGHT CREAM'],
            ['platform_product_name' => 'VIVA Triple Face Serum Series | Anti Aging Serum | Glowing White Serum | Peeling Serum | Acne Serum | BPOM', 'variant' => 'Peeling Serum'],
            ['platform_product_name' => 'Viva White Moisturizer 30ml - Viva Pelembab 30ml', 'variant' => 'Moisturizer Yogurt'],
            ['platform_product_name' => 'Viva White Moisturizer 30ml - Viva Pelembab 30ml', 'variant' => 'Pelembab GreenTea'],
            ['platform_product_name' => '[PAKET 5] BIOAQUA Serum Wajah Vitamin E Facial Essence Moisturizing Anti-aging Serum isi 60 Kapsul bpom Repair Skin FREE 1PCS SEAWEED MASK', 'variant' => 'Default'],
            ['platform_product_name' => 'Viva Lulur Mandi Brightening & Moisturizing (Body Scrub) 225g', 'variant' => 'Yogurt'],
            ['platform_product_name' => 'Viva Lulur Mandi Brightening & Moisturizing (Body Scrub) 225g', 'variant' => 'Bengkuang'],
            ['platform_product_name' => '[PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian Acid Arbutin Hydrating Lemon Orange', 'variant' => 'PAKET 24 PCS'],
            ['platform_product_name' => 'Viva Lulur Mandi Brightening & Moisturizing (Body Scrub) 225g', 'variant' => 'Soybean'],
            ['platform_product_name' => 'Emina Sun Battle SPF 30 PA+++ - Sunscreen Pelindung dari Sinar Matahari + UV - 60ml & 23ml Fun Size', 'variant' => 'Sun Battle 23ml'],
            ['platform_product_name' => 'Bioaqua Tone Up UV Mist 150ml - UV Mist 150ml', 'variant' => 'UV Mist'],
            ['platform_product_name' => '[PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian Acid Arbutin Hydrating Lemon Orange', 'variant' => 'PAKET 24 PCS'],
            ['platform_product_name' => 'Viva Fin-Touch (BLUSH ON) 2gr', 'variant' => '03 Red Pink'],
            ['platform_product_name' => '[PAKET 12 PCS] BIOAQUA Sheet Mask 25gr - 12 varian Acid Arbutin Extract Hyaluronic Vitamin', 'variant' => 'PAKET 12 PCS'],
            ['platform_product_name' => '[PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian Acid Arbutin Hydrating Lemon Orange', 'variant' => 'PAKET 24 PCS'],
            ['platform_product_name' => 'Garnier Sakura Glow Hyaluron | Serum Day Cream SPF30 / PA +++ Skin Care - 20ml (Krim Siang Untuk Kulit Cerah Merona) | Sleeping Mask Night Cream Skin Care - 20ml Mencerahkan Wajah Bunga Hitam Moisturizer', 'variant' => 'SAKURA GLOW HYALURON SERUM CREAM SPF30 -20ML'],
            ['platform_product_name' => 'Garnier Sakura Glow Hyaluron | Serum Day Cream SPF30 / PA +++ Skin Care - 20ml (Krim Siang Untuk Kulit Cerah Merona) | Sleeping Mask Night Cream Skin Care - 20ml Mencerahkan Wajah Bunga Hitam Moisturizer', 'variant' => 'SAKURA GLOW HYALURON SLEEPING MASK NIGHT -20ML'],
            ['platform_product_name' => 'Emina Sun Battle SPF 30 PA+++ - Sunscreen Pelindung dari Sinar Matahari + UV - 60ml & 23ml Fun Size', 'variant' => 'Sun Battle 60ml'],
            ['platform_product_name' => '[PAKET 24 PCS] BIOAQUA Sheet Mask 25gr Komplit 24 varian Acid Arbutin Hydrating Lemon Orange', 'variant' => 'PAKET 24 PCS'],
            ['platform_product_name' => '[PAKET 12 PCS] BIOAQUA eye mask Moisturizing masker mata 7.5g Memulurkan kerutan mata', 'variant' => 'eye mask Moisturizing masker mata'],
            ['platform_product_name' => 'BIOAQUA Yeast Collagen Mask Cream 30g face masker peel off', 'variant' => 'Yeast Collagen Mask Cream 30g'],
            ['platform_product_name' => '[PAKET 5] BIOAQUA Serum Wajah Vitamin E Facial Essence Moisturizing Anti-aging Serum isi 60 Kapsul bpom Repair Skin FREE 1PCS SEAWEED MASK', 'variant' => 'Default'],
            ['platform_product_name' => '[PAKET 12 PCS] VARIAN BARU - BIOAQUA Sheet Mask 25gr Seri Buah dan Sayuran - 12 varian', 'variant' => 'PAKET 12 VARIAN BARU'],
        ];

        // Hapus data lama jika ada
        PlatformProduct::where('platform_id', $tiktok->id)->delete();

        // Simpan semua platform products
        $platformProductIds = [];
        foreach ($tiktokProducts as $productData) {
            $platformProduct = PlatformProduct::create([
                'platform_id' => $tiktok->id,
                'platform_product_name' => $productData['platform_product_name'],
                'variant' => $productData['variant'],
            ]);

            $platformProductIds[] = $platformProduct->id;
        }

        $this->command->info('Total ' . count($tiktokProducts) . ' produk TikTok berhasil dimasukkan.');

        // Buat mapping barang secara acak
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada produk yang tersedia untuk mapping. Jalankan ProductSeeder terlebih dahulu.');
            return;
        }

        // Hapus mapping lama jika ada
        // Hapus mapping lama jika ada
        DB::table('mapping_barangs')
            ->whereIn('platform_product_id', function ($query) use ($tiktok) {
                $query->select('id')->from('platform_products')->where('platform_id', $tiktok->id);
            })
            ->delete();

        $mappings = [];
        $mappingCount = 0;

        // Looping melalui semua platform products TikTok
        foreach ($platformProductIds as $platformProductId) {
            // Pilih 1-3 produk acak untuk mapping
            $numProductsToMap = rand(1, 3);
            $randomProducts = $products->random(min($numProductsToMap, $products->count()));

            foreach ($randomProducts as $product) {
                $mappings[] = [
                    'platform_product_id' => $platformProductId,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 5), // Quantity acak antara 1-5
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $mappingCount++;
            }
        }

        // Masukkan mappings ke database
        if (!empty($mappings)) {
            DB::table('mapping_barangs')->insert($mappings);
            $this->command->info('Total ' . $mappingCount . ' mapping barang TikTok berhasil dibuat.');
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\PlatformProduct;
use Illuminate\Database\Seeder;

class LazadaProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dapatkan platform Lazada
        $platform = Platform::where('name', 'lazada')->first();
        
        if (!$platform) {
            $this->command->error('Platform Lazada tidak ditemukan. Jalankan PlatformSeeder terlebih dahulu.');
            return;
        }

        // Data produk Lazada
        $products = [
            // BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Greentea'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Spirulina'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Mawar'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Standard'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Bengkoang'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Lemon'],
            ['platform_product_name' => 'BUNDLING 3in1 VIVA Milk Cleanser + Face Tonic + Kapas 35gr (KECIL)', 'variant' => '3in1 Cucumber'],
            
            // (PAKET USAHA) Vicell Skin Care For Scars Solution
            ['platform_product_name' => '(PAKET USAHA) Vicell Skin Care For Scars Solution', 'variant' => 'Vicell 6pc'],
            ['platform_product_name' => '(PAKET USAHA) Vicell Skin Care For Scars Solution', 'variant' => 'Vicell 12pc'],
            
            // (PAKET USAHA) Dragon Minyak Urut 60 ml
            ['platform_product_name' => '(PAKET USAHA) Dragon Minyak Urut 60 ml - Minyak Pijat & Urut - Meredakan Pegal Linu', 'variant' => 'Minyak Urut 1 Lusin'],
            
            // (PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => 'Ash Blonde,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '4 - Coklat,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '5.32 - Coklat Carame,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '6.26 - Plum Red,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '6.62 Cranberry Red,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '6.64 - Merah Berry,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '7.3 - Golden Brown,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '7.65 - Raspberry Red,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => 'True Blue,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '1 - Hitam Alami,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '3 - Coklat kehitaman,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '3.1 - Midnight Blue,1BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Garnier Color Naturals / Ultra Color Sachet - Pewarna Rambut , Cat Rambut Natural', 'variant' => '3.16 - Burgundy Alam,1BOX/6PCS'],
            
            // (PAKET USAHA) Ovale 2in1 Facial Lotion
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Anti Acne 6pc,60ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Deep Control 6pc,60ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Deep Control 6pc,100ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Luminous 6pc,60ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Luminous 6pc,100ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Anti Acne 6pc,100ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Anti Acne 6pc,200ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Deep Control 6pc,200ml'],
            ['platform_product_name' => '(PAKET USAHA) Ovale 2in1 Facial Lotion 60ml/100ml/200ml | Deep Control - Anti Acne - Luminous', 'variant' => 'Luminous 6pc,200ml'],
            
            // (PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On Strong,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On Citrus,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On SandalWood,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On Kayu Putih,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On Splash Fruit,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Roll On Sport,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Mix Inhealer Citrus,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Mix Inhealer Eucalyp,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Press&Relax Strong,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => 'Press&Relax KayuPth,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => '4in1 Smash Strong,12pcs'],
            ['platform_product_name' => '(PAKET USAHA) FRESHCARE Minyak Angin Aromatheraphy All Varian - 1 Lusin', 'variant' => '4in1 Smash Matcha,12pcs'],
            
            // (PAKET USAHA) Herborist Juice For Skin Body Serum
            ['platform_product_name' => '(PAKET USAHA) Herborist Juice For Skin Body Serum 180ml', 'variant' => 'Paket 3pc,Herborist Body Serum'],
            ['platform_product_name' => '(PAKET USAHA) Herborist Juice For Skin Body Serum 180ml', 'variant' => 'Paket 6pc,Herborist Body Serum'],
            
            // (PAKET USAHA) Selection Kapas
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Kapas Bulat 1Lusin'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Tipis 60s 1Lusin'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Tipis 175s 1Lusin'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Tebal 60s 1Lusin'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Cotton Bud 180\'s 6pc'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Cotton Bud Reff 12pc'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Tebal 120s1Lusin'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Cotton Bud 180 12pc'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Cotton Bal Biru 12pc'],
            ['platform_product_name' => '(PAKET USAHA) Selection Kapas Tebal / Tipis / Cotton Ball / Cotton Bud / Facial Cotton Round LUSINAN', 'variant' => 'Cotton Bal Hjau 12pc'],
            
            // (PAKET USAHA) Viva White Moisture Balm
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Nourish Health 190ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Rich Moist 95ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Smooth & Glow 95ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Protect Care 95ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Nourish Bright 92ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Sunblock 85ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Healthy Glow 92ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Collagen Asta 92ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Protect Care 190ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Smooth & Glow 190ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'SPF 30 185ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Collagen Asta 185ml,set 6 Banded'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Choco Dreamer,12PCS/1BOX'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Aloe Secret,12PCS/1BOX'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Orange Blast,12PCS/1BOX'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisture Balm Chic On Lips -  4 Varian', 'variant' => 'Strawberry Inside,12PCS/1BOX'],
            
            // (PAKET USAHA) SALSA Nail Polish Remover
            ['platform_product_name' => '(PAKET USAHA) SALSA Nail Polish Remover / Aseton 1 Box / 1 Lusin', 'variant' => 'Aseton 80ml,12 pcs'],
            ['platform_product_name' => '(PAKET USAHA) SALSA Nail Polish Remover / Aseton 1 Box / 1 Lusin', 'variant' => 'Aseton 40ml,12 pcs'],
            ['platform_product_name' => '(PAKET USAHA) SALSA Nail Polish Remover / Aseton 1 Box / 1 Lusin', 'variant' => 'Aseton 100ml,12 pcs'],
            
            // (PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'PINK CUPCAKE,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'TRULY ENCHANTE,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'ALMOST FAMOUS,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'BOHEMIAN SPIRIT,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'PERSONA BLUSH,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'YELLOW IN LUV,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'LOVE YOURSELF,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'BERRY MACARON,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'PRINCESS CHARMING,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'SUMMER GARDEN PARTY,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'COTTON DREAMS,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'DESERT QUEEN,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'GREEN MIRAGE,1 BOX/6PCS'],
            ['platform_product_name' => '(PAKET USAHA) Fres & Natural Spray Cologne Hijab Refresh | BT21 Collection - 100ml', 'variant' => 'MIDNIGHT SECRET,1 BOX/6PCS'],
            
            // (PAKET USAHA) Makarizo Hair Energy Scentsations
            ['platform_product_name' => '(PAKET USAHA) Makarizo Hair Energy Scentsations Hair Fragrance 30ml & 100ml', 'variant' => 'Pkt 12pc (30ml) MIX,MKZ Hair Fragrance'],
            ['platform_product_name' => '(PAKET USAHA) Makarizo Hair Energy Scentsations Hair Fragrance 30ml & 100ml', 'variant' => 'Pkt 6pc (100ml) MIX,MKZ Hair Fragrance'],
            ['platform_product_name' => '(PAKET USAHA) Makarizo Hair Energy Scentsations Hair Fragrance 30ml & 100ml', 'variant' => 'Pkt 2pc (30ml) MIX,MKZ Hair Fragrance'],
            ['platform_product_name' => '(PAKET USAHA) Makarizo Hair Energy Scentsations Hair Fragrance 30ml & 100ml', 'variant' => 'Pkt 3pc (100ml) MIX,MKZ Hair Fragrance'],
            ['platform_product_name' => '(PAKET USAHA) Makarizo Hair Energy Scentsations Hair Fragrance 30ml & 100ml', 'variant' => 'Pkt 6pc (30ml) MIX,MKZ Hair Fragrance'],
            
            // (PAKET USAHA) VIVA Clean Mask
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Pink 6pc'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Pink 12p'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Hijau 6pc'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Hijau 12p'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Oren 6pc'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Clean Mask Pink, Oren, Hijau', 'variant' => 'Clean Mask Oren 12p'],
            
            // (PAKET USAHA) VIVA Acne Series
            ['platform_product_name' => '(PAKET USAHA) VIVA Acne Series I Acne Gel 6 pcs I Acne lotion 6 pcs I Whitening Cream 15gr 12 pcs I Whitening Cream 40g 6 pcs', 'variant' => 'Acne Gel 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Acne Series I Acne Gel 6 pcs I Acne lotion 6 pcs I Whitening Cream 15gr 12 pcs I Whitening Cream 40g 6 pcs', 'variant' => 'WhiteCream 15g 12pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Acne Series I Acne Gel 6 pcs I Acne lotion 6 pcs I Whitening Cream 15gr 12 pcs I Whitening Cream 40g 6 pcs', 'variant' => 'WhiteCreamJUMBO 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Acne Series I Acne Gel 6 pcs I Acne lotion 6 pcs I Whitening Cream 15gr 12 pcs I Whitening Cream 40g 6 pcs', 'variant' => 'WhiteCream 15g 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Acne Series I Acne Gel 6 pcs I Acne lotion 6 pcs I Whitening Cream 15gr 12 pcs I Whitening Cream 40g 6 pcs', 'variant' => 'Acne Lotion 6pcs'],
            
            // (PAKET USAHA) Viva White Moisturizer
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Moisturizer Mullbery,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Moisturizer Soybean,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Moisturizer Yogurt,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Pelembab Undermakeup,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Pelembab Bengkuang,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) Viva White Moisturizer 30ml - Viva Pelembab 30ml BANDED 6PCS', 'variant' => 'Pelembab Greentea,Set 6pcs'],
            
            // (PAKET USAHA) VIVA Triple Face Serum
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Gold Whitening Serum,1 BOX/12PCS'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Anti Aging Serum,1 BOX/12PCS'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Vitamin C + Collagen,1 BOX/12PCS'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Peeling Serum,1 BOX/12PCS'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Glowing White Serum,1 BOX/12PCS'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Triple Face Serum LUSINAN Glowing White, Peeling, Gold Whitening Serum', 'variant' => 'Acne Serum,1 BOX/12PCS'],
            
            // (PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic Green Tea,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic Spirulina,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Cleanser,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Cleanser Lemon,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Clean Bengkoang,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Clean Cucumber,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Clean Greentea,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Milk Clean Spirulina,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic Lemon,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic Bengkoang,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Face Tonic Cucumber,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Air Mawar 100ml,Set 6pcs'],
            ['platform_product_name' => '(PAKET USAHA) VIVA Milk Cleanser, Face Tonic, Air Mawar , Mawar Milk Cleanser 100ml All Varian BANDED 6PCS', 'variant' => 'Mawar Milk Cleanser,Set 6pcs'],
            
            // Produk tanpa variant
            ['platform_product_name' => '[PAKET 96PCS] BIOAQUA SHEET MASK 25gr - MASKER WAJAH KOMPLIT', 'variant' => null],
            ['platform_product_name' => '[PAKET 48PCS] BIOAQUA SHEET MASK 25gr - MASKER WAJAH KOMPLIT', 'variant' => null],
            ['platform_product_name' => '[PAKET 12 PCS] VARIAN BARU - BIOAQUA Sheet Mask 25gr Seri Buah dan Sayuran - 12 varian', 'variant' => null],
            ['platform_product_name' => 'BIOAQUA 24K Gold SkinBIOAQUA 24K Gold Skin Brightening Serum Essence Cream BPOM 50gr || BIOAQUA 24K Gold Black Truffle Anti-Aging Skincare BPOM Firming  Moisturizer 50gr Brightening Serum Essence Cream BPOM - 24K Cream - 50gr', 'variant' => '24K Gold Cream 50g'],
            ['platform_product_name' => '[PAKET 9 PCS] - BIOAQUA - Sheet Mask 28 gr - Flowers Series', 'variant' => null],
            ['platform_product_name' => '[PAKET 24 PCS] BIOAQUA SHEET MASK 25gr - MASKER WAJAH KOMPLIT 24 varian.', 'variant' => null],
            ['platform_product_name' => '[PAKET 12 PCS] BIOAQUA SHEET MASK 25gr - MASKER WAJAH TERSEDIA DALAM 12 VARIAN', 'variant' => null],
        ];

        $this->command->info('Memulai seeding produk Lazada...');
        
        $created = 0;
        $skipped = 0;

        foreach ($products as $product) {
            // Cek apakah produk sudah ada
            $existingProduct = PlatformProduct::where('platform_id', $platform->id)
                ->where('platform_product_name', $product['platform_product_name'])
                ->where('variant', $product['variant'])
                ->first();

            if (!$existingProduct) {
                PlatformProduct::create([
                    'platform_id' => $platform->id,
                    'platform_product_name' => $product['platform_product_name'],
                    'variant' => $product['variant'],
                ]);
                $created++;
                $this->command->info("✓ Created: {$product['platform_product_name']}" . ($product['variant'] ? " - {$product['variant']}" : ""));
            } else {
                $skipped++;
                $this->command->warn("⚠ Skipped (already exists): {$product['platform_product_name']}" . ($product['variant'] ? " - {$product['variant']}" : ""));
            }
        }

        $this->command->info("✅ Seeding selesai!");
        $this->command->info("📊 Statistik:");
        $this->command->info("   - Created: {$created} produk");
        $this->command->info("   - Skipped: {$skipped} produk");
        $this->command->info("   - Total: " . ($created + $skipped) . " produk");
    }
}

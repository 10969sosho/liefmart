<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateInitialPriceVivaThreeItemsSeeder extends Seeder
{
    public function run(): void
    {
        $codes = ['VV1247', 'VV1244', 'VV1243'];

        DB::table('products')
            ->whereIn('sku', $codes)
            ->orWhereIn('barcode', $codes)
            ->update([
                'initial_price' => 45000.00,
            ]);
    }
}


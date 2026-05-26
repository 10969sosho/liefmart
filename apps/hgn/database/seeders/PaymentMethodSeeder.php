<?php

namespace Database\Seeders;

use Shared\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear payment methods
        PaymentMethod::query()->delete();

        // Buat metode pembayaran Cash
        PaymentMethod::create([
            'name' => 'Cash',
            'description' => 'Pembayaran tunai langsung',
            'requires_due_date' => false,
            'is_active' => true,
        ]);

        // Buat metode pembayaran Jatuh Tempo
        PaymentMethod::create([
            'name' => 'Jatuh Tempo',
            'description' => 'Pembayaran di masa mendatang (perlu tanggal jatuh tempo)',
            'requires_due_date' => true,
            'is_active' => true,
        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
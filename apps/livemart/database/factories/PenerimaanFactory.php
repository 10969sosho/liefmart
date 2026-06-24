<?php

namespace Database\Factories;

use App\Models\Penerimaan;
use App\Models\MainCategory;
use App\Models\TaxCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenerimaanFactory extends Factory
{
    protected $model = Penerimaan::class;

    public function definition(): array
    {
        $mainCategory = MainCategory::where('is_active', true)->first();
        $taxCategory = TaxCategory::where('main_category_id', $mainCategory?->id)->first();

        return [
            'kode_penerimaan' => 'PNR-' . $this->faker->unique()->bothify('####??'),
            'main_category_id' => $mainCategory?->id ?? 1,
            'tax_category_id' => $taxCategory?->id ?? 1,
            'nomor_po' => 'PO-' . $this->faker->bothify('####??'),
            'tanggal_penerimaan' => $this->faker->date(),
            'metode_pembayaran' => 'Cash',
            'total_harga' => 0,
            'status' => 'Unlocated',
            'lokasi_id' => 1,
        ];
    }
}

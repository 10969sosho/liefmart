<?php

namespace Database\Factories;

use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    public function definition(): array
    {
        return [
            'kode' => $this->faker->unique()->lexify('LOC_???'),
            'nama' => $this->faker->word(),
            'deskripsi' => $this->faker->sentence(),
        ];
    }
}

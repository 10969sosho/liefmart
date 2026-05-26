<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        Satuan::query()->delete();
        
        // Daftar satuan umum
        $satuans = [
            ['name' => 'Pcs', 'kode' => 'PCS', 'description' => 'Pieces/Buah'],
            
        ];
        
        // Insert data
        foreach ($satuans as $satuan) {
            Satuan::create([
                'name' => $satuan['name'],
                'kode' => $satuan['kode'],
                'description' => $satuan['description'],
                'is_active' => true
            ]);
        }
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlatformProduct;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;

class PlatformProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platformFiles = [
            1 => 'MASTER - MASTER SHOPEE.csv',
            3 => 'MASTER - MASTER TIKTOK.csv',
        ];

        foreach ($platformFiles as $platformId => $filename) {
            $csvPath = storage_path('app/imports/' . $filename);
            if (!File::exists($csvPath)) {
                $this->command->warn("File not found: $filename");
                continue;
            }

            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            $headers = array_map('trim', $csv->getHeader());
            $csv->setHeaderOffset(null);
            foreach ($csv as $row) {
                $record = [];
                foreach ($headers as $i => $header) {
                    $record[$header] = isset($row[$i]) ? trim($row[$i]) : '';
                }
                $productName = $record['NAMA BARANG'] ?? null;
                $variant = $record['VARIAN'] ?? null;
                if (
                    !$productName || strtoupper($productName) === 'NAMA BARANG' ||
                    ($variant !== null && strtoupper($variant) === 'VARIAN')
                ) {
                    $this->command->warn("Skipping header/invalid row in $filename");
                    continue;
                }
                PlatformProduct::updateOrCreate(
                    [
                        'platform_id' => $platformId,
                        'platform_product_name' => $productName,
                        'variant' => $variant,
                    ],
                    [] // No additional fields to update
                );
            }
            $this->command->info("Imported products for platform ID $platformId from $filename");
        }
    }
} 
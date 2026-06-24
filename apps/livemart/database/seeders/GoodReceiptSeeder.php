<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Carbon\Carbon;

class GoodReceiptSeeder extends Seeder
{
    public function run()
    {
        // Data hardcode dari gambar
        $data = [
            // VIVA VW WATERDROP SLEEPING MASK 80GR
            [
                'nama_produk' => 'VIVA VW WATERDROP SLEEPING MASK 80GR',
                'po' => 'VA-PO-KOS-2402-0006',
                'tgl_po' => '2025-02-06',
                'qty' => 9,
                'hpp' => 10000,
                'disc' => 400,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'VIVA VW WATERDROP SLEEPING MASK 80GR',
                'po' => 'VA-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 9,
                'hpp' => 10000,
                'disc' => 800,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'VIVA VW WATERDROP SLEEPING MASK 80GR',
                'po' => 'VA-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 9,
                'hpp' => 10000,
                'disc' => 800,
                'ed' => '2027-12',
            ],
            [
                'nama_produk' => 'VIVA VW WATERDROP SLEEPING MASK 80GR',
                'po' => 'VA-PO-KOS-2403-0012',
                'tgl_po' => '2025-03-12',
                'qty' => 9,
                'hpp' => 12000,
                'disc' => 480,
                'ed' => '2027-12',
            ],
            // NIVEA EXTRA BRIGHT BODY SERUM 180ML
            [
                'nama_produk' => 'NIVEA EXTRA BRIGHT CARE & PROTECT 8 SUPER FOOD SPF15 BODY SERUM 180ML',
                'po' => 'NV-PO-KOS-2402-0006',
                'tgl_po' => '2025-02-06',
                'qty' => 1,
                'hpp' => 15000,
                'disc' => 600,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'NIVEA EXTRA BRIGHT CARE & PROTECT 8 SUPER FOOD SPF15 BODY SERUM 180ML',
                'po' => 'NV-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 1,
                'hpp' => 15000,
                'disc' => 1200,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'NIVEA EXTRA BRIGHT CARE & PROTECT 8 SUPER FOOD SPF15 BODY SERUM 180ML',
                'po' => 'NV-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 1,
                'hpp' => 15000,
                'disc' => 1200,
                'ed' => '2027-12',
            ],
            [
                'nama_produk' => 'NIVEA EXTRA BRIGHT CARE & PROTECT 8 SUPER FOOD SPF15 BODY SERUM 180ML',
                'po' => 'NV-PO-KOS-2403-0012',
                'tgl_po' => '2025-03-12',
                'qty' => 1,
                'hpp' => 17000,
                'disc' => 680,
                'ed' => '2027-12',
            ],
            // GARNIER BRIGHT COMP SPF30 YUZU 20ML EB
            [
                'nama_produk' => 'GARNIER BRIGHT COMP SPF30 YUZU 20ML EB',
                'po' => 'GAR-PO-KOS-2402-0006',
                'tgl_po' => '2025-02-06',
                'qty' => 20,
                'hpp' => 20000,
                'disc' => 4000,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'GARNIER BRIGHT COMP SPF30 YUZU 20ML EB',
                'po' => 'GAR-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 20,
                'hpp' => 20000,
                'disc' => 5200,
                'ed' => '2028-01',
            ],
            [
                'nama_produk' => 'GARNIER BRIGHT COMP SPF30 YUZU 20ML EB',
                'po' => 'GAR-PO-KOS-2403-0009',
                'tgl_po' => '2025-03-02',
                'qty' => 20,
                'hpp' => 20000,
                'disc' => 5200,
                'ed' => '2027-12',
            ],
            [
                'nama_produk' => 'GARNIER BRIGHT COMP SPF30 YUZU 20ML EB',
                'po' => 'GAR-PO-KOS-2403-0012',
                'tgl_po' => '2025-03-12',
                'qty' => 18,
                'hpp' => 22000,
                'disc' => 4400,
                'ed' => '2027-12',
            ],
        ];

        // Group by PO
        $poGroups = collect($data)->groupBy('po');
        $mainCategoryId = 2; // SKINCARE
        $taxCategoryId = 3; // PKP (atau sesuaikan kebutuhan)
        $satuanId = 1; // Default satuan
        $lokasiId = 2; // Gudang A (atau sesuaikan kebutuhan)

        // Get default values for required fields
        $defaultBrand = Brand::first();
        $defaultSubBrand = SubBrand::first();
        $defaultProductCategory = ProductCategory::first();
        $defaultProductType = ProductType::first();
        $defaultProductSize = ProductSize::first();
        $defaultProductVariant = ProductVariant::first();

        if (!$defaultBrand || !$defaultSubBrand || !$defaultProductCategory || 
            !$defaultProductType || !$defaultProductSize) {
            throw new \Exception('Required default values not found. Please seed the master data first.');
        }

        foreach ($poGroups as $po => $items) {
            $first = $items->first();
            $tanggalPenerimaan = Carbon::parse($first['tgl_po']);
            $totalHarga = $items->sum(function($item) {
                return $item['qty'] * $item['hpp'] - $item['disc'];
            });

            $penerimaan = Penerimaan::create([
                'kode_penerimaan' => 'GR-' . $po,
                'main_category_id' => $mainCategoryId,
                'tax_category_id' => $taxCategoryId,
                'nomor_po' => $po,
                'tanggal_penerimaan' => $tanggalPenerimaan,
                'metode_pembayaran' => 'Cash',
                'tanggal_jatuh_tempo' => null,
                'total_harga' => $totalHarga,
                'status' => 'Located',
                'catatan' => null,
                'lokasi_id' => $lokasiId,
            ]);

            foreach ($items as $item) {
                // Find or create product
                $product = Product::firstOrCreate(
                    ['name' => $item['nama_produk']],
                    [
                        'name' => $item['nama_produk'],
                        'main_category_id' => $mainCategoryId,
                        'brand_id' => $defaultBrand->id,
                        'sub_brand_id' => $defaultSubBrand->id,
                        'product_category_id' => $defaultProductCategory->id,
                        'product_type_id' => $defaultProductType->id,
                        'product_size_id' => $defaultProductSize->id,
                        'product_variant_id' => $defaultProductVariant ? $defaultProductVariant->id : null,
                        'tax_category_id' => $taxCategoryId,
                        'is_active' => true
                    ]
                );

                $subtotal = $item['qty'] * $item['hpp'] - $item['disc'];
                $detail = PenerimaanDetail::create([
                    'penerimaan_id' => $penerimaan->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'satuan_id' => $satuanId,
                    'harga_hpp' => $item['hpp'],
                    'diskon_persen_1' => 0,
                    'diskon_nominal_1' => $item['disc'],
                    'diskon_persen_2' => 0,
                    'diskon_nominal_2' => 0,
                    'diskon_persen_3' => 0,
                    'diskon_nominal_3' => 0,
                    'diskon_persen_4' => 0,
                    'diskon_nominal_4' => 0,
                    'diskon_persen_5' => 0,
                    'diskon_nominal_5' => 0,
                    'is_free' => 0,
                    'subtotal' => $subtotal,
                    'catatan' => null,
                ]);

                WarehouseStock::create([
                    'product_id' => $product->id,
                    'lokasi_id' => $lokasiId,
                    'penerimaan_detail_id' => $detail->id,
                    'tax_id' => $taxCategoryId,
                    'qty' => $item['qty'],
                    'expired_date' => Carbon::parse($item['ed'].'-01'),
                    'status_ed' => 'aman',
                    'catatan' => null,
                ]);
            }
        }
    }
} 
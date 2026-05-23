<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevisePenerimaanBioaqua extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:revise-bioaqua 
                            {kode_penerimaan=PR-20250930-587 : Kode penerimaan (default: PR-20250930-587)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisi qty dan cogs penerimaan Bioaqua dan update warehouse stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kodePenerimaan = $this->argument('kode_penerimaan');

        $this->info("Mencari penerimaan dengan kode: {$kodePenerimaan}");

        // Find penerimaan by kode_penerimaan
        $penerimaan = Penerimaan::where('kode_penerimaan', $kodePenerimaan)->first();

        if (!$penerimaan) {
            $this->error("Penerimaan dengan kode '{$kodePenerimaan}' tidak ditemukan.");
            return 1;
        }

        $this->info("Penerimaan ditemukan: {$penerimaan->kode_penerimaan} (ID: {$penerimaan->id})");
        $this->info("Nomor PO: {$penerimaan->nomor_po}");

        // Define the products to update from the image
        $productsToUpdate = [
            ['name' => 'BIOAQUA DARLING ME MATTE LIP TINT - CORAL', 'new_qty' => 173, 'new_cogs' => 17567.57],
            ['name' => 'BIOAQUA FANTASTIC ME GLOSSY LIP TINT - LYCHEE PINK', 'new_qty' => 173, 'new_cogs' => 17567.57],
            ['name' => 'BIOAQUA FANTASTIC ME GLOSSY LIP TINT - NUDE PINK', 'new_qty' => 246, 'new_cogs' => 19017.80],
            ['name' => 'BIOAQUA HYDRATING SOFT AND FLAWLESS AIR CUSHION BB CREAM - NATURAL COLOR (15G+15G)', 'new_qty' => 41, 'new_cogs' => 44504.50],
            ['name' => 'BIOAQUA CALENDULA REFRESHING & SOOTHING MASK 28G', 'new_qty' => 544, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA GARDENIA ECTOIN SENSITIVE REPAIR MASK 28G', 'new_qty' => 194, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA LILY LACTOBACILLUS ANTI-AGING MASK 28G', 'new_qty' => 338, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA PLUM HYALURONIC ACID NOURISHING & MOISTURIZING MASK 28G', 'new_qty' => 330, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA ROSE YEAST ELASTIC & TENDER MASK 28G', 'new_qty' => 371, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA SAKURA NIACINAMIDE BRIGHTENING MASK 28G', 'new_qty' => 315, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA SOPHORA CERAMIDE MOISTURIZING ANTIOXIDANT MASK 28G', 'new_qty' => 690, 'new_cogs' => 2476.58],
            ['name' => 'BIOAQUA ALOE VERA NICOTINAMIDE ACNE CARE BRIGHTENING ESSENCE MASK 25G', 'new_qty' => 72, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA APPLE CERAMIDE NP DEEP MOISTURIZING ESSENCE MASK 25G', 'new_qty' => 621, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA AVOCADO BIFIDA FERMENT LYSATE MOISTURIZING ESSENCE MASK 25G', 'new_qty' => 37, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA BLACKCURRANT HYALURONIC ACID MOISTURIZING SKIN ESSENCE MASK 25G', 'new_qty' => 605, 'new_cogs' => 2123.91],
            ['name' => 'BIOAQUA BLUEBERRY COLLAGEN MOISTURIZING ELASTIC ESSENCE MASK 25G', 'new_qty' => 835, 'new_cogs' => 2005.16],
            ['name' => 'BIOAQUA CARROT HYDRATING & GLOWING SKIN ESSENCE MASK 25G', 'new_qty' => 1295, 'new_cogs' => 1894.21],
            ['name' => 'BIOAQUA CENTELLA ASTAXANTHIN MOISTURIZING ANTIOXIDANT ESSENCE MASK 25G', 'new_qty' => 746, 'new_cogs' => 2042.43],
            ['name' => 'BIOAQUA CHAMOMILE AMINO ACID SOOTHING AND TENDER SKIN ESSENCE MASK 25G', 'new_qty' => 455, 'new_cogs' => 2158.56],
            ['name' => 'BIOAQUA CHERRY CERAMIDE AP SKIN BARRIER ESSENCE MASK 25G', 'new_qty' => 485, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA COCONUT NOURISHING & ANTIOXIDANT ESSENCE MASK 25G', 'new_qty' => 950, 'new_cogs' => 1967.35],
            ['name' => 'BIOAQUA CUCUMBER CERAMIDE EOP FRESH & SOOTHING ESSENCE MASK 25G', 'new_qty' => 588, 'new_cogs' => 2136.38],
            ['name' => 'BIOAQUA DRAGON FRUIT NOURISHING & ANTIOXIDANT ESSENCE MASK 25G', 'new_qty' => 497, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA GRAPE COPPER TRIPEPTIDE-1 ANTI WRINKLE & FIRMING ESSENCE MASK 25G', 'new_qty' => 1169, 'new_cogs' => 1915.72],
            ['name' => 'BIOAQUA GRAPEFRUIT ALGAE PORE REFINING ESSENCE MASK 25G', 'new_qty' => 1399, 'new_cogs' => 1879.63],
            ['name' => 'BIOAQUA HONEY FULLERERENE BRIGHTENING AND FIRMING ESSENCE MASK 25G', 'new_qty' => 595, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA KIWI VITAMIN E DELICATE MOISTURIZING ESSENCE MASK 25G', 'new_qty' => 336, 'new_cogs' => 2158.56],
            ['name' => 'BIOAQUA LEMON VITAMIN C HYDRATING AND BRIGHTENING ESSENCE MASK 25G', 'new_qty' => 981, 'new_cogs' => 1959.22],
            ['name' => 'BIOAQUA MANGOSTEEN NOURISHING & BRIGHTENING ESSENCE MASK 25G', 'new_qty' => 854, 'new_cogs' => 1998.21],
            ['name' => 'BIOAQUA ORANGE MOISTURIZING & HYDRATING ESSENCE MASK 25G', 'new_qty' => 573, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA PAPAYA ELASTICITY & NOURISHING ESSENCE MASK 25G', 'new_qty' => 277, 'new_cogs' => 1692.79],
            ['name' => 'BIOAQUA PEACH EXTRACT HEXAPEPTIDE EXTRACT FACIAL MASK 25G', 'new_qty' => 488, 'new_cogs' => 2158.56],
            ['name' => 'BIOAQUA POMEGRANATE OLIGOPEPTIDE FRESH AND BRIGHTENING ESSENCE MASK 25G', 'new_qty' => 510, 'new_cogs' => 2158.56],
        ];

        $this->info("\nProduk yang akan direvisi: " . count($productsToUpdate) . " items");

        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan revisi?')) {
                $this->info('Revisi dibatalkan.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Get Gudang A location
            $gudangALokasi = Lokasi::where('kode', 'GUDANG_A')->first();
            if (!$gudangALokasi) {
                // Try fallback or throw error
                $gudangALokasi = Lokasi::first();
                if (!$gudangALokasi) {
                     throw new \Exception('Tidak ada lokasi ditemukan');
                }
                $this->warn("GUDANG_A tidak ditemukan, menggunakan lokasi pertama: {$gudangALokasi->name}");
            }

            foreach ($productsToUpdate as $productUpdate) {
                $this->info("\n--- Memproses: {$productUpdate['name']} ---");

                // Find product by name (exact or partial match)
                $product = Product::where('name', 'like', "%{$productUpdate['name']}%")->first();

                if (!$product) {
                    $this->error("Produk '{$productUpdate['name']}' tidak ditemukan.");
                    continue;
                }

                $this->info("Produk ditemukan: {$product->name} (ID: {$product->id})");

                // Find penerimaan detail
                $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$penerimaanDetail) {
                    $this->error("Detail penerimaan untuk produk '{$product->name}' tidak ditemukan.");
                    // Optional: Create detail if missing? User said "Revisi", implying it exists.
                    continue;
                }

                $oldQty = (float) $penerimaanDetail->qty;
                $newQty = (float) $productUpdate['new_qty'];
                $oldCogs = (float) $penerimaanDetail->harga_hpp;
                $newCogs = (float) $productUpdate['new_cogs'];
                
                $qtyDiff = $newQty - $oldQty;

                $this->info("Qty: {$oldQty} -> {$newQty} (Diff: {$qtyDiff})");
                $this->info("COGS: {$oldCogs} -> {$newCogs}");

                // Update penerimaan_detail
                $penerimaanDetail->qty = $newQty;
                $penerimaanDetail->harga_hpp = $newCogs;
                // Update subtotal assuming no discounts for now, or keep discount ratio?
                // Subtotal usually = (qty * harga_hpp) - discounts
                // We'll recalculate subtotal based on new qty and cogs, preserving discount structure if possible.
                // But for simplicity and since image doesn't show discounts, we assume subtotal = qty * cogs
                // Check if there are discounts
                $discount = $penerimaanDetail->diskon_nominal_1 + $penerimaanDetail->diskon_nominal_2 + $penerimaanDetail->diskon_nominal_3;
                if ($discount > 0) {
                     $this->warn("Produk memiliki diskon. Subtotal akan dihitung ulang qty * new_cogs - existing_discounts.");
                }
                
                $penerimaanDetail->subtotal = ($newQty * $newCogs) - $discount; // Simple logic
                $penerimaanDetail->save();
                
                $this->info("✅ Penerimaan detail update saved");

                // Check existing warehouse stock
                $warehouseStocks = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->get();
                
                if ($warehouseStocks->count() > 0) {
                    if ($qtyDiff > 0) {
                        // Add to first stock
                        $firstStock = $warehouseStocks->first();
                        $firstStock->qty += $qtyDiff;
                        $firstStock->save();
                        $this->info("✅ Menambahkan {$qtyDiff} pcs ke warehouse stock ID {$firstStock->id}");
                    } elseif ($qtyDiff < 0) {
                        $remainingToReduce = abs($qtyDiff);
                        foreach ($warehouseStocks as $stock) {
                            if ($remainingToReduce <= 0) {
                                break;
                            }
                            $reduceAmount = min((int) $stock->qty, $remainingToReduce);
                            $stock->qty = (int) $stock->qty - $reduceAmount;
                            $stock->save();
                            $remainingToReduce -= $reduceAmount;
                        }
                        $this->info("✅ Warehouse stock qty dikurangi sebanyak " . abs($qtyDiff) . " pcs");
                    } else {
                        $this->info("Qty tidak berubah, warehouse stock tidak diupdate");
                    }
                } else {
                    // Create new stock if qty > 0
                    if ($newQty > 0) {
                        WarehouseStock::create([
                            'product_id' => $penerimaanDetail->product_id,
                            'lokasi_id' => $gudangALokasi->id,
                            'penerimaan_detail_id' => $penerimaanDetail->id,
                            'tax_id' => $penerimaan->tax_category_id,
                            'qty' => $newQty,
                            'expired_date' => null,
                            'status_ed' => 'aman',
                            'catatan' => '',
                            'source_type' => 'penerimaan',
                            'source_id' => $penerimaan->id,
                            'source_date' => $penerimaan->tanggal_penerimaan ?? now(),
                        ]);
                        $this->info("✅ Warehouse stock baru dibuat");
                    }
                }
            }

            // Recalculate total harga
            $penerimaan->recalculateTotalHarga();
            $this->info("\n✅ Total harga penerimaan berhasil dihitung ulang: " . number_format($penerimaan->total_harga, 2));

            DB::commit();

            $this->info("\n✅ Revisi berhasil selesai!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}

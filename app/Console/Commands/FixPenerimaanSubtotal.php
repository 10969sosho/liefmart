<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Helpers\NumberFormatter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPenerimaanSubtotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:fix-subtotal 
                            {--dry-run : Hanya tampilkan perbedaan tanpa update}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbaiki subtotal penerimaan detail yang tidak konsisten dengan perhitungan ulang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - Tidak akan mengubah data');
        }

        $this->info('Mencari penerimaan detail dengan subtotal tidak konsisten...');
        $this->newLine();

        $inconsistentDetails = [];
        $penerimaans = Penerimaan::with('details')->get();

        foreach ($penerimaans as $penerimaan) {
            foreach ($penerimaan->details as $detail) {
                // Hitung ulang subtotal menggunakan NumberFormatter (seperti saat penyimpanan)
                $qty = (float)($detail->qty ?? 0);
                $hargaHpp = (float)($detail->harga_hpp ?? 0);
                
                // Jika is_free, subtotal = 0
                if ($detail->is_free) {
                    $subtotalCalculated = 0;
                } else {
                    $subtotalSebelumDiskon = NumberFormatter::multiplyDecimal($qty, $hargaHpp);
                    $subtotal = $subtotalSebelumDiskon;
                    
                    // Hitung diskon cascading menggunakan NumberFormatter
                    for ($i = 1; $i <= 5; $i++) {
                        $diskonPersen = (float)($detail->{"diskon_persen_$i"} ?? 0);
                        $diskonNominal = (float)($detail->{"diskon_nominal_$i"} ?? 0);
                        
                        if ($diskonPersen > 0) {
                            $potongan = NumberFormatter::percentageOf($subtotal, $diskonPersen);
                            $subtotal = NumberFormatter::subtractDecimal($subtotal, $potongan);
                        } elseif ($diskonNominal > 0) {
                            $subtotal = NumberFormatter::subtractDecimal($subtotal, $diskonNominal);
                        }
                    }
                    
                    $subtotalCalculated = $subtotal;
                }
                
                // Bandingkan dengan subtotal tersimpan
                $subtotalStored = (float)$detail->subtotal;
                $diff = abs($subtotalStored - $subtotalCalculated);
                
                if ($diff > 0.01) {
                    $inconsistentDetails[] = [
                        'detail_id' => $detail->id,
                        'penerimaan_id' => $penerimaan->id,
                        'penerimaan_kode' => $penerimaan->kode_penerimaan,
                        'product_name' => $detail->product->name ?? 'N/A',
                        'qty' => $qty,
                        'harga_hpp' => $hargaHpp,
                        'is_free' => $detail->is_free,
                        'subtotal_stored' => $subtotalStored,
                        'subtotal_calculated' => $subtotalCalculated,
                        'diff' => $diff
                    ];
                }
            }
        }

        $this->info("Ditemukan " . count($inconsistentDetails) . " detail dengan subtotal tidak konsisten");
        $this->newLine();

        if (count($inconsistentDetails) == 0) {
            $this->info('✅ Semua subtotal sudah konsisten!');
            return 0;
        }

        // Tampilkan ringkasan
        $this->table(
            ['Kode Penerimaan', 'Produk', 'Subtotal Lama', 'Subtotal Baru', 'Selisih'],
            array_map(function($inc) {
                return [
                    $inc['penerimaan_kode'],
                    substr($inc['product_name'], 0, 30) . '...',
                    number_format($inc['subtotal_stored'], 2, ',', '.'),
                    number_format($inc['subtotal_calculated'], 2, ',', '.'),
                    number_format($inc['diff'], 2, ',', '.')
                ];
            }, array_slice($inconsistentDetails, 0, 10))
        );

        if (count($inconsistentDetails) > 10) {
            $this->info('... dan ' . (count($inconsistentDetails) - 10) . ' detail lainnya');
        }

        $this->newLine();

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin memperbaiki semua subtotal ini?')) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        if ($dryRun) {
            $this->info('✅ DRY RUN - Data tidak diubah');
            return 0;
        }

        try {
            DB::beginTransaction();

            $fixedCount = 0;
            $penerimaanIds = [];

            foreach ($inconsistentDetails as $inc) {
                $detail = PenerimaanDetail::find($inc['detail_id']);
                if ($detail) {
                    $oldSubtotal = $detail->subtotal;
                    $detail->subtotal = $inc['subtotal_calculated'];
                    $detail->save();
                    
                    $fixedCount++;
                    
                    if (!in_array($inc['penerimaan_id'], $penerimaanIds)) {
                        $penerimaanIds[] = $inc['penerimaan_id'];
                    }
                    
                    $this->line("✅ Fixed detail ID {$detail->id}: " . 
                               number_format($oldSubtotal, 2, ',', '.') . 
                               " -> " . number_format($inc['subtotal_calculated'], 2, ',', '.'));
                }
            }

            // Recalculate total_harga untuk semua penerimaan yang terpengaruh
            $this->newLine();
            $this->info('Menghitung ulang total_harga untuk penerimaan yang terpengaruh...');
            
            foreach ($penerimaanIds as $penerimaanId) {
                $penerimaan = Penerimaan::find($penerimaanId);
                if ($penerimaan) {
                    $oldTotal = $penerimaan->total_harga;
                    $newTotal = $penerimaan->recalculateTotalHarga();
                    $this->line("✅ Recalculated penerimaan {$penerimaan->kode_penerimaan}: " . 
                               number_format($oldTotal, 2, ',', '.') . 
                               " -> " . number_format($newTotal, 2, ',', '.'));
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ Berhasil memperbaiki {$fixedCount} detail subtotal");
            $this->info("✅ Berhasil menghitung ulang total untuk " . count($penerimaanIds) . " penerimaan");

            // Verifikasi
            $this->newLine();
            $this->info('Verifikasi konsistensi...');
            
            $remainingInconsistent = 0;
            $penerimaans = Penerimaan::with('details')->get();
            foreach ($penerimaans as $penerimaan) {
                foreach ($penerimaan->details as $detail) {
                    $qty = (float)($detail->qty ?? 0);
                    $hargaHpp = (float)($detail->harga_hpp ?? 0);
                    
                    if ($detail->is_free) {
                        $subtotalCalculated = 0;
                    } else {
                        $subtotalSebelumDiskon = NumberFormatter::multiplyDecimal($qty, $hargaHpp);
                        $subtotal = $subtotalSebelumDiskon;
                        
                        for ($i = 1; $i <= 5; $i++) {
                            $diskonPersen = (float)($detail->{"diskon_persen_$i"} ?? 0);
                            $diskonNominal = (float)($detail->{"diskon_nominal_$i"} ?? 0);
                            
                            if ($diskonPersen > 0) {
                                $potongan = NumberFormatter::percentageOf($subtotal, $diskonPersen);
                                $subtotal = NumberFormatter::subtractDecimal($subtotal, $potongan);
                            } elseif ($diskonNominal > 0) {
                                $subtotal = NumberFormatter::subtractDecimal($subtotal, $diskonNominal);
                            }
                        }
                        
                        $subtotalCalculated = $subtotal;
                    }
                    
                    $subtotalStored = (float)$detail->subtotal;
                    $diff = abs($subtotalStored - $subtotalCalculated);
                    
                    if ($diff > 0.01) {
                        $remainingInconsistent++;
                    }
                }
            }

            if ($remainingInconsistent == 0) {
                $this->info('✅ Semua subtotal sekarang konsisten!');
            } else {
                $this->warn("⚠️  Masih ada {$remainingInconsistent} detail yang tidak konsisten");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}


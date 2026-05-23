<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FixTiktokFinancialOrders extends Command
{
    protected $signature = 'tiktok:repair-orders 
                            {order_numbers* : Daftar nomor pesanan TikTok} 
                            {--source=existing : Sumber data biaya/pembayaran (existing|manual)} 
                            {--fees-json= : JSON nominal biaya admin, affiliate, shipping, voucher, cashback, biaya6..12} 
                            {--payment= : Nominal saldo masuk total} 
                            {--payment-date= : Tanggal masuk pembayaran (YYYY-MM-DD)} 
                            {--payment-day= : Hari masuk pembayaran} 
                            {--dry-run : Jalankan tanpa perubahan}';

    protected $description = 'Perbaiki transaksi TikTok yang terbelah PKP/Non-PKP tanpa biaya admin dan saldo masuk';

    public function handle()
    {
        $orderNumbers = $this->argument('order_numbers');
        $source = $this->option('source') ?: 'existing';
        $feesJson = $this->option('fees-json');
        $payment = $this->option('payment');
        $paymentDate = $this->option('payment-date');
        $paymentDay = $this->option('payment-day');
        $dryRun = $this->option('dry-run');

        if ($source === 'manual' && (!$feesJson || $payment === null)) {
            $this->error('Untuk source=manual, wajib set --fees-json dan --payment');
            return Command::FAILURE;
        }

        $fees = [
            'nominal_diskon1' => 0,
            'nominal_diskon2' => 0,
            'nominal_diskon3' => 0,
            'nominal_diskon4' => 0,
            'nominal_diskon5' => 0,
            'nominal_diskon6' => 0,
            'nominal_diskon7' => 0,
            'nominal_diskon8' => 0,
            'nominal_diskon9' => 0,
            'nominal_diskon10' => 0,
            'nominal_diskon11' => 0,
            'nominal_diskon12' => 0,
        ];

        if ($source === 'manual') {
            try {
                $parsed = json_decode($feesJson, true, 512, JSON_THROW_ON_ERROR);
                foreach ($fees as $k => $v) {
                    if (isset($parsed[$k])) {
                        $fees[$k] = (float)$parsed[$k];
                    }
                }
                $payment = (float)$payment;
            } catch (\Throwable $e) {
                $this->error('fees-json tidak valid: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('Memproses ' . count($orderNumbers) . ' nomor pesanan');

        foreach ($orderNumbers as $noOrder) {
            try {
                $order = Order::where('order_number', $noOrder)->first();
                if (!$order) {
                    $this->warn("Order {$noOrder} tidak ditemukan");
                    continue;
                }

                $transactions = TiktokFinancialTransaction::where('no_order', $noOrder)->orderBy('id')->get();
                if ($transactions->isEmpty()) {
                    $this->warn("Transaksi TikTok untuk {$noOrder} tidak ditemukan");
                    continue;
                }

                $currentGroups = $transactions->filter(function ($t) {
                    return $t->no_invoice && (strpos($t->no_invoice, '/01') !== false || strpos($t->no_invoice, '/02') !== false);
                });

                if ($currentGroups->isEmpty()) {
                    $this->warn("Tidak ada grup PKP/Non-PKP aktif untuk {$noOrder}");
                    continue;
                }

                $totalNominal = (float)$currentGroups->sum('nominal_harga');
                if ($totalNominal <= 0) {
                    $this->warn("Total nominal_harga 0 untuk {$noOrder}");
                    continue;
                }

                if ($source === 'existing') {
                    $sourceTrans = $transactions->first(function ($t) {
                        return ($t->nominal_diskon1 ?? 0) != 0 ||
                               ($t->nominal_diskon2 ?? 0) != 0 ||
                               ($t->nominal_diskon3 ?? 0) != 0 ||
                               ($t->nominal_diskon4 ?? 0) != 0 ||
                               ($t->nominal_diskon5 ?? 0) != 0 ||
                               ($t->saldo_masuk ?? 0) != 0;
                    });

                    if (!$sourceTrans) {
                        $this->warn("Tidak ada transaksi sumber yang memiliki biaya/saldo untuk {$noOrder}. Gunakan --source=manual");
                        continue;
                    }

                    $fees = [
                        'nominal_diskon1' => (float)($sourceTrans->nominal_diskon1 ?? 0),
                        'nominal_diskon2' => (float)($sourceTrans->nominal_diskon2 ?? 0),
                        'nominal_diskon3' => (float)($sourceTrans->nominal_diskon3 ?? 0),
                        'nominal_diskon4' => (float)($sourceTrans->nominal_diskon4 ?? 0),
                        'nominal_diskon5' => (float)($sourceTrans->nominal_diskon5 ?? 0),
                        'nominal_diskon6' => (float)($sourceTrans->nominal_diskon6 ?? 0),
                        'nominal_diskon7' => (float)($sourceTrans->nominal_diskon7 ?? 0),
                        'nominal_diskon8' => (float)($sourceTrans->nominal_diskon8 ?? 0),
                        'nominal_diskon9' => (float)($sourceTrans->nominal_diskon9 ?? 0),
                        'nominal_diskon10' => (float)($sourceTrans->nominal_diskon10 ?? 0),
                        'nominal_diskon11' => (float)($sourceTrans->nominal_diskon11 ?? 0),
                        'nominal_diskon12' => (float)($sourceTrans->nominal_diskon12 ?? 0),
                    ];
                    $payment = abs((float)($sourceTrans->saldo_masuk ?? 0));
                    $paymentDate = $sourceTrans->tanggal_masuk_pembayaran ?? $paymentDate;
                    $paymentDay = $sourceTrans->hari_masuk_pembayaran ?? $paymentDay;
                }

                $updated = 0;

                foreach ($currentGroups as $groupTrans) {
                    $proportion = $groupTrans->nominal_harga > 0 ? ($groupTrans->nominal_harga / $totalNominal) : 0;

                    $groupTrans->nominal_diskon1 = round($fees['nominal_diskon1'] * $proportion, 2);
                    $groupTrans->nominal_diskon2 = round($fees['nominal_diskon2'] * $proportion, 2);
                    $groupTrans->nominal_diskon3 = round($fees['nominal_diskon3'] * $proportion, 2);
                    $groupTrans->nominal_diskon4 = round($fees['nominal_diskon4'] * $proportion, 2);
                    $groupTrans->nominal_diskon5 = round($fees['nominal_diskon5'] * $proportion, 2);
                    $groupTrans->nominal_diskon6 = round($fees['nominal_diskon6'] * $proportion, 2);
                    $groupTrans->nominal_diskon7 = round($fees['nominal_diskon7'] * $proportion, 2);
                    $groupTrans->nominal_diskon8 = round($fees['nominal_diskon8'] * $proportion, 2);
                    $groupTrans->nominal_diskon9 = round($fees['nominal_diskon9'] * $proportion, 2);
                    $groupTrans->nominal_diskon10 = round($fees['nominal_diskon10'] * $proportion, 2);
                    $groupTrans->nominal_diskon11 = round($fees['nominal_diskon11'] * $proportion, 2);
                    $groupTrans->nominal_diskon12 = round($fees['nominal_diskon12'] * $proportion, 2);

                    if ($payment !== null) {
                        $groupTrans->saldo_masuk = round($payment * $proportion, 2);
                        if ($paymentDate) {
                            $groupTrans->tanggal_masuk_pembayaran = $paymentDate;
                        }
                        if ($paymentDay) {
                            $groupTrans->hari_masuk_pembayaran = $paymentDay;
                        }
                    }

                    $groupTrans->calculateNominalFix();
                    $groupTrans->calculateOutstanding();
                    $groupTrans->calculatePercentages();

                    if (!$dryRun) {
                        $groupTrans->save();
                    }
                    $updated++;
                }

                $this->info("{$noOrder}: diperbarui {$updated} grup");
            } catch (\Throwable $e) {
                $this->error("Error {$noOrder}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}

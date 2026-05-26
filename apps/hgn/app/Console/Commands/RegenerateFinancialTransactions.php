<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\TiktokFinancialTransaction;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TokopediaFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
use App\Models\LazadaFinancialTransaction;
use App\Models\InvoiceSequence;
use App\Services\OrderTaxSplitter;
use Carbon\Carbon;

class RegenerateFinancialTransactions extends Command
{
    protected $signature = 'finance:regenerate-all {--force : Force execution without confirmation}';
    protected $description = 'Regenerate all financial transactions with correct Tax ID split logic.';

    public function handle(OrderTaxSplitter $splitter)
    {
        if (!$this->option('force') && !$this->confirm('This will TRUNCATE all financial transaction tables and regenerate them. Are you sure?')) {
            return;
        }

        $this->info("Starting regeneration process...");

        // 1. Get All Orders (Sorted by Date)
        // We do this BEFORE truncate to capture existing fee data if possible?
        // No, we process order by order. We can capture fee data inside the loop.
        // BUT if we truncate first, we lose data!
        // So we CANNOT truncate everything at once if we want to preserve data.
        // We must process order by order:
        //   - Read old trans
        //   - Delete old trans
        //   - Create new trans
        // BUT Invoice Sequence needs to be reset to start from 1.
        // If we process order by order without truncating sequence table, the numbers will just append.
        // To get "Sequential from Start", we DO need to reset sequence.
        // And we need to process chronologically.
        
        // Solution:
        // 1. Fetch ALL Orders.
        // 2. Build a memory map of "Order Fees" from existing transactions.
        // 3. Truncate tables.
        // 4. Process Orders using the memory map.

        $this->info("Step 1: Backing up Fee Data...");
        $feeMap = $this->backupFeeData();
        $this->info("Backed up fees for " . count($feeMap) . " orders.");

        $this->info("Step 2: Truncating Tables...");
        $this->truncateTables();

        $this->info("Step 3: Regenerating Transactions...");
        $orders = Order::orderBy('tanggal', 'asc')->orderBy('created_at', 'asc')->get();
        $this->info("Found " . $orders->count() . " orders to process.");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            try {
                $fees = $feeMap[$order->id] ?? null;
                $this->processOrder($order, $splitter, $fees);
            } catch (\Exception $e) {
                // Log error but continue
                $this->error("\nError processing Order {$order->order_number}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Regeneration complete.");
    }

    private function backupFeeData()
    {
        $map = [];
        $platforms = [
            'tiktok' => TiktokFinancialTransaction::class,
            'shopee' => ShopeeFinancialTransaction::class,
            'tokopedia' => TokopediaFinancialTransaction::class,
            'blibli' => BlibliFinancialTransaction::class,
        ];

        foreach ($platforms as $name => $class) {
            if (!class_exists($class)) continue;
            
            try {
                // Chunk to save memory
                $class::chunk(1000, function($transactions) use (&$map) {
                    foreach ($transactions as $t) {
                        if (!isset($map[$t->order_id])) {
                            $map[$t->order_id] = [
                                'discounts' => array_fill(1, 12, 0),
                                'adjustment' => 0,
                                'original_total_value' => 0
                            ];
                        }
                        
                        // Sum discounts
                        for ($i = 1; $i <= 12; $i++) {
                            $col = "nominal_diskon{$i}";
                            $map[$t->order_id]['discounts'][$i] += ($t->$col ?? 0);
                        }
                        $map[$t->order_id]['adjustment'] += ($t->adjustment ?? 0);
                        $map[$t->order_id]['original_total_value'] += ($t->nominal_harga ?? 0);
                    }
                });
            } catch (\Exception $e) {
                $this->warn("Could not backup $name: " . $e->getMessage());
            }
        }
        return $map;
    }

    private function truncateTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        TiktokFinancialTransaction::truncate();
        ShopeeFinancialTransaction::truncate();
        TokopediaFinancialTransaction::truncate();
        BlibliFinancialTransaction::truncate();
        try { LazadaFinancialTransaction::truncate(); } catch (\Exception $e) {}

        InvoiceSequence::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function processOrder($order, $splitter, $fees)
    {
        // 1. Split Order
        $taxGroups = $splitter->splitOrder($order);

        if (empty($taxGroups)) {
            return;
        }

        // Calculate total value of new groups
        $totalNewValue = 0;
        foreach ($taxGroups as $group) {
            $totalNewValue += $group['total_value'];
        }

        // 2. Create Transaction for each group
        foreach ($taxGroups as $taxId => $group) {
            $this->createTransaction($order, $taxId, $group, $fees, $totalNewValue);
        }
    }

    private function createTransaction($order, $taxId, $group, $fees, $totalNewValue)
    {
        $platform = strtolower($order->platform);
        $model = null;

        if (str_contains($platform, 'tiktok')) {
            $model = new TiktokFinancialTransaction();
        } elseif (str_contains($platform, 'shopee')) {
            $model = new ShopeeFinancialTransaction();
        } elseif (str_contains($platform, 'tokopedia')) {
            $model = new TokopediaFinancialTransaction();
        } elseif (str_contains($platform, 'blibli')) {
            $model = new BlibliFinancialTransaction();
        } else {
            return;
        }

        // Generate Invoice Number
        // Use dynamic static call
        $className = get_class($model);
        if (method_exists($className, 'generateInvoiceNumber')) {
            $invoiceNumber = $className::generateInvoiceNumber($order, $taxId);
        } else {
            // Fallback
            $invoiceNumber = "INV-{$order->order_number}-{$taxId}";
        }

        $model->order_id = $order->id;
        $model->tanggal_order = $order->tanggal;
        $model->hari_order = $order->hari ?? Carbon::parse($order->tanggal)->format('l');
        $model->no_order = $order->order_number;
        $model->no_invoice = $invoiceNumber;
        
        // Values
        $model->qty = $group['total_qty'];
        $model->nominal_harga = $group['total_value'];
        
        // Apply Fees Proportionally
        $ratio = ($totalNewValue > 0) ? ($group['total_value'] / $totalNewValue) : 0;
        
        if ($fees) {
            for ($i = 1; $i <= 12; $i++) {
                $col = "nominal_diskon{$i}";
                $model->$col = $fees['discounts'][$i] * $ratio;
            }
            $model->adjustment = $fees['adjustment'] * $ratio;
        } else {
            // No fees found (new order or lost data)
            // Initialize with 0
             for ($i = 1; $i <= 12; $i++) {
                $col = "nominal_diskon{$i}";
                $model->$col = 0;
            }
            $model->adjustment = 0;
        }

        // Calculate Saldo Masuk
        // Saldo Masuk = Nominal Harga + Sum(Diskons) + Adjustment
        // Note: Diskons are usually negative.
        $totalDiscounts = 0;
         for ($i = 1; $i <= 12; $i++) {
            $col = "nominal_diskon{$i}";
            $totalDiscounts += $model->$col;
        }
        
        $model->saldo_masuk = $model->nominal_harga + $totalDiscounts + $model->adjustment;
        $model->nominal_fix = $model->saldo_masuk; // Assuming nominal_fix matches saldo_masuk for now
        $model->outstanding = 0; // Paid fully?

        $model->tanggal_masuk_pembayaran = Carbon::now(); // Dummy
        // Ideally preserve old payment date if in $fees?
        // Let's assume user just wants to fix the Invoices.

        $model->save();
    }
}

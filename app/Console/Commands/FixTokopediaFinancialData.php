<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TokopediaFinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FixTokopediaFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:tokopedia-financial-data {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Tokopedia financial transactions with incorrect nominal_harga values';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }
        
        // Get all Tokopedia financial transactions
        $transactions = TokopediaFinancialTransaction::with('order.orderItems')->get();
        
        $fixedCount = 0;
        $errorCount = 0;
        
        foreach ($transactions as $transaction) {
            if (!$transaction->order) {
                $this->error("Transaction {$transaction->id} has no associated order");
                $errorCount++;
                continue;
            }
            
            // Calculate correct values from order
            $correctNominalHarga = 0;
            $correctQty = 0;
            foreach ($transaction->order->orderItems as $item) {
                $correctNominalHarga += $item->price_after_discount * $item->quantity;
                $correctQty += $item->quantity;
            }
            
            // Check if values are incorrect (allow for small floating point differences)
            $nominalHargaDiff = abs($transaction->nominal_harga - $correctNominalHarga);
            $qtyDiff = abs($transaction->qty - $correctQty);
            
            if ($nominalHargaDiff > 1 || $qtyDiff > 0.1) {
                $this->warn("Transaction {$transaction->id} (Order: {$transaction->no_order}) has incorrect values:");
                $this->line("  Current Nominal Harga: " . number_format($transaction->nominal_harga, 2));
                $this->line("  Correct Nominal Harga: " . number_format($correctNominalHarga, 2));
                $this->line("  Current Quantity: " . $transaction->qty);
                $this->line("  Correct Quantity: " . $correctQty);
                
                if (!$dryRun) {
                    try {
                        DB::beginTransaction();
                        
                        // Update with correct values
                        $transaction->nominal_harga = $correctNominalHarga;
                        $transaction->qty = $correctQty;
                        
                        // Recalculate nominal_fix
                        $transaction->calculateNominalFix();
                        
                        // If saldo_masuk is way off, adjust it to match nominal_fix
                        if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
                            $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
                            if ($ratio > 5.0 || $ratio < 0.2) {
                                $this->warn("  Adjusting saldo_masuk from " . number_format($transaction->saldo_masuk, 2) . " to " . number_format($transaction->nominal_fix, 2));
                                $transaction->saldo_masuk = $transaction->nominal_fix;
                            }
                        }
                        
                        $transaction->calculateOutstanding();
                        $transaction->calculatePercentages();
                        
                        $transaction->save();
                        
                        DB::commit();
                        
                        $this->info("  ✓ Fixed transaction {$transaction->id}");
                        $fixedCount++;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("  ✗ Error fixing transaction {$transaction->id}: " . $e->getMessage());
                        $errorCount++;
                    }
                } else {
                    $this->info("  Would fix transaction {$transaction->id}");
                    $fixedCount++;
                }
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Total transactions processed: " . $transactions->count());
        $this->info("Transactions " . ($dryRun ? 'that would be fixed' : 'fixed') . ": " . $fixedCount);
        if ($errorCount > 0) {
            $this->error("Errors encountered: " . $errorCount);
        }
        
        if ($dryRun && $fixedCount > 0) {
            $this->info("\nRun without --dry-run to apply the fixes");
        }
        
        return 0;
    }
} 
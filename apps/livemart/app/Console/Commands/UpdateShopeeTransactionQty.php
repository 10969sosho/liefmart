<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeFinancialTransaction;
use App\Models\BarangKeluar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateShopeeTransactionQty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:update-qty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update qty field for all ShopeeFinancialTransaction records based on BarangKeluar data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to update ShopeeFinancialTransaction qty field...');
        
        // Get all shopee financial transactions
        $transactions = ShopeeFinancialTransaction::whereNull('qty')
            ->orWhere('qty', 0)
            ->get();
        
        $this->info("Found {$transactions->count()} transactions with empty qty field.");
        
        $progressBar = $this->output->createProgressBar($transactions->count());
        $progressBar->start();
        
        $updatedCount = 0;
        
        foreach ($transactions as $transaction) {
            try {
                // Extract tax_id from invoice number
                $taxId = null;
                
                if (strpos($transaction->no_invoice, 'HPNSDA-OLK/01') !== false) {
                    $taxId = 1; // PKP - Coffee
                } elseif (strpos($transaction->no_invoice, 'HPNSDA-OLK/02') !== false) {
                    $taxId = 2; // Non PKP - Coffee
                } elseif (strpos($transaction->no_invoice, 'AMP/01') !== false) {
                    $taxId = 3; // PKP - Skincare
                } elseif (strpos($transaction->no_invoice, 'AMP/02') !== false) {
                    $taxId = 4; // Non PKP - Skincare
                }
                
                // If no order_id, try to find it
                if (!$transaction->order_id && $transaction->no_order) {
                    $order = \App\Models\Order::where('order_number', $transaction->no_order)->first();
                    if ($order) {
                        $transaction->order_id = $order->id;
                        $transaction->save();
                    }
                }
                
                // Get BarangKeluar for this order and tax_id
                if ($transaction->order_id) {
                    // Get all BarangKeluar for this order
                    $barangKeluarItems = BarangKeluar::whereHas('orderItem', function($query) use ($transaction) {
                        $query->where('order_id', $transaction->order_id);
                    })->with(['orderItem.warehouseStock'])->get();
                    
                    $totalQty = 0;
                    
                    // Calculate total qty for matching tax_id
                    foreach ($barangKeluarItems as $bk) {
                        if ($bk->orderItem && $bk->orderItem->warehouseStock && $bk->orderItem->warehouseStock->tax_id == $taxId) {
                            $totalQty += $bk->qty;
                        }
                    }
                    
                    // If no specific tax items found, use order items from the transaction
                    if ($totalQty == 0 && $transaction->order && $taxId) {
                        $orderItems = $transaction->order->orderItems()
                            ->whereHas('warehouseStock', function($query) use ($taxId) {
                                $query->where('tax_id', $taxId);
                            })
                            ->get();
                            
                        foreach ($orderItems as $item) {
                            $totalQty += $item->quantity;
                        }
                    }
                    
                    // If still no specific tax items, use all order items
                    if ($totalQty == 0 && $transaction->order) {
                        $totalQty = $transaction->order->orderItems->sum('quantity');
                    }
                    
                    // Update the transaction qty
                    if ($totalQty > 0) {
                        $transaction->qty = $totalQty;
                        $transaction->save();
                        $updatedCount++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error updating qty for transaction ID {$transaction->id}: " . $e->getMessage());
                $this->error("Error with transaction {$transaction->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        $this->info("Successfully updated {$updatedCount} transactions.");
        
        return Command::SUCCESS;
    }
}

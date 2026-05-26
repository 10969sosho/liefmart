<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeFinancialTransaction;
use App\Http\Controllers\Finance\PembayaranShopeeController;
use Illuminate\Support\Facades\Log;

class UpdateShopeeInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:update-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing Shopee invoices to the new format';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new PembayaranShopeeController();
        $transactions = ShopeeFinancialTransaction::whereNotNull('order_id')->get();
        
        $this->info("Found {$transactions->count()} transactions to update.");
        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();
        
        $updated = 0;
        
        foreach ($transactions as $transaction) {
            $oldInvoice = $transaction->no_invoice;
            
            // Skip if invoice is already in the new format
            if (preg_match('/^\d{6}\/\d{4}\/[A-Z-]+\/\d{2}$/', $oldInvoice)) {
                $bar->advance();
                continue;
            }
            
            if ($transaction->order) {
                try {
                    // Use the controller's method to generate a new invoice number
                    $reflectionMethod = new \ReflectionMethod($controller, 'generateInvoiceForOrder');
                    $reflectionMethod->setAccessible(true);
                    $newInvoice = $reflectionMethod->invoke($controller, $transaction->order);
                    
                    $transaction->no_invoice = $newInvoice;
                    $transaction->save();
                    
                    Log::info("Updated invoice from {$oldInvoice} to {$newInvoice} for transaction {$transaction->id}");
                    $updated++;
                } catch (\Exception $e) {
                    Log::error("Error updating invoice for transaction {$transaction->id}: " . $e->getMessage());
                    $this->error("Error updating transaction {$transaction->id}: " . $e->getMessage());
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Updated {$updated} invoice numbers to the new format.");
        
        return 0;
    }
} 
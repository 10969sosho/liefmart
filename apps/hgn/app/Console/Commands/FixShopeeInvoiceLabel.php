<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopeeFinancialTransaction;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

class FixShopeeInvoiceLabel extends Command
{
    protected $signature = 'shopee:fix-invoice-label {order_number}';
    protected $description = 'Fix invoice label (PKP/Non-PKP) for a specific Shopee order transaction';

    public function handle()
    {
        $orderNumber = $this->argument('order_number');
        $this->info("Checking Order: $orderNumber");

        $transactions = ShopeeFinancialTransaction::where('no_order', $orderNumber)->get();

        if ($transactions->isEmpty()) {
            $this->error("No transactions found for order $orderNumber");
            return 1;
        }

        foreach ($transactions as $trx) {
            $this->info("Transaction ID: {$trx->id} | Invoice: {$trx->no_invoice} | Nominal: {$trx->nominal_harga}");
            
            // Logic to identify HGN (Tax ID 3) transaction
            // We know from analysis that 103500 is the HGN item
            if ($trx->nominal_harga == 103500) {
                $this->info("  -> Identified as HGN Item (Tax ID 3).");
                
                if (str_ends_with($trx->no_invoice, '/02')) {
                    $this->warn("  -> Currently labeled as Non-PKP (/02). Should be PKP (/01).");
                    
                    if ($this->confirm("Do you want to regenerate this invoice number to PKP (/01)?", true)) {
                        $this->regenerateInvoice($trx);
                    }
                } else {
                    $this->info("  -> Already labeled as PKP (/01). No action needed.");
                }
            } else {
                 $this->info("  -> Assumed LM/Other (Tax ID 4). Status /02 is likely correct.");
            }
        }
        
        return 0;
    }

    private function regenerateInvoice($trx)
    {
        DB::beginTransaction();
        try {
            // Params for HGN (Tax ID 3) -> Skincare, Online, PKP
            $category = InvoiceSequence::CATEGORY_SKINCARE;
            $salesType = InvoiceSequence::SALES_ONLINE;
            $taxStatus = InvoiceSequence::TAX_PKP; // This triggers /01
            $date = $trx->tanggal_order; // Or transaction date? Using order date as per logic.

            $newInvoiceData = InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus, $date);
            $newInvoiceNumber = $newInvoiceData['invoice_number'];

            $this->info("  -> Old Invoice: {$trx->no_invoice}");
            $this->info("  -> New Invoice: $newInvoiceNumber");

            $trx->no_invoice = $newInvoiceNumber;
            $trx->save();
            
            DB::commit();
            $this->info("  -> Successfully updated invoice number.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  -> Failed to update: " . $e->getMessage());
        }
    }
}

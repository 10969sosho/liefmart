<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\ShopeeFinancialTransaction;
use App\Models\BarangKeluar;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResyncShopeeInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:resync-invoices 
                            {--dry-run : Run without making changes}
                            {--order-id= : Process specific order ID only}
                            {--force : Force regenerate even if invoice exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync Shopee invoices to create separate invoices for each tax_id (PKP and Non-PKP)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $orderId = $this->option('order-id');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting Shopee invoice resync...');
        $this->newLine();

        // Get orders that need resync
        $query = Order::where('platform_id', 1) // Shopee platform
            ->whereHas('orderItems.barangKeluar.warehouseStock', function($q) {
                $q->whereNotNull('tax_id');
            })
            ->with(['orderItems.barangKeluar.warehouseStock']);

        if ($orderId) {
            $query->where('id', $orderId);
        }

        $orders = $query->get();

        $this->info("Found {$orders->count()} orders to process");
        $this->newLine();

        $processed = 0;
        $skipped = 0;
        $errors = 0;
        $created = 0;

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            try {
                // Group barang keluar by tax_id
                $barangKeluarItems = BarangKeluar::whereHas('orderItem', function($q) use ($order) {
                    $q->where('order_id', $order->id);
                })->with('warehouseStock')->get();

                $taxGroups = [];
                foreach ($barangKeluarItems as $bk) {
                    if ($bk->warehouseStock && $bk->warehouseStock->tax_id) {
                        $taxId = $bk->warehouseStock->tax_id;
                        if (!isset($taxGroups[$taxId])) {
                            $taxGroups[$taxId] = [];
                        }
                        $taxGroups[$taxId][] = $bk;
                    }
                }

                if (empty($taxGroups)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Check existing transactions
                $existingTransactions = ShopeeFinancialTransaction::where('order_id', $order->id)->get();
                
                // Check if we need to create missing invoices
                $existingTaxIds = [];
                foreach ($existingTransactions as $trans) {
                    // Extract tax_id from invoice number
                    $taxId = $this->extractTaxIdFromInvoice($trans->no_invoice);
                    if ($taxId) {
                        $existingTaxIds[] = $taxId;
                    }
                }

                $missingTaxIds = array_diff(array_keys($taxGroups), $existingTaxIds);

                if (empty($missingTaxIds) && !$force) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // If force mode, delete existing transactions first
                if ($force && $existingTransactions->count() > 0) {
                    if (!$dryRun) {
                        ShopeeFinancialTransaction::where('order_id', $order->id)->delete();
                        $this->line("  Deleted existing transactions for order {$order->order_number}");
                    } else {
                        $this->line("  [DRY RUN] Would delete existing transactions for order {$order->order_number}");
                    }
                    $missingTaxIds = array_keys($taxGroups);
                }

                // Get existing transaction data to copy (if any)
                $existingTrans = $existingTransactions->first();
                
                if (!$existingTrans && !$force) {
                    $skipped++;
                    $this->warn("  Order {$order->order_number} has no existing transaction to replicate");
                    $bar->advance();
                    continue;
                }

                // Calculate total nominal for proportion calculation
                $totalNominal = $order->orderItems->sum(function($item) {
                    return $item->price_after_discount * $item->quantity;
                });

                // Create invoices for all tax_ids
                foreach ($taxGroups as $taxId => $group) {
                    // Check if invoice for this tax_id already exists
                    $hasInvoice = false;
                    foreach ($existingTransactions as $trans) {
                        $transTaxId = $this->extractTaxIdFromInvoice($trans->no_invoice);
                        if ($transTaxId == $taxId) {
                            $hasInvoice = true;
                            break;
                        }
                    }

                    if ($hasInvoice && !$force) {
                        continue; // Skip if invoice already exists
                    }

                    if (!$dryRun) {
                        // Calculate qty and nominal for this tax_id
                        $groupQty = 0;
                        $groupNominal = 0;
                        foreach ($group as $bk) {
                            $groupQty += $bk->qty;
                            if ($bk->orderItem) {
                                $groupNominal += $bk->orderItem->price_after_discount * $bk->orderItem->quantity;
                            }
                        }
                        
                        // Calculate proportion
                        $proportion = $totalNominal > 0 ? ($groupNominal / $totalNominal) : (1 / count($taxGroups));
                        
                        // Create new transaction
                        $newTrans = new ShopeeFinancialTransaction();
                        $newTrans->tanggal_order = $order->tanggal;
                        $newTrans->hari_order = $order->hari;
                        $newTrans->no_order = $order->order_number;
                        $newTrans->order_id = $order->id;
                        $newTrans->qty = $groupQty;
                        $newTrans->nominal_harga = $groupNominal;
                        
                        // Distribute discounts and payment proportionally
                        if ($existingTrans) {
                            $newTrans->nominal_diskon1 = $existingTrans->nominal_diskon1 * $proportion;
                            $newTrans->nominal_diskon2 = $existingTrans->nominal_diskon2 * $proportion;
                            $newTrans->nominal_diskon3 = $existingTrans->nominal_diskon3 * $proportion;
                            $newTrans->nominal_diskon4 = $existingTrans->nominal_diskon4 * $proportion;
                            $newTrans->nominal_diskon5 = $existingTrans->nominal_diskon5 * $proportion;
                            $newTrans->nominal_diskon6 = $existingTrans->nominal_diskon6 * $proportion;
                            $newTrans->nominal_diskon7 = $existingTrans->nominal_diskon7 * $proportion;
                            $newTrans->nominal_diskon8 = $existingTrans->nominal_diskon8 * $proportion;
                            $newTrans->nominal_diskon9 = $existingTrans->nominal_diskon9 * $proportion;
                            $newTrans->nominal_diskon10 = $existingTrans->nominal_diskon10 * $proportion;
                            $newTrans->nominal_diskon11 = $existingTrans->nominal_diskon11 * $proportion;
                            $newTrans->nominal_diskon12 = $existingTrans->nominal_diskon12 * $proportion;
                            $newTrans->adjustment = $existingTrans->adjustment * $proportion;
                            $newTrans->adjustment_description = $existingTrans->adjustment_description;
                            $newTrans->saldo_masuk = $existingTrans->saldo_masuk * $proportion;
                            $newTrans->tanggal_masuk_pembayaran = $existingTrans->tanggal_masuk_pembayaran;
                            $newTrans->hari_masuk_pembayaran = $existingTrans->hari_masuk_pembayaran;
                            $newTrans->is_locked = $existingTrans->is_locked;
                        } else {
                            // No existing transaction, set defaults
                            $newTrans->nominal_diskon1 = 0;
                            $newTrans->nominal_diskon2 = 0;
                            $newTrans->nominal_diskon3 = 0;
                            $newTrans->nominal_diskon4 = 0;
                            $newTrans->nominal_diskon5 = 0;
                            $newTrans->nominal_diskon6 = 0;
                            $newTrans->nominal_diskon7 = 0;
                            $newTrans->nominal_diskon8 = 0;
                            $newTrans->nominal_diskon9 = 0;
                            $newTrans->nominal_diskon10 = 0;
                            $newTrans->nominal_diskon11 = 0;
                            $newTrans->nominal_diskon12 = 0;
                            $newTrans->adjustment = 0;
                            $newTrans->saldo_masuk = 0;
                        }
                        
                        // Generate invoice number for this tax_id
                        $newTrans->no_invoice = ShopeeFinancialTransaction::generateInvoiceNumber($order, $taxId);
                        
                        // Calculate values
                        $newTrans->calculateNominalFix()
                                 ->calculateOutstanding()
                                 ->calculatePercentages();
                        
                        $newTrans->save();
                        $created++;
                        
                        $taxCategory = in_array($taxId, [1, 3, 5, 7]) ? 'PKP' : 'Non-PKP';
                        $this->line("  Created invoice {$newTrans->no_invoice} for order {$order->order_number} ({$taxCategory})");
                    } else {
                        $this->line("  [DRY RUN] Would create invoice for order {$order->order_number}, tax_id: {$taxId}");
                        $created++;
                    }
                }

                $processed++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Error processing order {$order->order_number}: " . $e->getMessage());
                Log::error("ResyncShopeeInvoices error for order {$order->order_number}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Resync completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Processed', $processed],
                ['Created', $created],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    /**
     * Extract tax_id from invoice number
     */
    private function extractTaxIdFromInvoice($invoiceNumber)
    {
        if (!$invoiceNumber) {
            return null;
        }

        // Invoice format: 000002/2508/AMP/01 or 000002/2508/AMP/02
        // Last part is tax code: 01 = PKP, 02 = Non-PKP
        if (preg_match('/\/(\d{2})$/', $invoiceNumber, $matches)) {
            $taxCode = $matches[1];
            
            // Map tax code to tax_id
            // 01 = PKP (tax_id 1, 3, 5, 7)
            // 02 = Non-PKP (tax_id 2, 4, 6, 8)
            
            if (strpos($invoiceNumber, 'AMP/01') !== false) {
                // Skincare PKP
                return 3;
            } elseif (strpos($invoiceNumber, 'AMP/02') !== false) {
                // Skincare Non-PKP
                return 4;
            }
        }

        return null;
    }
}


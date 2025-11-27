<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturPenjualan;
use App\Models\ReturOfflineSale;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TokopediaFinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
use App\Models\FinanceOffline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReturFinanceService
{
    /**
     * Handle finance logic for online return
     * 
     * @param ReturPenjualan $returPenjualan
     * @param float $refundAmount
     * @param float $additionalDeduction (ongkir, etc)
     * @return void
     */
    public function handleOnlineReturFinance(ReturPenjualan $returPenjualan, float $refundAmount = null, float $additionalDeduction = 0)
    {
        $order = $returPenjualan->order;
        
        // Calculate refund amount if not provided
        if ($refundAmount === null) {
            $refundAmount = $this->calculateReturRefundAmount($returPenjualan);
        }
        
        // Get original order total
        $originalOrderTotal = $this->getOriginalOrderTotal($order);
        
        Log::info("Processing retur finance for order {$order->order_number}", [
            'refund_amount' => $refundAmount,
            'original_total' => $originalOrderTotal,
            'additional_deduction' => $additionalDeduction
        ]);
        
        // Determine platform and handle accordingly
        $platform = strtolower($order->platform->name ?? '');
        
        if ($refundAmount >= $originalOrderTotal && $additionalDeduction == 0) {
            // SCENARIO 1: Full refund - remove payment and move to unpaid
            $this->handleFullRefund($order, $platform);
        } else {
            // SCENARIO 2: Partial refund or has additional deduction
            $this->handlePartialRefundWithDeduction($order, $platform, $refundAmount, $originalOrderTotal, $additionalDeduction);
        }
    }
    
    /**
     * Handle finance logic for offline return
     * 
     * @param ReturOfflineSale $returOfflineSale
     * @param float $refundAmount
     * @param float $additionalDeduction
     * @return void
     */
    public function handleOfflineReturFinance(ReturOfflineSale $returOfflineSale, float $refundAmount = null, float $additionalDeduction = 0)
    {
        $offlineSale = $returOfflineSale->offlineSale;
        
        // Calculate refund amount if not provided
        if ($refundAmount === null) {
            $refundAmount = $this->calculateOfflineReturRefundAmount($returOfflineSale);
        }
        
        // Get original sale total
        $originalSaleTotal = $offlineSale->total_amount;
        
        Log::info("Processing offline retur finance for sale {$offlineSale->id}", [
            'refund_amount' => $refundAmount,
            'original_total' => $originalSaleTotal,
            'additional_deduction' => $additionalDeduction
        ]);
        
        if ($refundAmount >= $originalSaleTotal && $additionalDeduction == 0) {
            // SCENARIO 1: Full refund - mark all invoices as returned and create negative entry
            $this->handleOfflineFullRefund($offlineSale);
        } else {
            // SCENARIO 2: Partial refund or has additional deduction
            $this->handleOfflinePartialRefundWithDeduction($offlineSale, $refundAmount, $originalSaleTotal, $additionalDeduction);
        }
    }
    
    /**
     * Handle full refund - remove financial transaction and move to unpaid
     */
    private function handleFullRefund(Order $order, string $platform)
    {
        try {
            DB::beginTransaction();
            
            // Delete existing financial transaction based on platform
            switch ($platform) {
                case 'shopee':
                    ShopeeFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
                case 'tokopedia':
                    TokopediaFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
                case 'tiktok':
                    TiktokFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
                case 'blibli':
                    BlibliFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
            }
            
            Log::info("Deleted financial transaction for order {$order->order_number} - moved to unpaid with RETUR status");
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error handling full refund: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Handle partial refund with deduction - keep in finance with adjusted values
     */
    private function handlePartialRefundWithDeduction(Order $order, string $platform, float $refundAmount, float $originalTotal, float $additionalDeduction)
    {
        try {
            DB::beginTransaction();
            
            // Calculate outstanding (negative for additional deduction)
            $outstanding = -$additionalDeduction;
            
            // Update existing financial transaction
            $updateData = [
                'nominal_harga' => 0, // Set price to 0 since refunded
                'nominal_fix' => 0,   // Set final amount to 0
                'saldo_masuk' => -$additionalDeduction, // Negative cash flow for deduction
                'outstanding' => $outstanding,
                'adjustment' => -$additionalDeduction,
                'adjustment_description' => "Retur dengan potongan tambahan: Rp " . number_format($additionalDeduction, 0, ',', '.')
            ];
            
            switch ($platform) {
                case 'shopee':
                    ShopeeFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'tokopedia':
                    TokopediaFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'tiktok':
                    TiktokFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'blibli':
                    BlibliFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
            }
            
            Log::info("Updated financial transaction for partial refund", [
                'order' => $order->order_number,
                'outstanding' => $outstanding,
                'adjustment' => -$additionalDeduction
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error handling partial refund: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Handle offline full refund
     */
    private function handleOfflineFullRefund($offlineSale)
    {
        try {
            DB::beginTransaction();
            
            // Get all invoices related to this sale
            $invoices = $offlineSale->getInvoices();
            
            foreach ($invoices as $invoice) {
                // Recalculate nominal based on updated subtotals
                $newNominal = $this->recalculateInvoiceNominal($invoice);
                
                // Set invoice values to 0 and mark as retur_full
                $invoice->update([
                    'nominal' => 0,
                    'outstanding' => 0,
                    'status' => 'retur_full',
                    'notes' => 'Retur penuh - invoice dibatalkan'
                ]);
            }
            
            Log::info("Marked offline sale invoices as retur_full for sale {$offlineSale->id}");
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error handling offline full refund: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Handle offline partial refund with deduction
     */
    private function handleOfflinePartialRefundWithDeduction($offlineSale, float $refundAmount, float $originalTotal, float $additionalDeduction)
    {
        try {
            DB::beginTransaction();
            
            // Get invoices related to this sale
            $invoices = $offlineSale->getInvoices();
            
            foreach ($invoices as $invoice) {
                $oldNominal = $invoice->nominal;
                
                Log::info("Processing invoice for partial refund", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'old_nominal' => $oldNominal
                ]);
                
                // Recalculate nominal based on updated subtotals (DPP)
                $newNominalDPP = $this->recalculateInvoiceNominal($invoice);
                
                // Get tax_id from first item - refresh to get latest data
                $firstItem = $invoice->barangKeluarItems()->with('warehouseStock')->first();
                $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id 
                    ? $firstItem->warehouseStock->tax_id 
                    : null;
                
                // Calculate grand total (nominal + PPN)
                $dpp = \App\Helpers\NumberFormatter::calculateDPP($newNominalDPP);
                $grandTotal = $dpp;
                
                if ($taxId == 3) {
                    // PKP: Calculate PPN
                    $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                    $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                    $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
                } else {
                    // Non-PKP: Just round DPP
                    $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
                }
                
                // Update invoice with grand total (nominal + PPN)
                // Set status as 'partial_refund' to indicate nominal already includes PPN
                $updateData = [
                    'nominal' => $grandTotal,
                    'status' => 'partial_refund',
                    'notes' => 'Retur sebagian - nominal sudah termasuk PPN'
                ];
                
                // Only update outstanding if there's additional deduction
                if ($additionalDeduction > 0) {
                    $updateData['outstanding'] = -$additionalDeduction;
                    $updateData['notes'] = 'Retur dengan potongan tambahan: Rp ' . number_format($additionalDeduction, 0, ',', '.') . ' - nominal sudah termasuk PPN';
                }
                
                $invoice->update($updateData);
                
                // Refresh to verify update
                $invoice->refresh();
                
                Log::info("Invoice updated for partial refund", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'old_nominal' => $oldNominal,
                    'new_nominal_dpp' => $newNominalDPP,
                    'new_nominal_grand_total' => $grandTotal,
                    'tax_id' => $taxId,
                    'status' => $invoice->status
                ]);
            }
            
            Log::info("Updated offline sale invoices for partial refund", [
                'sale_id' => $offlineSale->id,
                'additional_deduction' => $additionalDeduction
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error handling offline partial refund: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate refund amount for online return
     */
    private function calculateReturRefundAmount(ReturPenjualan $returPenjualan): float
    {
        $totalRefund = 0;
        
        foreach ($returPenjualan->details as $detail) {
            $orderItem = $detail->orderItem;
            if ($orderItem) {
                $itemRefund = $orderItem->price_after_discount * $detail->qty;
                $totalRefund += $itemRefund;
            }
        }
        
        return $totalRefund;
    }
    
    /**
     * Calculate refund amount for offline return
     */
    private function calculateOfflineReturRefundAmount(ReturOfflineSale $returOfflineSale): float
    {
        $totalRefund = 0;
        
        foreach ($returOfflineSale->details as $detail) {
            // Get the offline sale item through the relationship
            $offlineSaleItem = \App\Models\OfflineSaleItem::find($detail->offline_sale_item_id);
            if ($offlineSaleItem) {
                $itemRefund = $offlineSaleItem->unit_price * $detail->qty;
                $totalRefund += $itemRefund;
            }
        }
        
        return $totalRefund;
    }
    
    /**
     * Get original order total
     */
    private function getOriginalOrderTotal(Order $order): float
    {
        $total = $order->orderItems->sum(function($item) {
            return $item->price_after_discount * $item->quantity;
        });
        
        // Add shipping cost if exists
        $total += $order->shipping_cost ?? 0;
        
        return $total;
    }
    
    /**
     * Recalculate invoice nominal based on updated subtotals
     */
    private function recalculateInvoiceNominal(FinanceOffline $invoice): float
    {
        // Refresh invoice to get latest data
        $invoice->refresh();
        
        // Get all barang keluar items for this invoice with fresh data
        $barangKeluarItems = $invoice->barangKeluarItems()->with('offlineSaleItem')->get();
        
        $totalNominal = 0;
        
        foreach ($barangKeluarItems as $barangKeluar) {
            if ($barangKeluar->offlineSaleItem) {
                // Refresh offlineSaleItem to get updated subtotal
                $barangKeluar->offlineSaleItem->refresh();
                // Use the updated subtotal from offlineSaleItem
                $totalNominal += $barangKeluar->offlineSaleItem->subtotal ?? 0;
            }
        }
        
        Log::info("Recalculated invoice nominal", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'new_nominal_dpp' => $totalNominal
        ]);
        
        return $totalNominal;
    }
}
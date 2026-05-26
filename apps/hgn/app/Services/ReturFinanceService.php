<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturPenjualan;
use App\Models\ReturOfflineSale;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TokopediaFinancialTransaction;
use App\Models\Shopee2FinancialTransaction;
use App\Models\Tiktok2FinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
use App\Models\FinanceOffline;
use App\Models\MappingBarang;
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
        
        // Determine platform
        $platform = $this->normalizePlatform(strtolower($order->platform->name ?? ''));
        
        // Get original order total (Current Total in Finance)
        // We prioritize getting this from the existing Financial Transaction to ensure consistency
        $financeTotal = $this->getCurrentFinanceTotal($order, $platform);
        
        // Calculate theoretical total from Order Items
        $orderItemsTotal = $this->getOriginalOrderTotal($order);
        
        // Check for previous returns
        $previousReturnsCount = ReturPenjualan::where('order_id', $order->id)
            ->where('status', 'selesai')
            ->where('id', '!=', $returPenjualan->id)
            ->count();
            
        // Use Finance Total normally, but validate if suspicious (e.g. Finance Total is significantly less than Order Total on first return)
        $originalOrderTotal = $financeTotal;
        
        if ($previousReturnsCount == 0 && $financeTotal > 0 && $financeTotal < ($orderItemsTotal - 1)) {
             Log::warning("Finance total mismatch for order {$order->order_number}. Finance: $financeTotal, Order: $orderItemsTotal. Using Order Total as base.");
             $originalOrderTotal = $orderItemsTotal;
        }
        
        // Fallback if no transaction found (e.g. not yet generated)
        if ($originalOrderTotal <= 0) {
             // If we can't find it in finance, we reconstruct it from current items + refund amount
             // This assumes order items have already been reduced by the return
             $currentItemsTotal = $this->getOriginalOrderTotal($order); 
             $originalOrderTotal = $currentItemsTotal + $refundAmount;
        }
        
        Log::info("Processing retur finance for order {$order->order_number}", [
            'refund_amount' => $refundAmount,
            'original_total_from_finance' => $originalOrderTotal,
            'additional_deduction' => $additionalDeduction
        ]);
        
        // Check if this is a full quantity return
        $isFullQtyReturn = $this->checkIfFullQtyReturn($returPenjualan);

        if ($isFullQtyReturn && $additionalDeduction == 0) {
            $this->handleFullRefund($order, $platform);
        } else {
            $this->handlePartialRefundWithDeduction($order, $returPenjualan, $platform, $refundAmount, $originalOrderTotal, $additionalDeduction);
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
     * Get current total from financial transaction
     */
    private function getCurrentFinanceTotal(Order $order, string $platform): float
    {
        $platform = $this->normalizePlatform($platform);
        switch ($platform) {
            case 'shopee':
                return (float) ShopeeFinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
            case 'shopee2':
                return (float) Shopee2FinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
            case 'tokopedia':
                return (float) TokopediaFinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
            case 'tiktok':
                return (float) TiktokFinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
            case 'tiktok2':
                return (float) Tiktok2FinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
            case 'blibli':
                return (float) BlibliFinancialTransaction::where('order_id', $order->id)->sum('nominal_harga');
        }
        return 0.0;
    }

    /**
     * Handle full refund - remove financial transaction and move to unpaid
     */
    private function handleFullRefund(Order $order, string $platform)
    {
        try {
            DB::beginTransaction();
            
            $platform = $this->normalizePlatform($platform);
            // Delete existing financial transaction based on platform
            switch ($platform) {
                case 'shopee':
                    ShopeeFinancialTransaction::where(function ($q) use ($order) {
                        $q->where('order_id', $order->id)
                          ->orWhere('no_order', $order->order_number)
                          ->orWhereRaw('TRIM(no_order) = ?', [trim((string) $order->order_number)]);
                    })->delete();
                    break;
                case 'shopee2':
                    Shopee2FinancialTransaction::where(function ($q) use ($order) {
                        $q->where('order_id', $order->id)
                          ->orWhere('no_order', $order->order_number)
                          ->orWhereRaw('TRIM(no_order) = ?', [trim((string) $order->order_number)]);
                    })->delete();
                    break;
                case 'tokopedia':
                    TokopediaFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
                case 'tiktok':
                    TiktokFinancialTransaction::where('order_id', $order->id)->delete();
                    break;
                case 'tiktok2':
                    Tiktok2FinancialTransaction::where('order_id', $order->id)->delete();
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
    private function handlePartialRefundWithDeduction(Order $order, ReturPenjualan $returPenjualan, string $platform, float $refundAmount, float $originalTotal, float $additionalDeduction)
    {
        try {
            DB::beginTransaction();

            $platform = $this->normalizePlatform($platform);
            if (in_array($platform, ['tiktok', 'tiktok2'], true)) {
                $modelClass = $platform === 'tiktok' ? TiktokFinancialTransaction::class : Tiktok2FinancialTransaction::class;

                $order->loadMissing('orderItems.platformProduct');
                $returPenjualan->loadMissing('details');

                $transactions = $modelClass::where('order_id', $order->id)->get();
                if ($transactions->isEmpty()) {
                    $transactions = $modelClass::where('no_order', $order->order_number)->get();
                }

                $discountFields = [
                    'nominal_diskon1',
                    'nominal_diskon2',
                    'nominal_diskon3',
                    'nominal_diskon4',
                    'nominal_diskon5',
                    'nominal_diskon6',
                    'nominal_diskon7',
                    'nominal_diskon8',
                    'nominal_diskon9',
                    'nominal_diskon10',
                    'nominal_diskon11',
                    'nominal_diskon12',
                ];

                $marker = 'retur_id:' . $returPenjualan->id;
                $amountText = "Retur sebagian: Rp " . number_format($refundAmount, 0, ',', '.');

                if ($transactions->isEmpty()) {
                    $newTx = new $modelClass();
                    $newTx->setDataFromOrder($order);
                    $newTx->no_invoice = null;
                    $newTx->saldo_masuk = 0;
                    $newTx->adjustment = 0 - $additionalDeduction;
                    $newTx->adjustment_description = $amountText .
                        ($additionalDeduction > 0 ? " dengan potongan tambahan: Rp " . number_format($additionalDeduction, 0, ',', '.') : '') .
                        " | " . $marker;
                    $newTx->calculateNominalFix();
                    $newTx->calculateOutstanding();
                    $newTx->calculatePercentages();
                    $newTx->save();

                    DB::commit();
                    return;
                }

                $hasMarker = $transactions->contains(function ($t) use ($marker) {
                    return str_contains((string) ($t->adjustment_description ?? ''), $marker);
                });
                if ($hasMarker) {
                    DB::commit();
                    return;
                }

                $remainingNominalFromOrder = (float) $order->orderItems->sum(function ($item) {
                    return ((float) ($item->price_after_discount ?? 0)) * ((float) ($item->quantity ?? 0));
                });

                $totalNominalBefore = (float) $transactions->sum('nominal_harga');
                $totalNominalAfter = max(0, $remainingNominalFromOrder);
                $transactionCount = max(1, $transactions->count());

                foreach ($transactions as $transaction) {
                    if ((int) ($transaction->order_id ?? 0) !== (int) $order->id) {
                        $transaction->order_id = $order->id;
                    }

                    $rowNominalBefore = (float) ($transaction->nominal_harga ?? 0);
                    $rowShare = 0.0;

                    if ($totalNominalBefore > 0 && $rowNominalBefore > 0) {
                        $rowShare = $rowNominalBefore / $totalNominalBefore;
                    } else {
                        $rowShare = 1 / $transactionCount;
                    }

                    $rowNominalAfter = round($totalNominalAfter * $rowShare, 2);
                    $rowAdditionalDeduction = $additionalDeduction * $rowShare;
                    $rowRefund = $refundAmount * $rowShare;

                    $oldNominalFix = (float) ($transaction->nominal_fix ?? 0);
                    $paidRate = $oldNominalFix > 0 ? ((float) ($transaction->saldo_masuk ?? 0) / $oldNominalFix) : 0.0;

                    $rates = [];
                    foreach ($discountFields as $field) {
                        $rates[$field] = $rowNominalBefore != 0.0 ? (((float) ($transaction->{$field} ?? 0)) / $rowNominalBefore) : 0.0;
                    }

                    $oldQty = (float) ($transaction->qty ?? 0);
                    $qtyRate = $rowNominalBefore > 0 ? ($oldQty / $rowNominalBefore) : 0.0;

                    $transaction->nominal_harga = $rowNominalAfter;

                    foreach ($discountFields as $field) {
                        $transaction->{$field} = round($rates[$field] * $rowNominalAfter, 2);
                    }

                    $transaction->qty = round($qtyRate * $rowNominalAfter, 2);
                    $transaction->adjustment = round(((float) ($transaction->adjustment ?? 0)) - $rowAdditionalDeduction, 2);

                    $existingDescription = (string) ($transaction->adjustment_description ?? '');
                    $appendDescription = $amountText .
                        ($rowAdditionalDeduction > 0 ? " dengan potongan tambahan: Rp " . number_format($rowAdditionalDeduction, 0, ',', '.') : '') .
                        " | " . $marker;
                    $transaction->adjustment_description = $existingDescription
                        ? ($existingDescription . ' | ' . $appendDescription)
                        : $appendDescription;

                    $transaction->calculateNominalFix();
                    $transaction->saldo_masuk = round($paidRate * (float) ($transaction->nominal_fix ?? 0), 2);
                    $transaction->calculateOutstanding();
                    $transaction->calculatePercentages();
                    $transaction->save();
                }

                DB::commit();
                return;
            }

            if (in_array($platform, ['shopee', 'shopee2'], true)) {
                $modelClass = $platform === 'shopee' ? ShopeeFinancialTransaction::class : Shopee2FinancialTransaction::class;

                $order->loadMissing('orderItems.platformProduct');
                $returPenjualan->loadMissing('details');

                $transactions = $modelClass::where('order_id', $order->id)->get();
                if ($transactions->isEmpty()) {
                    $transactions = $modelClass::where('no_order', $order->order_number)->get();
                }

                if ($transactions->isEmpty()) {
                    $newTx = new $modelClass();
                    $newTx->setDataFromOrder($order);
                    $newTx->no_invoice = null;
                    $newTx->saldo_masuk = 0;
                    $newTx->adjustment = 0 - $additionalDeduction;
                    $newTx->adjustment_description = "Retur sebagian: Rp " . number_format($refundAmount, 0, ',', '.') .
                        ($additionalDeduction > 0 ? " dengan potongan tambahan: Rp " . number_format($additionalDeduction, 0, ',', '.') : '');
                    $newTx->calculateNominalFix();
                    $newTx->calculateOutstanding();
                    $newTx->calculatePercentages();
                    $newTx->save();

                    Log::info("Created financial transaction for partial refund", [
                        'order' => $order->order_number,
                        'platform' => $platform,
                        'nominal_harga' => $newTx->nominal_harga,
                        'nominal_fix' => $newTx->nominal_fix,
                    ]);

                    DB::commit();
                    return;
                }

                $remainingNominalFromOrder = (float) $order->orderItems->sum(function ($item) {
                    return ((float) ($item->price_after_discount ?? 0)) * ((float) ($item->quantity ?? 0));
                });

                $discountFields = $platform === 'shopee'
                    ? ['nominal_diskon1','nominal_diskon2','nominal_diskon3','nominal_diskon4','nominal_diskon5','nominal_diskon6','nominal_diskon7','nominal_diskon8','nominal_diskon9','nominal_diskon10','nominal_diskon11','nominal_diskon12']
                    : ['nominal_diskon1','nominal_diskon2','nominal_diskon3','nominal_diskon4','nominal_diskon5','nominal_diskon6'];

                $transactionCount = max(1, $transactions->count());
                $marker = 'retur_id:' . $returPenjualan->id;
                $amountText = "Retur sebagian: Rp " . number_format($refundAmount, 0, ',', '.');
                $hasMarker = $transactions->contains(function ($t) use ($marker) {
                    return str_contains((string) ($t->adjustment_description ?? ''), $marker);
                });

                if ($hasMarker) {
                    [$weightBefore, $weightAfter] = $this->calculateOrderWeightAndUnits($order, $returPenjualan, true);
                    $weightRatio = $weightBefore > 0 ? ($weightAfter / $weightBefore) : 1.0;

                    if ($weightRatio > 0 && $weightRatio < 0.9999) {
                        foreach ($transactions as $transaction) {
                            $desc = (string) ($transaction->adjustment_description ?? '');
                            $occ = $amountText !== '' ? substr_count($desc, $amountText) : 0;

                            if ($occ <= 1) {
                                continue;
                            }

                            $factor = pow(1 / $weightRatio, $occ - 1);

                            foreach ($discountFields as $field) {
                                $transaction->{$field} = round(((float) ($transaction->{$field} ?? 0)) * $factor, 2);
                            }

                            $transaction->saldo_masuk = round(((float) ($transaction->saldo_masuk ?? 0)) * $factor, 2);
                            $transaction->qty = round(((float) ($transaction->qty ?? 0)) * $factor, 2);
                            $transaction->calculateNominalFix();
                            $transaction->calculateOutstanding();
                            $transaction->calculatePercentages();
                            $transaction->save();
                        }
                    }

                    DB::commit();
                    return;
                }

                $totalNominalBefore = (float) $transactions->sum('nominal_harga');
                $totalNominalAfter = max(0, $remainingNominalFromOrder);

                foreach ($transactions as $transaction) {
                    if ((int) ($transaction->order_id ?? 0) !== (int) $order->id) {
                        $transaction->order_id = $order->id;
                    }

                    $rowNominalBefore = (float) ($transaction->nominal_harga ?? 0);
                    $rowShare = 0.0;

                    if ($totalNominalBefore > 0 && $rowNominalBefore > 0) {
                        $rowShare = $rowNominalBefore / $totalNominalBefore;
                    } else {
                        $rowShare = 1 / $transactionCount;
                    }

                    $rowNominalAfter = round($totalNominalAfter * $rowShare, 2);
                    $rowAdditionalDeduction = $additionalDeduction * $rowShare;
                    $rowRefund = $refundAmount * $rowShare;

                    $oldNominalFix = (float) ($transaction->nominal_fix ?? 0);
                    $paidRate = $oldNominalFix > 0 ? ((float) ($transaction->saldo_masuk ?? 0) / $oldNominalFix) : 0.0;

                    $rates = [];
                    foreach ($discountFields as $field) {
                        $rates[$field] = $rowNominalBefore != 0.0 ? (((float) ($transaction->{$field} ?? 0)) / $rowNominalBefore) : 0.0;
                    }

                    $oldQty = (float) ($transaction->qty ?? 0);
                    $qtyRate = $rowNominalBefore > 0 ? ($oldQty / $rowNominalBefore) : 0.0;

                    $transaction->nominal_harga = $rowNominalAfter;

                    foreach ($discountFields as $field) {
                        $transaction->{$field} = round($rates[$field] * $rowNominalAfter, 2);
                    }

                    $transaction->qty = round($qtyRate * $rowNominalAfter, 2);
                    $transaction->adjustment = round(((float) ($transaction->adjustment ?? 0)) - $rowAdditionalDeduction, 2);

                    $existingDescription = (string) ($transaction->adjustment_description ?? '');
                    $appendDescription = $amountText .
                        ($rowAdditionalDeduction > 0 ? " dengan potongan tambahan: Rp " . number_format($rowAdditionalDeduction, 0, ',', '.') : '') .
                        " | " . $marker;
                    $transaction->adjustment_description = $existingDescription
                        ? ($existingDescription . ' | ' . $appendDescription)
                        : $appendDescription;

                    $transaction->calculateNominalFix();
                    $transaction->saldo_masuk = round($paidRate * (float) ($transaction->nominal_fix ?? 0), 2);
                    $transaction->calculateOutstanding();
                    $transaction->calculatePercentages();
                    $transaction->save();
                }

                Log::info("Updated financial transactions for partial refund (weighted)", [
                    'order' => $order->order_number,
                    'platform' => $platform,
                ]);

                DB::commit();
                return;
            }

            $adjustedNominalHarga = max(0, $originalTotal - $refundAmount);

            $existingTransaction = null;
            switch ($platform) {
                case 'tokopedia':
                    $existingTransaction = TokopediaFinancialTransaction::where('order_id', $order->id)->first();
                    break;
                case 'tiktok':
                    $existingTransaction = TiktokFinancialTransaction::where('order_id', $order->id)->first();
                    break;
                case 'tiktok2':
                    $existingTransaction = Tiktok2FinancialTransaction::where('order_id', $order->id)->first();
                    break;
                case 'blibli':
                    $existingTransaction = BlibliFinancialTransaction::where('order_id', $order->id)->first();
                    break;
            }

            if (!$existingTransaction) {
                Log::warning("No existing transaction found for order {$order->order_number} during partial refund");
                DB::rollBack();
                return;
            }

            $totalDiscounts = ($existingTransaction->nominal_diskon1 ?? 0) +
                             ($existingTransaction->nominal_diskon2 ?? 0) +
                             ($existingTransaction->nominal_diskon3 ?? 0) +
                             ($existingTransaction->nominal_diskon4 ?? 0) +
                             ($existingTransaction->nominal_diskon5 ?? 0) +
                             ($existingTransaction->nominal_diskon6 ?? 0) +
                             ($existingTransaction->nominal_diskon7 ?? 0) +
                             ($existingTransaction->nominal_diskon8 ?? 0) +
                             ($existingTransaction->nominal_diskon9 ?? 0) +
                             ($existingTransaction->nominal_diskon10 ?? 0) +
                             ($existingTransaction->nominal_diskon11 ?? 0) +
                             ($existingTransaction->nominal_diskon12 ?? 0);

            $adjustedNominalFix = max(0, $adjustedNominalHarga + $totalDiscounts - $additionalDeduction);
            $outstanding = $adjustedNominalFix - ($existingTransaction->saldo_masuk ?? 0);
            $adjustedQty = $this->calculateAdjustedQuantity($order);

            $updateData = [
                'nominal_harga' => $adjustedNominalHarga,
                'nominal_fix' => $adjustedNominalFix,
                'qty' => $adjustedQty,
                'outstanding' => $outstanding,
                'adjustment' => ($existingTransaction->adjustment ?? 0) - $additionalDeduction,
                'adjustment_description' => ($existingTransaction->adjustment_description ?? '') .
                    ($existingTransaction->adjustment_description ? ' | ' : '') .
                    "Retur sebagian: Rp " . number_format($refundAmount, 0, ',', '.') .
                    ($additionalDeduction > 0 ? " dengan potongan tambahan: Rp " . number_format($additionalDeduction, 0, ',', '.') : '')
            ];

            switch ($platform) {
                case 'tokopedia':
                    TokopediaFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'tiktok':
                    TiktokFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'tiktok2':
                    Tiktok2FinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
                case 'blibli':
                    BlibliFinancialTransaction::where('order_id', $order->id)->update($updateData);
                    break;
            }

            Log::info("Updated financial transaction for partial refund", [
                'order' => $order->order_number,
                'original_total' => $originalTotal,
                'refund_amount' => $refundAmount,
                'adjusted_nominal_harga' => $adjustedNominalHarga,
                'adjusted_nominal_fix' => $adjustedNominalFix,
                'adjusted_qty' => $adjustedQty,
                'outstanding' => $outstanding,
                'adjustment' => $updateData['adjustment']
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error handling partial refund: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate adjusted quantity after returns
     */
    private function calculateAdjustedQuantity(Order $order): float
    {
        if (!$order->relationLoaded('orderItems')) {
            $order->load('orderItems.platformProduct.mappingBarang');
        }
        
        $totalAdjustedQty = 0;
        
        foreach ($order->orderItems as $item) {
            // Current quantity (already reduced by returns)
            $currentQty = (float)($item->quantity ?? 0);
            $totalAdjustedQty += $currentQty;
        }
        
        return $totalAdjustedQty;
    }

    private function calculateOrderWeightAndUnits(Order $order, ReturPenjualan $returPenjualan, bool $includeRetur = true): array
    {
        $order->loadMissing('orderItems.platformProduct');
        $returPenjualan->loadMissing('details');

        $weightBefore = 0.0;
        $weightAfter = 0.0;
        $unitsBefore = 0.0;
        $unitsAfter = 0.0;

        foreach ($order->orderItems as $item) {
            $packageQty = $this->getOrderItemPackageQuantity($item);

            $returnedUnits = 0.0;
            if ($includeRetur) {
                $returnedUnits = (float) $returPenjualan->details
                    ->where('order_item_id', $item->id)
                    ->sum('qty');
            }

            $returnedPackages = $packageQty > 0 ? ($returnedUnits / $packageQty) : $returnedUnits;

            $qtyAfterPackages = (float) ($item->quantity ?? 0);
            $qtyBeforePackages = $qtyAfterPackages + $returnedPackages;

            $unitWeight = 0.0;
            if ($item->platformProduct) {
                $unitWeight = (float) ($item->platformProduct->initial_price ?? 0);
            }
            if ($unitWeight <= 0) {
                $unitWeight = (float) ($item->price_after_discount ?? 0);
            }

            $weightBefore += $unitWeight * $qtyBeforePackages;
            $weightAfter += $unitWeight * $qtyAfterPackages;

            $unitsBefore += $qtyBeforePackages * $packageQty;
            $unitsAfter += $qtyAfterPackages * $packageQty;
        }

        return [$weightBefore, $weightAfter, $unitsBefore, $unitsAfter];
    }

    private function getOrderItemPackageQuantity($orderItem): float
    {
        if (!$orderItem || !$orderItem->platformProduct) {
            return 1.0;
        }

        $orderCreatedAt = $orderItem->order->created_at ?? $orderItem->created_at;
        $mappings = MappingBarang::getMappingsForOrderCreatedAt($orderItem->platformProduct->id, $orderCreatedAt);
        $packageQty = (float) $mappings->sum('quantity');

        return $packageQty > 0 ? $packageQty : 1.0;
    }

    private function normalizePlatform(string $platform): string
    {
        $platform = strtolower(trim($platform));

        if (str_contains($platform, 'shopee') && (str_contains($platform, 'trueblu') || str_contains($platform, 'trubleu'))) {
            return 'shopee2';
        }
        if (str_contains($platform, 'tiktok') && (str_contains($platform, 'trueblu') || str_contains($platform, 'trubleu'))) {
            return 'tiktok2';
        }
        if (str_contains($platform, 'shopee') && str_contains($platform, 'lamourad')) {
            return 'shopee';
        }
        if (str_contains($platform, 'tiktok') && str_contains($platform, 'lamourad')) {
            return 'tiktok';
        }
        if (str_contains($platform, 'shopee2')) {
            return 'shopee2';
        }
        if (str_contains($platform, 'shopee')) {
            return 'shopee';
        }
        if (str_contains($platform, 'tokopedia')) {
            return 'tokopedia';
        }
        if (str_contains($platform, 'tiktok2')) {
            return 'tiktok2';
        }
        if (str_contains($platform, 'tiktok')) {
            return 'tiktok';
        }
        if (str_contains($platform, 'blibli')) {
            return 'blibli';
        }

        return $platform;
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
                
                // Set invoice values to 0 and mark as refunded (retur full)
                $invoice->update([
                    'nominal' => 0,
                    'outstanding' => 0,
                    'status' => 'refunded', // Use 'refunded' to match database enum
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
            
            // Get all returs for this sale
            $returs = \App\Models\ReturOfflineSale::where('offline_sale_id', $offlineSale->id)
                ->where('status', 'selesai')
                ->with('details.offlineSaleItem')
                ->get();
            
            foreach ($invoices as $invoice) {
                $oldNominal = $invoice->nominal;
                
                Log::info("Processing invoice for partial refund", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'old_nominal' => $oldNominal
                ]);
                
                // Calculate DPP Original (before retur) - tetap sama, tidak berubah
                $dppOriginal = $this->calculateDPPOriginal($invoice, $returs);
                
                // Calculate retur amount (DPP yang diretur dengan diskon)
                $returAmount = $this->calculateReturAmountForInvoice($invoice, $returs);
                
                // Calculate NET DPP = DPP Original - Retur Amount
                $netDPP = max(0, $dppOriginal - $returAmount);
                $netDPP = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);
                
                // Get tax_id from first item - refresh to get latest data
                $firstItem = $invoice->barangKeluarItems()->with('warehouseStock')->first();
                $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id 
                    ? $firstItem->warehouseStock->tax_id 
                    : null;
                
                // Calculate grand total from NET DPP (NET DPP + PPN)
                $grandTotal = $netDPP;
                
                if ($taxId == 3) {
                    // PKP: Calculate PPN from NET DPP
                    $netDPP11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($netDPP);
                    $netPPN = \App\Helpers\NumberFormatter::calculatePPN($netDPP11_12);
                    $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($netDPP, $netPPN);
                } else {
                    // Non-PKP: Just round NET DPP
                    $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);
                }
                
                // Update invoice with grand total (NET DPP + PPN)
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
                    'dpp_original' => $dppOriginal,
                    'retur_amount' => $returAmount,
                    'net_dpp' => $netDPP,
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
                // Calculate price per individual unit handling package/mapping
                $pricePerUnit = $orderItem->price_after_discount;
                
                $platformProduct = $orderItem->platformProduct;
                if ($platformProduct) {
                     $orderCreatedAt = $orderItem->order->created_at ?? $orderItem->created_at;
                     $mappings = MappingBarang::getMappingsForOrderCreatedAt($platformProduct->id, $orderCreatedAt);
                     
                     if ($mappings->count() > 0) {
                         $packageQuantity = $mappings->sum('quantity');
                         if ($packageQuantity > 0) {
                             $pricePerUnit = $orderItem->price_after_discount / $packageQuantity;
                         }
                     }
                }
                
                // detail->qty is in individual units
                $itemRefund = $pricePerUnit * $detail->qty;
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
    
    /**
     * Calculate DPP Original (before retur) - tetap sama, tidak berubah
     * Menghitung dari quantity original (current qty + returned qty)
     */
    private function calculateDPPOriginal(FinanceOffline $invoice, $returs): float
    {
        $dppOriginal = 0;
        
        foreach ($invoice->barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem) {
                $osi = $bk->offlineSaleItem;
                $currentQty = $osi->quantity;
                $currentSubtotal = $osi->subtotal ?? 0;
                
                // Calculate returned qty from returs
                $returnedQty = 0;
                foreach ($returs as $retur) {
                    foreach ($retur->details as $detail) {
                        if ($detail->offline_sale_item_id == $osi->id) {
                            $returnedQty += $detail->qty;
                        }
                    }
                }
                
                // Calculate original quantity (before retur)
                $originalQty = $currentQty + $returnedQty;
                
                // Calculate original subtotal
                if ($currentQty > 0) {
                    // Calculate subtotal per unit, then multiply by original qty
                    $subtotalPerUnit = $currentSubtotal / $currentQty;
                    $originalSubtotal = $subtotalPerUnit * $originalQty;
                } else {
                    // If current qty is 0, calculate from unit_price
                    $originalSubtotal = $osi->unit_price * $originalQty;
                }
                
                $dppOriginal += $originalSubtotal;
            }
        }
        
        $dppOriginal = \App\Helpers\NumberFormatter::roundToWholeNumber($dppOriginal);
        
        Log::info("Calculated DPP Original", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'dpp_original' => $dppOriginal
        ]);
        
        return $dppOriginal;
    }
    
    /**
     * Calculate retur amount for invoice (DPP yang diretur dengan diskon)
     */
    private function calculateReturAmountForInvoice(FinanceOffline $invoice, $returs): float
    {
        $returAmount = 0;
        
        foreach ($returs as $retur) {
            foreach ($retur->details as $detail) {
                $offlineSaleItem = $detail->offlineSaleItem;
                if ($offlineSaleItem) {
                    // Check if this item belongs to this invoice
                    $belongsToInvoice = false;
                    foreach ($invoice->barangKeluarItems as $bk) {
                        if ($bk->offlineSaleItem && $bk->offlineSaleItem->id == $offlineSaleItem->id) {
                            $belongsToInvoice = true;
                            break;
                        }
                    }
                    
                    if ($belongsToInvoice) {
                        $qtyRetur = (float)($detail->qty ?? 0);
                        $basePrice = (float)($offlineSaleItem->unit_price ?? 0);
                        
                        // Start with base total (price × qty retur)
                        $currentTotal = $basePrice * $qtyRetur;
                        
                        // Apply percentage discounts (1-5) in cascading order
                        for($i = 1; $i <= 5; $i++) {
                            $percentField = "discount_percent_" . $i;
                            $discountPercent = (float)($offlineSaleItem->$percentField ?? 0);
                            if($discountPercent > 0) {
                                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
                            }
                        }
                        
                        // Apply nominal discounts (1-5) in cascading order
                        for($i = 1; $i <= 5; $i++) {
                            $amountField = "discount_amount_" . $i;
                            $discountAmount = (float)($offlineSaleItem->$amountField ?? 0);
                            if($discountAmount > 0) {
                                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qtyRetur);
                            }
                        }
                        
                        // Add to retur amount (already includes discounts)
                        $returAmount += \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
                    }
                }
            }
        }
        
        $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);
        
        Log::info("Calculated retur amount for invoice", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'retur_amount' => $returAmount
        ]);
        
        return $returAmount;
    }

    /**
     * Check if the return covers all remaining quantities
     */
    private function checkIfFullQtyReturn(ReturPenjualan $returPenjualan): bool
    {
        $order = $returPenjualan->order;
        $order->loadMissing('orderItems');

        if ($order->orderItems->isEmpty()) {
            return false;
        }

        foreach ($order->orderItems as $item) {
            if (((float) ($item->quantity ?? 0)) > 0.0001) {
                $totalShippedUnits = (float) \App\Models\BarangKeluar::whereHas('orderItem', function ($q) use ($order) {
                    $q->where('order_id', $order->id);
                })->sum('qty');

                if ($totalShippedUnits <= 0.0001) {
                    return false;
                }

                $totalReturnedUnits = (float) \App\Models\ReturPenjualanDetail::whereHas('returPenjualan', function ($q) use ($order) {
                    $q->where('order_id', $order->id)
                      ->whereIn('status', ['draft', 'selesai']);
                })->sum('qty');

                return ($totalReturnedUnits + 0.0001) >= $totalShippedUnits;
            }
        }

        return true;
    }
}

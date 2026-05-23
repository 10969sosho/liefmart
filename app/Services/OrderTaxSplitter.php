<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BarangKeluar;
use App\Models\WarehouseStock;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OrderTaxSplitter
{
    /**
     * Splits an order into tax groups based on Barang Keluar usage and Product Pricing.
     * 
     * @param Order $order
     * @return array [tax_id => ['tax_id' => int, 'total_value' => float, 'total_qty' => int]]
     */
    public function splitOrder(Order $order)
    {
        $taxGroups = [];
        $orderCreatedAt = $order->created_at ?? now();

        foreach ($order->orderItems as $item) {
            $this->processOrderItem($item, $orderCreatedAt, $taxGroups);
        }

        // Round values
        foreach ($taxGroups as $key => $group) {
            $taxGroups[$key]['total_value'] = round($group['total_value'], 2);
        }

        return $taxGroups;
    }

    private function processOrderItem(OrderItem $item, $orderCreatedAt, &$taxGroups)
    {
        $bks = BarangKeluar::where('order_item_id', $item->id)->get();
        
        if ($bks->isEmpty()) {
            // If no Barang Keluar, we cannot determine tax ID accurately.
            // In a regeneration context, this might mean the order hasn't been processed by warehouse yet.
            // We should skip or handle gracefully.
            return;
        }

        // 1. Calculate Total Pricelist Value for this Order Item (based on components)
        $totalPricelistValue = 0;
        $bkDetails = [];

        foreach ($bks as $bk) {
            $stock = WarehouseStock::find($bk->warehouse_stock_id);
            if (!$stock) continue;
            
            $product = Product::find($stock->product_id);
            if (!$product) continue;

            // Use initial_price as pricelist price. Default to 0 if not set.
            $price = (float) $product->getInitialPriceAt($orderCreatedAt);
            $value = $bk->qty * $price;
            
            $totalPricelistValue += $value;
            
            $bkDetails[] = [
                'bk' => $bk,
                'tax_id' => $stock->tax_id,
                'pricelist_value' => $value,
                'qty' => $bk->qty
            ];
        }

        // 2. Distribute Real Price
        // item_real_price is usually price_after_discount * quantity
        // But we need to be careful. Is price_after_discount unit price or total?
        // Typically in this system: price_after_discount is UNIT price.
        $realPrice = $item->price_after_discount * $item->quantity; 
        
        // Total BK Qty (to fallback if pricelist is 0)
        $totalBkQty = $bks->sum('qty');

        foreach ($bkDetails as $detail) {
            $taxId = $detail['tax_id'];
            
            $share = 0;
            if ($totalPricelistValue > 0) {
                // Split based on Pricelist Value Share
                $share = ($detail['pricelist_value'] / $totalPricelistValue) * $realPrice;
            } else {
                // Fallback: Split by Qty ratio if prices are zero
                if ($totalBkQty > 0) {
                     $share = ($detail['qty'] / $totalBkQty) * $realPrice;
                }
            }

            if (!isset($taxGroups[$taxId])) {
                $taxGroups[$taxId] = [
                    'tax_id' => $taxId,
                    'total_value' => 0,
                    'total_qty' => 0,
                    'items' => [] // Optional: track items if needed
                ];
            }

            $taxGroups[$taxId]['total_value'] += $share;
            $taxGroups[$taxId]['total_qty'] += $detail['qty']; 
        }
    }
}

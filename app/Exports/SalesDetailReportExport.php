<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SalesDetailReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder
{
    protected $orderItems;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($orderItems, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        $this->orderItems = collect($orderItems);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 4 is "No Order" and Column 14 is "No Resi" - force these to be text
        // (Updated from 13 to 14 because we added QTY Retur column)
        if (($columnIndex === 4 || $columnIndex === 14) && is_string($value) && !empty($value) && $value !== '-') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return $this->orderItems;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Hari',
            'No Order',
            'Platform',
            'Nama Barang',
            'Varian',
            'Qty',
            'QTY Retur',
            'Harga',
            'Total Item',
            'Qty Total',
            'Total Invoice',
            'No Resi',
        ];
    }

    public function map($item): array
    {
        static $index = 0;
        $index++;

        // Handle both object and array formats
        if (is_object($item)) {
            $tanggal = $item->order && $item->order->tanggal ? $item->order->tanggal->format('d-m-Y') : '-';
            $hari = $item->order ? $item->order->hari ?? '-' : '-';
            $orderNumber = $item->order ? (string)$item->order->order_number : '';
            $platformName = $item->order && $item->order->platform ? $item->order->platform->name : '-';
            $productName = $item->platformProduct ? $item->platformProduct->platform_product_name : 'Data produk tidak tersedia';
            $variant = $item->platformProduct && $item->platformProduct->variant ? $item->platformProduct->variant : '-';
            
            // Calculate qty retur for this order item
            // Include all returns except cancelled ones (draft and selesai should be counted)
            $qtyReturIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                ->whereHas('returPenjualan', function($q) { 
                    $q->whereIn('status', ['draft', 'selesai']); 
                })
                ->sum('qty');
            $qtyReturIndividual = (float) ($qtyReturIndividual ?? 0);
            
            // Check if this is a package product and get total package quantity
            $packageQuantity = 1; // Default for non-package products
            if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
            }
            
            // Convert individual retur quantity back to package quantity
            $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
            
            // Calculate original quantity (current quantity + returned quantity)
            $currentQty = (float) ($item->quantity ?? 0);
            $quantity = $currentQty + $qtyRetur; // Original quantity before return
            
            $price = $item->price_after_discount ?? 0;
            // Calculate original total value for this item (before any returns)
            $totalItem = $price * $quantity;
            
            // Calculate order totals after returns
            $qtyTotal = 0;
            $totalInvoice = 0;
            if ($item->order && $item->order->orderItems) {
                foreach($item->order->orderItems as $orderItem) {
                    $itemQtyReturIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $orderItem->id)
                        ->whereHas('returPenjualan', function($q) { 
                            $q->whereIn('status', ['draft', 'selesai']); 
                        })
                        ->sum('qty');
                    $itemQtyReturIndividual = (float) ($itemQtyReturIndividual ?? 0);
                    
                    // Check if this is a package product and get total package quantity
                    $itemPackageQuantity = 1;
                    if ($orderItem->platformProduct && $orderItem->platformProduct->mappingBarang && $orderItem->platformProduct->mappingBarang->count() > 0) {
                        $itemPackageQuantity = $orderItem->platformProduct->mappingBarang->sum('quantity');
                    }
                    
                    // Convert individual retur quantity back to package quantity
                    $itemQtyRetur = $itemPackageQuantity > 0 ? $itemQtyReturIndividual / $itemPackageQuantity : $itemQtyReturIndividual;
                    
                    // Calculate original quantity (current + returned) and then remaining
                    $currentItemQty = (float) ($orderItem->quantity ?? 0);
                    $originalItemQty = $currentItemQty + $itemQtyRetur;
                    $remainingItemQty = max(0, $originalItemQty - $itemQtyRetur);
                    
                    $qtyTotal += $remainingItemQty;
                    $totalInvoice += ($orderItem->price_after_discount * $remainingItemQty);
                }
            }
            $trackingNumber = $item->tracking_number ? (string)$item->tracking_number : '-';
        } else {
            $tanggal = isset($item['order']['tanggal']) ? $item['order']['tanggal']->format('d-m-Y') : '-';
            $hari = $item['order']['hari'] ?? '-';
            $orderNumber = isset($item['order']['order_number']) ? (string)$item['order']['order_number'] : '';
            $platformName = $item['order']['platform']['name'] ?? '-';
            $productName = $item['platform_product']['platform_product_name'] ?? 'Data produk tidak tersedia';
            $variant = $item['platform_product']['variant'] ?? '-';
            
            // Calculate qty retur for this order item (array format)
            $orderItemId = $item['id'] ?? null;
            $qtyReturIndividual = 0;
            if ($orderItemId) {
                $qtyReturIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $orderItemId)
                    ->whereHas('returPenjualan', function($q) { 
                        $q->whereIn('status', ['draft', 'selesai']); 
                    })
                    ->sum('qty');
                $qtyReturIndividual = (float) ($qtyReturIndividual ?? 0);
            }
            
            // Check if this is a package product and get total package quantity (array format)
            $packageQuantity = 1;
            if (isset($item['platform_product']['mapping_barang']) && is_array($item['platform_product']['mapping_barang'])) {
                $packageQuantity = array_sum(array_column($item['platform_product']['mapping_barang'], 'quantity'));
            }
            
            // Convert individual retur quantity back to package quantity
            $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
            
            // Calculate original quantity (current quantity + returned quantity)
            $currentQty = (float) ($item['quantity'] ?? 0);
            $quantity = $currentQty + $qtyRetur; // Original quantity before return
            
            $price = $item['price_after_discount'] ?? 0;
            // Calculate original total value for this item (before any returns)
            $totalItem = $price * $quantity;
            
            // For array format, we need to calculate totals differently
            // Since we don't have full order context, we'll use the provided values
            // but adjust them based on return status
            $qtyTotal = $item['order']['total_volume'] ?? 0;
            $totalInvoice = $item['order']['total_value'] ?? 0;
            $trackingNumber = isset($item['tracking_number']) ? (string)$item['tracking_number'] : '-';
        }

        // Ensure all numeric values are never null or empty - force to 0
        // Use multiple fallback methods to handle all possible null/empty cases
        $quantity = $quantity ?? 0;
        $quantity = is_numeric($quantity) ? (float)$quantity : 0;
        
        $qtyRetur = $qtyRetur ?? 0;
        $qtyRetur = is_numeric($qtyRetur) ? (float)$qtyRetur : 0;
        
        $price = $price ?? 0;
        $price = is_numeric($price) ? (float)$price : 0;
        
        $totalItem = $totalItem ?? 0;
        $totalItem = is_numeric($totalItem) ? (float)$totalItem : 0;
        
        $qtyTotal = $qtyTotal ?? 0;
        $qtyTotal = is_numeric($qtyTotal) ? (float)$qtyTotal : 0;
        
        $totalInvoice = $totalInvoice ?? 0;
        $totalInvoice = is_numeric($totalInvoice) ? (float)$totalInvoice : 0;

        return [
            $index,
            $tanggal ?: '-',
            $hari ?: '-',
            $orderNumber ?: '-',
            $platformName ?: '-',
            $productName ?: '-',
            $variant ?: '-',
            (string)$quantity,        // Force string conversion to ensure "0" is displayed
            (string)$qtyRetur,        // Force string conversion to ensure "0" is displayed
            (string)$price,           // Force string conversion to ensure "0" is displayed
            (string)$totalItem,       // Force string conversion to ensure "0" is displayed
            (string)$qtyTotal,        // Force string conversion to ensure "0" is displayed
            (string)$totalInvoice,    // Force string conversion to ensure "0" is displayed
            $trackingNumber ?: '-',
        ];
    }
} 
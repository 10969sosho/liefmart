<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SalesDetailReportExport extends DefaultValueBinder implements FromQuery, WithChunkReading, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder
{
    protected $query;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($query, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        // Accept either a query builder or collection for backward compatibility
        if ($query instanceof Builder) {
            $this->query = $query;
        } else {
            // For backward compatibility, convert collection to query
            // This should not be used in production but kept for safety
            $this->query = \App\Models\OrderItem::whereIn('id', collect($query)->pluck('id'));
        }
        
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }
    
    public function query()
    {
        return $this->query->with([
            'order.platform',
            'order.orderItems.platformProduct.mappingBarang', // Preload for siblings
            'order.orderItems.returPenjualanDetails.returPenjualan', // Preload for siblings
            'platformProduct.mappingBarang', // Preload for current item
            'returPenjualanDetails.returPenjualan' // Preload for current item
        ]);
    }
    
    public function chunkSize(): int
    {
        return 500; // Process 500 items at a time
    }

    /**
     * Helper to calculate retur quantity efficiently
     */
    private function getReturQty($orderItem)
    {
        // Use eager loaded relation if available
        if ($orderItem->relationLoaded('returPenjualanDetails')) {
            return $orderItem->returPenjualanDetails
                ->filter(function ($detail) {
                    return $detail->returPenjualan && in_array($detail->returPenjualan->status, ['draft', 'selesai']);
                })
                ->sum('qty');
        }

        // Fallback to query
        return \App\Models\ReturPenjualanDetail::where('order_item_id', $orderItem->id)
            ->whereHas('returPenjualan', function($q) { 
                $q->whereIn('status', ['draft', 'selesai']); 
            })
            ->sum('qty');
    }

    /**
     * Helper to calculate package quantity efficiently
     */
    private function getPackageQuantity($orderItem)
    {
        if (!$orderItem->platformProduct) {
            return 1;
        }

        // Use eager loaded relation if available
        if ($orderItem->platformProduct->relationLoaded('mappingBarang')) {
            $mappings = $orderItem->platformProduct->mappingBarang;
            $orderCreatedAt = $orderItem->order ? ($orderItem->order->created_at ?? $orderItem->created_at) : $orderItem->created_at;
            
            // Logic mirrored from MappingBarang::getMappingsForOrderCreatedAt
            
            // 1. Find the latest version
            $validMappings = $mappings->filter(function ($mapping) use ($orderCreatedAt) {
                if ($mapping->valid_from) {
                    return $mapping->valid_from <= $orderCreatedAt;
                }
                return $mapping->created_at <= $orderCreatedAt;
            });
            
            $latestVersion = $validMappings->max('version');
            
            if ($latestVersion !== null) {
                $versionMappings = $mappings->where('version', $latestVersion);
                return $versionMappings->count() > 0 ? $versionMappings->sum('quantity') : 1;
            }
            
            // 3. Fallback to active mappings
            $activeMappings = $mappings->where('is_active', true);
            return $activeMappings->count() > 0 ? $activeMappings->sum('quantity') : 1;
        }

        // Fallback to query
        $orderCreatedAt = $orderItem->order ? ($orderItem->order->created_at ?? $orderItem->created_at) : $orderItem->created_at;
        $mappings = \App\Models\MappingBarang::getMappingsForOrderCreatedAt($orderItem->platformProduct->id, $orderCreatedAt);
        return $mappings->count() > 0 ? $mappings->sum('quantity') : 1;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 4 is "No Order" and Column 14 is "No Resi" - force these to be text
        // We use explicit string casting and TYPE_STRING to prevent Excel scientific notation
        if ($columnIndex === 4 || $columnIndex === 14) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
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

        // Ensure item is loaded with relationships
        if (!$item->relationLoaded('order')) {
            $item->load('order.platform');
        }
        if (!$item->relationLoaded('platformProduct')) {
            $item->load('platformProduct.mappingBarang');
        }

        $tanggal = $item->order && $item->order->tanggal ? 
            (\Carbon\Carbon::parse($item->order->tanggal)->format('d-m-Y')) : '-';
        
        // Calculate hari if not set
        $hari = $item->order ? ($item->order->hari ?? 
            ($item->order->tanggal ? \Carbon\Carbon::parse($item->order->tanggal)->locale('id')->isoFormat('dddd') : '-')) : '-';
        
        $orderNumber = $item->order ? (string)$item->order->order_number : '';
        $platformName = $item->order && $item->order->platform ? $item->order->platform->name : '-';
        $productName = $item->platformProduct ? $item->platformProduct->platform_product_name : 'Data produk tidak tersedia';
        $variant = $item->platformProduct && $item->platformProduct->variant ? $item->platformProduct->variant : '-';
        
        // Calculate qty retur for this order item (optimized with caching)
        $qtyReturIndividual = $this->getReturQty($item);
        $qtyReturIndividual = (float) ($qtyReturIndividual ?? 0);
        
        // Check if this is a package product and get total package quantity
        $packageQuantity = $this->getPackageQuantity($item);
        
        // Convert individual retur quantity back to package quantity
        $qtyRetur = $packageQuantity > 0 ? round($qtyReturIndividual / $packageQuantity, 4) : $qtyReturIndividual;
        
        // Calculate original quantity (current quantity + returned quantity)
        $currentQty = (float) ($item->quantity ?? 0);
        $quantity = $currentQty + $qtyRetur; // Original quantity before return
        
        $price = $item->price_after_discount ?? 0;
        // Calculate original total value for this item (before any returns)
        $totalItem = $price * $quantity;
        
        // Calculate order totals after returns (cache order items to avoid reloading)
        $qtyTotal = 0;
        $totalInvoice = 0;
        if ($item->order) {
            // Load order items if not already loaded
            if (!$item->order->relationLoaded('orderItems')) {
                $item->order->load('orderItems.platformProduct');
            }
            
            foreach($item->order->orderItems as $orderItem) {
                $itemQtyReturIndividual = $this->getReturQty($orderItem);
                $itemQtyReturIndividual = (float) ($itemQtyReturIndividual ?? 0);
                
                // Check if this is a package product and get total package quantity
                $itemPackageQuantity = $this->getPackageQuantity($orderItem);
                
                // Convert individual retur quantity back to package quantity
                $itemQtyRetur = $itemPackageQuantity > 0 ? round($itemQtyReturIndividual / $itemPackageQuantity, 4) : $itemQtyReturIndividual;
                
                // Calculate original quantity (current + returned) and then remaining
                $currentItemQty = (float) ($orderItem->quantity ?? 0);
                $originalItemQty = $currentItemQty + $itemQtyRetur;
                $remainingItemQty = max(0, $originalItemQty - $itemQtyRetur);
                
                $qtyTotal += $remainingItemQty;
                $totalInvoice += ($orderItem->price_after_discount * $remainingItemQty);
            }
        }
        $trackingNumber = $item->tracking_number ? (string)$item->tracking_number : '-';

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
            "'" . ($orderNumber ?: '-'),
            $platformName ?: '-',
            $productName ?: '-',
            $variant ?: '-',
            (string)$quantity,        // Force string conversion to ensure "0" is displayed
            (string)$qtyRetur . ' pcs',  // Format qty retur dengan "pcs" seperti di view
            (string)$price,           // Force string conversion to ensure "0" is displayed
            (string)$totalItem,       // Force string conversion to ensure "0" is displayed
            (string)$qtyTotal,        // Force string conversion to ensure "0" is displayed
            (string)$totalInvoice,    // Force string conversion to ensure "0" is displayed
            "'" . ($trackingNumber ?: '-'),
        ];
    }
} 
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\ReturOfflineSaleDetail;

class OfflineSalesDetailReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $sales;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedCustomer;
    protected $selectedProduct;

    public function __construct($sales, $summary, $startDate, $endDate, $selectedCustomer = null, $selectedProduct = null)
    {
        // Accept pre-processed sales instead of re-querying
        $this->sales = collect($sales);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedCustomer = $selectedCustomer;
        $this->selectedProduct = $selectedProduct;
    }

    /**
     * Calculate total value after all cascading discounts
     */
    private function calculateTotalAfterDiscounts($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        
        // Start with base total (price × quantity)
        $currentTotal = $basePrice * $qty;
        
        // Apply percentage discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }
        
        // Apply nominal discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }
        
        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }

    /**
     * Calculate total discount amount
     */
    private function calculateTotalDiscount($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        $baseTotal = $basePrice * $qty;
        $finalTotal = $this->calculateTotalAfterDiscounts($item);
        
        return $baseTotal - $finalTotal;
    }

    /**
     * Get discount summary text
     */
    private function getDiscountSummary($item)
    {
        $discounts = [];
        
        // Check percentage discounts
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $discounts[] = number_format($discountPercent, 0) . '%';
            }
        }
        
        // Check nominal discounts
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $discounts[] = 'Rp ' . number_format($discountAmount, 0);
            }
        }
        
        return empty($discounts) ? '-' : implode(' + ', $discounts);
    }

    public function collection()
    {
        // Transform the sales data to flatten the items for export
        $exportData = collect();
        
        foreach ($this->sales as $sale) {
            foreach ($sale->items as $item) {
                // Cari qty retur untuk item ini (hanya dari retur dengan status selesai)
                $qtyRetur = ReturOfflineSaleDetail::where('offline_sale_item_id', $item->id)
                    ->whereHas('returOfflineSale', function($q) { $q->where('status', 'selesai'); })
                    ->sum('qty');
                $qtyRetur = (float) $qtyRetur;
                $qtyAfterRetur = max(0, (float)$item->quantity - $qtyRetur);
                $hargaJual = (float)($item->unit_price ?? 0);
                
                // ✅ Calculate proper total value after all discounts
                $totalValue = $this->calculateTotalAfterDiscounts($item);
                $totalDiscount = $this->calculateTotalDiscount($item);
                $discountSummary = $this->getDiscountSummary($item);
                
                // Debug logging untuk troubleshooting
                \Log::info('Offline Sales Export - Item Debug', [
                    'item_id' => $item->id,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'original_qty' => $item->quantity,
                    'qty_retur' => $qtyRetur,
                    'qty_after_retur' => $qtyAfterRetur,
                    'unit_price' => $hargaJual,
                    'total_before_discount' => $hargaJual * $item->quantity,
                    'total_discount' => $totalDiscount,
                    'total_value' => $totalValue,
                    'discount_summary' => $discountSummary
                ]);
                
                $exportData->push([
                    'tanggal_invoice' => $sale->sale_date->format('Y-m-d'),
                    'nomor_invoice' => $sale->surat_jalan_number,
                    'customer_name' => $sale->customerInfo ? $sale->customerInfo->name : $sale->customer_name,
                    'product_name' => $item->product ? $item->product->name : 'Unknown Product',
                    'quantity' => $item->quantity,
                    'qty_retur' => $qtyRetur,
                    'harga_jual' => $hargaJual,
                    'total_discount' => $totalDiscount,
                    'discount_summary' => $discountSummary,
                    'total_value' => $totalValue,
                ]);
            }
        }
        
        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nomor Invoice',
            'Customer',
            'Produk',
            'Quantity',
            'QTY Retur',
            'Harga Satuan',
            'Total Diskon',
            'Detail Diskon',
            'Total Value (Setelah Diskon)'
        ];
    }

    public function map($item): array
    {
        // Handle array format from collection
        $tanggalInvoice = $item['tanggal_invoice'] ?? '';
        $nomorInvoice = $item['nomor_invoice'] ?? '';
        $customerName = $item['customer_name'] ?? '';
        $productName = $item['product_name'] ?? '';
        $quantity = (float)($item['quantity'] ?? 0);
        $qtyRetur = (float)($item['qty_retur'] ?? 0);
        $hargaJual = (float)($item['harga_jual'] ?? 0);
        $totalDiscount = (float)($item['total_discount'] ?? 0);
        $discountSummary = $item['discount_summary'] ?? '-';
        $totalValue = (float)($item['total_value'] ?? 0);
        
        // Debug logging for specific items with zero total value
        if ($totalValue == 0 && $quantity > 0 && $hargaJual > 0) {
            \Log::warning('Offline Sales Export - Zero Total Value Warning', [
                'product' => $productName,
                'quantity' => $quantity,
                'qty_retur' => $qtyRetur,
                'harga_jual' => $hargaJual,
                'total_discount' => $totalDiscount,
                'calculated_value' => $quantity * $hargaJual, // ✅ Fixed: Use original quantity for total value
                'item_data' => $item
            ]);
        }

        return [
            $tanggalInvoice,
            $nomorInvoice,
            $customerName,
            $productName,
            number_format($quantity, 0), // Format as integer for quantity
            number_format($qtyRetur, 0), // Format as integer for return quantity
            number_format($hargaJual, 0), // Format currency without decimals
            number_format($totalDiscount, 0), // Format total discount
            $discountSummary, // Discount details (e.g., "10% + Rp 5000")
            number_format($totalValue, 0), // ✅ Format total value after discount
        ];
    }
} 
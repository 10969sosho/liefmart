<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OfflineSalesByProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $productSummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedCustomer;
    protected $selectedProduct;

    public function __construct($productSummary, $summary, $startDate, $endDate, $selectedCustomer = null, $selectedProduct = null)
    {
        $this->productSummary = collect($productSummary);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedCustomer = $selectedCustomer;
        $this->selectedProduct = $selectedProduct;
    }

    public function collection()
    {
        return $this->productSummary;
    }

    public function headings(): array
    {
        return [
            'Produk',
            'Total Quantity (pcs)',
            'Total Value (Rp)',
            'Avg Harga/Item (Rp)',
        ];
    }

    public function map($product): array
    {
        return [
            $product['product_name'],
            $product['total_quantity'],
            $product['total_value'],
            $product['avg_price'],
        ];
    }
} 
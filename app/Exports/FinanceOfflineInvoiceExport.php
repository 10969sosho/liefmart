<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FinanceOfflineInvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Invoice',
            'Tanggal Invoice',
            'No. SJ',
            'Tax ID',
            'Kategori',
            'Customer',
            'DPP (Rp)',
            'Retur (Rp)',
            'Net (Rp)',
            'PPN (Rp)',
            'Total (Rp)',
            'Status',
            'Total Dibayar (Rp)',
            'Sisa Tagihan (Rp)',
            'Tanggal Pembayaran',
            'Jumlah Cetak',
            'Terakhir Dicetak'
        ];
    }

    public function map($invoice): array
    {
        $firstItem = $invoice->barangKeluarItems->first();
        $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id ? $firstItem->warehouseStock->tax_id : null;
        
        // DPP = nominal (yang sudah berdasarkan total_amount dari offline_sales)
        // Tidak perlu reverse calculate karena nominal sudah benar
        $dpp = $invoice->nominal;
        
        $totalPaid = $invoice->payments->sum('amount');
        $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($totalPaid);
        
        // Get SJ numbers, tax_id and customer
        $sjNumber = '-';
        $customer = '-';
        $taxLabel = '-';
        
        if ($firstItem) {
            if ($firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale) {
                $sjNumber = $firstItem->offlineSaleItem->offlineSale->surat_jalan_number;
                $customer = $firstItem->offlineSaleItem->offlineSale->customer_name;
            }
            
            if ($firstItem->warehouseStock && $firstItem->warehouseStock->tax_id) {
                $taxId = $firstItem->warehouseStock->tax_id;
                
                if ($taxId == 3) {
                    $taxLabel = 'PKP';
                } elseif ($taxId == 4) {
                    $taxLabel = 'Non-PKP';
                }
            }
        }
        
        // Calculate NET: NET = DPP - Retur
        // DPP = nominal (yang sudah berdasarkan total_amount dari offline_sales)
        // Retur = jumlah retur yang proportional
        
        // Get all unique offline sales from this invoice
        $offlineSales = collect();
        foreach ($invoice->barangKeluarItems as $bk) {
            if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                $offlineSales->push($bk->offlineSaleItem->offlineSale);
            }
        }
        $offlineSales = $offlineSales->unique('id');
        
        // Calculate retur amount (proportional to nominal/DPP)
        $returAmount = 0;
        
        foreach ($offlineSales as $sale) {
            $sale->load('items');
            
            // Calculate total value of all items in the sale (with discounts) - untuk proportion
            // Use original quantity (before return) for accurate proportion calculation
            $totalSaleItemsValue = 0;
            foreach ($sale->items as $saleItem) {
                // Get original quantity (current + returned)
                $returnedQty = \App\Models\ReturOfflineSaleDetail::where('offline_sale_item_id', $saleItem->id)
                    ->whereHas('returOfflineSale', function($q) {
                        $q->where('status', 'selesai');
                    })
                    ->sum('qty');
                $originalQty = (float)$saleItem->quantity + (float)$returnedQty;
                
                $itemValue = $this->calculateItemValueWithQty($saleItem, $originalQty);
                $totalSaleItemsValue += $itemValue;
            }
            
            // Calculate value of items from this sale that are in this invoice
            // Use original quantity (before return) for accurate proportion calculation
            $invoiceItemsValue = 0;
            foreach ($invoice->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem && $bk->offlineSaleItem->offline_sale_id == $sale->id) {
                    $saleItem = $bk->offlineSaleItem;
                    
                    // Get original quantity (current + returned)
                    $returnedQty = \App\Models\ReturOfflineSaleDetail::where('offline_sale_item_id', $saleItem->id)
                        ->whereHas('returOfflineSale', function($q) {
                            $q->where('status', 'selesai');
                        })
                        ->sum('qty');
                    $originalQty = (float)$saleItem->quantity + (float)$returnedQty;
                    
                    $itemValue = $this->calculateItemValueWithQty($saleItem, $originalQty);
                    $invoiceItemsValue += $itemValue;
                }
            }
            
            // Calculate proportion: how much of this sale is in this invoice
            $proportion = $totalSaleItemsValue > 0 ? ($invoiceItemsValue / $totalSaleItemsValue) : 0;
            
            // Get sale total_amount (DPP dari sales - ini yang digunakan di sales value)
            $saleDPP = $sale->tax_amount > 0 ? $sale->total_amount : $sale->subtotal;
            
            // Calculate retur amount for this sale (proportional to DPP)
            $saleReturAmount = 0;
            $returs = \App\Models\ReturOfflineSale::where('offline_sale_id', $sale->id)
                ->where('status', 'selesai')
                ->get();
            
            foreach ($returs as $retur) {
                foreach ($retur->details as $detail) {
                    $offlineSaleItem = $detail->offlineSaleItem;
                    if ($offlineSaleItem) {
                        // Get returned quantity
                        $returnedQty = (float)$detail->qty;
                        $currentQty = (float)$offlineSaleItem->quantity;
                        $originalQty = $currentQty + $returnedQty;
                        
                        // Calculate item value with original quantity (before return)
                        $returItemValue = $this->calculateItemValueWithQty($offlineSaleItem, $originalQty);
                        $returProportion = $totalSaleItemsValue > 0 ? ($returItemValue / $totalSaleItemsValue) : 0;
                        
                        // Retur qty proportion (how much of original qty is returned)
                        $returQtyProportion = $originalQty > 0 ? ($returnedQty / $originalQty) : 0;
                        
                        // Retur amount = sale DPP * proportion of item * proportion of qty
                        $saleReturAmount += $saleDPP * $returProportion * $returQtyProportion;
                    }
                }
            }
            
            // Apply proportion to retur (karena invoice mungkin hanya sebagian dari sale)
            $proportionalRetur = $saleReturAmount * $proportion;
            $returAmount += $proportionalRetur;
        }
        
        // DPP = nominal (sudah berdasarkan total_amount dari offline_sales)
        $dppOriginal = $invoice->nominal;
        
        // Round retur amount
        $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);
        
        // NET = DPP (nominal) - Retur
        $netDPP = max(0, $dppOriginal - $returAmount);
        $netDPP = \App\Helpers\NumberFormatter::roundToWholeNumber($netDPP);
        
        // PPN dari NET
        $netPPN = 0;
        if ($taxId == 3) {
            $netDPP11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($netDPP);
            $netPPN = \App\Helpers\NumberFormatter::calculatePPN($netDPP11_12);
            $netPPN = \App\Helpers\NumberFormatter::roundToWholeNumber($netPPN);
        }
        
        // Total = NET + PPN
        $netTotal = $netDPP + $netPPN;
        $netTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($netTotal);
        
        // Update remaining amount based on net total
        $remainingAmount = max(0, $netTotal - $totalPaid);
        
        // Get payment dates (comma separated if multiple)
        $paymentDates = $invoice->payments
            ->map(function($payment) {
                return $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '';
            })
            ->filter()
            ->implode(', ');
        
        if (empty($paymentDates)) {
            $paymentDates = '-';
        }
        
        // Get main category name
        $mainCategoryName = 'N/A';
        if ($firstItem && $firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale && $firstItem->offlineSaleItem->offlineSale->mainCategory) {
            $mainCategoryName = $firstItem->offlineSaleItem->offlineSale->mainCategory->name;
        } elseif ($firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->product && $firstItem->warehouseStock->product->mainCategory) {
            $mainCategoryName = $firstItem->warehouseStock->product->mainCategory->name;
        } elseif (session()->has('main_category_name')) {
            $mainCategoryName = session('main_category_name');
        }
        
        // Update status based on net total
        // Check if there's a partial return
        $hasPartialReturn = $returAmount > 0 && $invoice->status != 'retur_full' && $invoice->nominal > 0;
        
        if ($invoice->status == 'retur_full' || $invoice->nominal == 0) {
            $status = 'Retur Full';
        } elseif ($totalPaid > $netTotal) {
            // Tidak Balance: pembayaran melebihi net total
            $status = 'Tidak Balance';
        } elseif ($totalPaid >= $netTotal) {
            // Lunas - check if there's partial return
            if ($hasPartialReturn) {
                $status = 'Lunas (Retur Sebagian)';
            } else {
                $status = 'Lunas';
            }
        } elseif ($totalPaid > 0) {
            $status = 'Belum Lunas';
        } else {
            $status = 'Belum Lunas';
        }
        
        return [
            '', // No - will be filled by Excel
            $invoice->invoice_number,
            $invoice->tanggal_invoice->format('d/m/Y'),
            $sjNumber,
            $taxLabel,
            $mainCategoryName,
            $customer,
            $dppOriginal, // DPP (original sebelum retur)
            $returAmount, // Retur
            $netDPP, // Net (DPP setelah retur)
            $netPPN, // PPN dari Net
            $netTotal, // Total (Net + PPN)
            $status,
            $totalPaid,
            $remainingAmount,
            $paymentDates, // Tanggal Pembayaran
            $invoice->print_count,
            $invoice->last_printed_at ? $invoice->last_printed_at->format('d/m/Y H:i') : '-'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // DPP
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Retur
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Net
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPN
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Dibayar
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Sisa Tagihan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(25); // Tanggal Pembayaran
        $sheet->getColumnDimension('Q')->setWidth(12);
        $sheet->getColumnDimension('R')->setWidth(20);

        // Auto-fit row height
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
        } else {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
        }
        return true;
    }

    /**
     * Calculate item value with all discounts
     */
    private function calculateItemValue($item)
    {
        $qty = (float)($item->quantity ?? 0);
        return $this->calculateItemValueWithQty($item, $qty);
    }

    /**
     * Calculate item value with all discounts using specified quantity
     */
    private function calculateItemValueWithQty($item, $qty)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)$qty;

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
}

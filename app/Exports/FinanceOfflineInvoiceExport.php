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
        
        // Check if this is partial refund (nominal already includes PPN)
        $isPartialRefund = $invoice->status == 'partial_refund';
        
        if ($isPartialRefund) {
            // Nominal already includes PPN (grand total), need to reverse calculate
            $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->nominal);
            
            if ($taxId == 3) {
                // PKP: Reverse calculate DPP from grand total
                $dpp = \App\Helpers\NumberFormatter::roundToWholeNumber($grandTotal / 1.11);
                $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                $ppn = $grandTotal - $dpp;
                $ppn = \App\Helpers\NumberFormatter::roundToWholeNumber($ppn);
            } else {
                // Non-PKP: No PPN, DPP = Grand Total
                $dpp = $grandTotal;
                $ppn = 0;
            }
        } else {
            // Normal case: use nominal from DB directly (DPP)
            $dpp = \App\Helpers\NumberFormatter::calculateDPP($invoice->nominal);
            $ppn = 0;
            $grandTotal = $dpp;
            
            if ($taxId == 3) {
                $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
            } else {
                $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
            }
        }
        
        $totalPaid = $invoice->payments->sum('amount');
        $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($totalPaid);
        $remainingAmount = max(0, $grandTotal - $totalPaid);
        
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
        
        // Get retur information and calculate amounts
        $returAmount = 0; // Nominal yang diretur (DPP dengan diskon)
        $dppOriginal = 0; // DPP original (sebelum retur) - tetap sama, tidak berubah
        
        // Calculate retur amount and DPP original (sebelum retur)
        if ($firstItem && $firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale) {
            $offlineSale = $firstItem->offlineSaleItem->offlineSale;
            
            // Get all returs - use eager loaded if available
            $returs = $offlineSale->relationLoaded('returOfflineSales') 
                ? $offlineSale->returOfflineSales 
                : \App\Models\ReturOfflineSale::where('offline_sale_id', $offlineSale->id)
                    ->where('status', 'selesai')
                    ->get();
            
            // Calculate DPP Original from quantity original (before retur)
            // Only count each offline_sale_item once
            $processedSaleItemIds = [];
            foreach ($invoice->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem) {
                    $osi = $bk->offlineSaleItem;
                    $saleItemId = $osi->id;
                    
                    // Only count each sale item once
                    if (!in_array($saleItemId, $processedSaleItemIds)) {
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
                        $processedSaleItemIds[] = $saleItemId;
                    }
                }
            }
            
            // Calculate retur amount (DPP yang diretur dengan diskon)
            foreach ($returs as $retur) {
                foreach ($retur->details as $detail) {
                    $offlineSaleItem = $detail->offlineSaleItem;
                    if ($offlineSaleItem) {
                        $qtyRetur = (float)($detail->qty ?? 0);
                        
                        // Use helper to calculate value with discounts
                        $currentTotal = $this->calculateItemValueWithQty($offlineSaleItem, $qtyRetur);
                        
                        // Add to retur amount (already includes discounts)
                        $returAmount += \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
                    }
                }
            }
        } else {
            // If no offline sale, use nominal from DB directly (DPP)
            // This is for backward compatibility
            $dppOriginal = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->nominal);
        }
        
        // Round amounts
        $dppOriginal = \App\Helpers\NumberFormatter::roundToWholeNumber($dppOriginal);
        $returAmount = \App\Helpers\NumberFormatter::roundToWholeNumber($returAmount);
        
        // NET = DPP setelah retur = DPP original - RETUR
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
        
        // Use status from DB directly
        $dbStatus = $invoice->status ?? 'unpaid';
        
        // Check if there's a partial return for display
        $hasPartialReturn = $returAmount > 0 && $dbStatus != 'refunded' && $invoice->nominal > 0;
        
        // Map DB status to display status
        if ($dbStatus == 'refunded') {
            $status = 'Retur Full';
        } elseif ($dbStatus == 'partial_refund') {
            // For partial_refund, check payment status
            if ($totalPaid >= $netTotal) {
                $status = 'Lunas (Retur Sebagian)';
            } else {
                $status = 'Belum Lunas (Retur Sebagian)';
            }
        } elseif ($dbStatus == 'paid') {
            $status = 'Lunas';
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

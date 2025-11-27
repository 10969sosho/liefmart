<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $stocks;
    protected $totalInventoryValue;
    protected $freeItemRows = [];
    private static $index = 1;

    public function __construct($stocks, $totalInventoryValue = 0)
    {
        $this->stocks = $stocks;
        $this->totalInventoryValue = $totalInventoryValue;
        self::$index = 1;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->stocks;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'SKU',
            'Nama Produk',
            'Kategori Utama',
            'Kategori Produk',
            'Brand',
            'Sub Brand',
            'Tipe Produk',
            'Ukuran',
            'Varian',
            'Lokasi',
            'Expired Date',
            'Status ED',
            'Total Qty',
            'Satuan',
            'Pajak',
            'Tanggal Penerimaan',
            'Nomor PO',
            'Status',
            'Total',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        // Track free items for styling (dinonaktifkan karena is_free tidak ada di warehouse_stock)
        // if ($row->is_free) {
        //     $this->freeItemRows[] = self::$index + 1; // +1 because Excel is 1-indexed and we have a header row
        // }

        // Calculate total value (harga beli x qty) for this item
        $totalValue = 0;
        if ($row->penerimaanDetail) {
            $penerimaanDetail = $row->penerimaanDetail;
            
            // Calculate HPP after discounts (same logic as in controller)
            $hppAsli = $penerimaanDetail->harga_hpp ?? 0;
            $hppSetelahDiskon = $hppAsli;
            
            // Apply percentage discounts in sequence
            if ($penerimaanDetail->diskon_persen_1 > 0) {
                $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_1 / 100);
            }
            if ($penerimaanDetail->diskon_persen_2 > 0) {
                $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_2 / 100);
            }
            if ($penerimaanDetail->diskon_persen_3 > 0) {
                $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_3 / 100);
            }
            if ($penerimaanDetail->diskon_persen_4 > 0) {
                $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_4 / 100);
            }
            if ($penerimaanDetail->diskon_persen_5 > 0) {
                $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_5 / 100);
            }
            
            // Apply nominal discounts
            $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_1 ?? 0);
            $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_2 ?? 0);
            $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_3 ?? 0);
            $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_4 ?? 0);
            $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_5 ?? 0);
            
            // Ensure price doesn't go negative
            $finalHpp = max(0, $hppSetelahDiskon);
            
            // Calculate total: harga beli x qty
            $totalValue = $finalHpp * ($row->qty ?? 0);
        }

        return [
            self::$index++,
            $row->product->sku ?? '-',
            $row->product->name ?? '-',
            $row->product->mainCategory->name ?? '-',
            $row->product->productCategory->name ?? '-',
            $row->product->brand->name ?? '-',
            $row->product->subBrand->name ?? '-',
            $row->product->productType->name ?? '-',
            $row->product->productSize->name ?? '-',
            $row->product->productVariant->name ?? '-',
            $row->lokasi->nama ?? '-',
            $row->expired_date ? date('d/m/Y', strtotime($row->expired_date)) : '-',
            $row->ed_status ?? '-',
            number_format($row->qty, 2) ?? '0.00', // Format dengan 2 desimal
            $row->penerimaanDetail->satuan->name ?? '-',
            $row->tax->name ?? '-',
            $row->penerimaanDetail && $row->penerimaanDetail->penerimaan && $row->penerimaanDetail->penerimaan->tanggal_penerimaan ? date('d/m/Y', strtotime($row->penerimaanDetail->penerimaan->tanggal_penerimaan)) : '-',
            $row->penerimaanDetail && $row->penerimaanDetail->penerimaan ? $row->penerimaanDetail->penerimaan->nomor_po ?? '-' : '-',
            'Normal', // Status is_free dinonaktifkan
            $totalValue, // Total value (will be formatted in Excel)
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Register events for the export
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // Add title
                $sheet->insertNewRowBefore(1, 5);
                $sheet->mergeCells('A1:T1');
                $sheet->setCellValue('A1', 'LAPORAN STOK BARANG');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add date
                $sheet->mergeCells('A2:T2');
                $sheet->setCellValue('A2', 'Tanggal: ' . date('d/m/Y'));
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add summary section with Total Nilai Inventory
                $sheet->mergeCells('A4:J4');
                $sheet->setCellValue('A4', 'Total Nilai Inventory:');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
                
                $sheet->mergeCells('K4:T4');
                $sheet->setCellValue('K4', 'Rp ' . number_format($this->totalInventoryValue, 0, ',', '.'));
                $sheet->getStyle('K4')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('K4')->getFont()->getColor()->setRGB('0066CC');
                
                // Add background color to summary row
                $sheet->getStyle('A4:T4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E7F3FF');
                
                // Add borders to summary row
                $sheet->getStyle('A4:T4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Style headers (now at row 5)
                $sheet->getStyle('A5:T5')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A5:T5')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A5:T5')->getFont()->setBold(true);
                
                // Add borders
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A5:T'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Format Total column (column T) as currency for all data rows
                for ($row = 6; $row <= $lastRow; $row++) {
                    $sheet->getStyle('T'.$row)->getNumberFormat()->setFormatCode('#,##0');
                    
                    // Add zebra striping
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':T'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F2F2F2');
                    }
                }
                
                // Style free items with a light blue background (dinonaktifkan)
                // foreach ($this->freeItemRows as $row) {
                //     $event->sheet->getStyle('A'.$row.':T'.$row)->getFill()
                //         ->setFillType(Fill::FILL_SOLID)
                //         ->getStartColor()->setRGB('D6F5FF');
                // }
                
                // Add total row at the bottom
                $totalRow = $lastRow + 1;
                $sheet->setCellValue('A'.$totalRow, 'TOTAL');
                $sheet->mergeCells('A'.$totalRow.':M'.$totalRow);
                $sheet->getStyle('A'.$totalRow)->getFont()->setBold(true);
                $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Calculate total qty
                $sheet->setCellValue('N'.$totalRow, '=SUM(N6:N'.$lastRow.')');
                $sheet->getStyle('N'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('N'.$totalRow)->getFont()->setBold(true);
                
                // Calculate total value (sum of Total column)
                $sheet->setCellValue('T'.$totalRow, '=SUM(T6:T'.$lastRow.')');
                $sheet->getStyle('T'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('T'.$totalRow)->getFont()->setBold(true);
                
                // Style the total row
                $sheet->getStyle('A'.$totalRow.':T'.$totalRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E7F1FF');
                $sheet->getStyle('A'.$totalRow.':T'.$totalRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
} 
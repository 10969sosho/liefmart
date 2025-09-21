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
    protected $freeItemRows = [];
    private static $index = 1;

    public function __construct($stocks)
    {
        $this->stocks = $stocks;
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
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:S1');
                $sheet->setCellValue('A1', 'LAPORAN STOK BARANG');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add date
                $sheet->mergeCells('A2:S2');
                $sheet->setCellValue('A2', 'Tanggal: ' . date('d/m/Y'));
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Style headers
                $sheet->getStyle('A4:S4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A4:S4')->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A4:S4')->getFont()->setBold(true);
                
                // Add borders
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A4:S'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Add zebra striping
                for ($row = 5; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':S'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F2F2F2');
                    }
                }
                
                // Style free items with a light blue background (dinonaktifkan)
                // foreach ($this->freeItemRows as $row) {
                //     $event->sheet->getStyle('A'.$row.':S'.$row)->getFill()
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
                $sheet->setCellValue('N'.$totalRow, '=SUM(N5:N'.$lastRow.')');
                $sheet->getStyle('N'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('N'.$totalRow)->getFont()->setBold(true);
                
                // Style the total row
                $sheet->getStyle('A'.$totalRow.':S'.$totalRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E7F1FF');
                $sheet->getStyle('A'.$totalRow.':S'.$totalRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
} 
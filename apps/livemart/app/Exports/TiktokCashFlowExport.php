<?php

namespace App\Exports;

use App\Models\ArusKasTiktokImport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class TiktokCashFlowExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 3 is "No. Pesanan" - force to be text to preserve order numbers exactly
        if ($columnIndex === 3 && is_string($value) && !empty($value) && $value !== '-') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
    }

    public function query()
    {
        $query = ArusKasTiktokImport::select([
            'id', 'tanggal_pembayaran', 'deskripsi', 'no_pesanan', 'tanggal_pesanan', 'pembayaran', 'saldo_akhir'
        ])
        ->orderBy('tanggal_pembayaran', 'desc');

        // Apply filters based on ArusKasTiktokController filter structure
        if (isset($this->request['start_date']) && !empty($this->request['start_date'])) {
            $query->whereDate('tanggal_pembayaran', '>=', $this->request['start_date']);
        }
        if (isset($this->request['end_date']) && !empty($this->request['end_date'])) {
            $query->whereDate('tanggal_pembayaran', '<=', $this->request['end_date']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Tanggal Pembayaran',
            'Deskripsi',
            'No. Pesanan',
            'Tanggal Pesanan',
            'Pembayaran',
            'Saldo Akhir'
        ];
    }

    public function map($transaction): array
    {
        return [
            // Tanggal Pembayaran - format as d-M-Y to match import expectations
            $transaction->tanggal_pembayaran ? Carbon::parse($transaction->tanggal_pembayaran)->format('d-M-Y') : '',
            
            // Deskripsi - use the actual description from the cash flow data
            $transaction->deskripsi ?? '',
            
            // No. Pesanan - preserve original order number format for lookup, ensure exact string representation
            $transaction->no_pesanan ? (string)$transaction->no_pesanan : '',
            
            // Tanggal Pesanan - format as d-M-Y to match import expectations  
            $transaction->tanggal_pesanan ? Carbon::parse($transaction->tanggal_pesanan)->format('d-M-Y') : '',
            
            // Pembayaran - the payment amount from cash flow data
            $transaction->pembayaran ?? 0,
            
            // Saldo Akhir - the balance from cash flow data
            $transaction->saldo_akhir ?? 0
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style for headers
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }
} 
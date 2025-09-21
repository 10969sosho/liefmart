<?php

namespace App\Exports;

use App\Models\BlibliFinancialTransaction;
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

class BlibliFinanceAnalyticsExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Custom rounding function: max 1 decimal place
     * Rule: 5+ rounds up, 4- rounds down, with special case for .45 pattern
     */
    private function customRound($value, $decimals = 1)
    {
        // Convert to string to avoid floating point issues
        $valueStr = number_format($value, 10, '.', '');
        $valueStr = rtrim($valueStr, '0');
        $valueStr = rtrim($valueStr, '.');
        
        $parts = explode('.', $valueStr);
        if (count($parts) < 2 || strlen($parts[1]) <= $decimals) {
            return round($value, $decimals);
        }
        
        $intPart = $parts[0];
        $decPart = $parts[1];
        $keepDigits = substr($decPart, 0, $decimals);
        $nextDigit = isset($decPart[$decimals]) ? (int)$decPart[$decimals] : 0;
        
        // Special case: X.45 exactly should round down (user's specific rule)
        if (strlen($decPart) == 2 && $decPart == '45') {
            return (float)($intPart . '.4');
        }
        
        // Normal rounding: 5+ up, 4- down
        if ($nextDigit >= 5) {
            $keepDigitsInt = (int)$keepDigits + 1;
            if ($keepDigitsInt >= pow(10, $decimals)) {
                $intPart = (int)$intPart + 1;
                $keepDigits = str_repeat('0', $decimals);
            } else {
                $keepDigits = str_pad($keepDigitsInt, $decimals, '0', STR_PAD_LEFT);
            }
        } else {
            $keepDigits = str_pad($keepDigits, $decimals, '0', STR_PAD_RIGHT);
        }
        
        return (float)($intPart . '.' . $keepDigits);
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 3 is "No. Order" and Column 4 is "No. Invoice" in the final export - force these to be text
        if (($columnIndex === 3 || $columnIndex === 4) && is_string($value) && !empty($value) && $value !== '-') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
    }

    public function query()
    {
        $query = BlibliFinancialTransaction::select([
            'id', 'tanggal_order', 'hari_order', 'no_order', 'no_invoice',
            'nominal_harga', 'nominal_diskon1', 'nominal_diskon2', 'nominal_diskon3', 
            'nominal_diskon4', 'nominal_diskon5', 'nominal_diskon6', 'nominal_diskon7',
            'nominal_diskon8', 'nominal_diskon9', 'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
            'persentase_diskon1', 'persentase_diskon2', 'persentase_diskon3', 
            'persentase_diskon4', 'persentase_diskon5', 'persentase_diskon6', 'persentase_diskon7',
            'persentase_diskon8', 'persentase_diskon9', 'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12',
            'adjustment', 'adjustment_description', 'total_persentase', 'nominal_fix', 'saldo_masuk',
            'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran', 'outstanding'
        ])
        ->orderBy('tanggal_order', 'desc');

        // Apply filters - same as in controller
        // Apply filters for payment dates
        if (isset($this->request['from_date']) && !empty($this->request['from_date'])) {
            $query->whereDate('tanggal_masuk_pembayaran', '>=', $this->request['from_date']);
        }
        if (isset($this->request['to_date']) && !empty($this->request['to_date'])) {
            $query->whereDate('tanggal_masuk_pembayaran', '<=', $this->request['to_date']);
        }
        
        // Apply filters for order dates
        if (isset($this->request['from_order_date']) && !empty($this->request['from_order_date'])) {
            $query->whereDate('tanggal_order', '>=', $this->request['from_order_date']);
        }
        if (isset($this->request['to_order_date']) && !empty($this->request['to_order_date'])) {
            $query->whereDate('tanggal_order', '<=', $this->request['to_order_date']);
        }
        
        if (isset($this->request['order_number']) && !empty($this->request['order_number'])) {
            $query->where('no_order', 'like', '%' . $this->request['order_number'] . '%');
        }
        if (isset($this->request['invoice_number']) && !empty($this->request['invoice_number'])) {
            $query->where('no_invoice', 'like', '%' . $this->request['invoice_number'] . '%');
        }
        if (isset($this->request['payment_date']) && !empty($this->request['payment_date'])) {
            $query->whereDate('tanggal_masuk_pembayaran', $this->request['payment_date']);
        }
        
        // Filter by tax ID (sama seperti di controller)
        if (isset($this->request['tax_id']) && is_array($this->request['tax_id'])) {
            $query->where(function($q) {
                foreach ($this->request['tax_id'] as $taxId) {
                    $q->orWhere('no_invoice', 'like', '%' . $taxId . '%');
                }
            });
        }
        
        if (isset($this->request['min_nominal']) && !empty($this->request['min_nominal'])) {
            $query->where('nominal_fix', '>=', $this->request['min_nominal']);
        }
        if (isset($this->request['max_nominal']) && !empty($this->request['max_nominal'])) {
            $query->where('nominal_fix', '<=', $this->request['max_nominal']);
        }
        
        // Filter by outstanding status
        if (isset($this->request['outstanding_status']) && $this->request['outstanding_status'] !== '') {
            if ($this->request['outstanding_status'] === '0') {
                $query->where('outstanding', 0);
            } elseif ($this->request['outstanding_status'] === '1') {
                $query->where(function($q) {
                    $q->where('outstanding', '>', 0)
                      ->orWhere('outstanding', '<', 0);
                });
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Order',
            'No. Order',
            'No. Invoice',
            'Status',
            'HPP (Rp)',
            'Biaya Admin (Rp)',
            'Biaya Layanan (Rp)',
            'Diskon 3 (Rp)',
            'Diskon 4 (Rp)',
            'Biaya 5 (Rp)',
            'Biaya 6 (Rp)',
            'Biaya 7 (Rp)',
            'Biaya 8 (Rp)',
            'Biaya 9 (Rp)',
            'Biaya 10 (Rp)',
            'Biaya 11 (Rp)',
            'Biaya 12 (Rp)',
            'Adjustment (Rp)',
            'Nominal Fix (Rp)',
            'Saldo Masuk (Rp)',
            'Tanggal Pembayaran',
            'Outstanding (Rp)',
            'Biaya Admin (%)',
            'Biaya Layanan (%)',
            'Diskon 3 (%)',
            'Diskon 4 (%)',
            'Biaya 5 (%)',
            'Biaya 6 (%)',
            'Biaya 7 (%)',
            'Biaya 8 (%)',
            'Biaya 9 (%)',
            'Biaya 10 (%)',
            'Biaya 11 (%)',
            'Biaya 12 (%)',
            'Adjustment (%)',
            'Total (%)'
        ];
    }

    public function map($transaction): array
    {
        static $no = 1;
        
        // Determine tax status similar to view logic
        $taxId = null;
        if (strpos($transaction->no_invoice, 'HPNSDA-OLK/01') !== false) {
            $taxId = 1;
        } elseif (strpos($transaction->no_invoice, 'HPNSDA-OLK/02') !== false) {
            $taxId = 2;
        } elseif (strpos($transaction->no_invoice, 'HGNSDA-OL/01') !== false) {
            $taxId = 3;
        } elseif (strpos($transaction->no_invoice, 'HGNSDA-OL/02') !== false) {
            $taxId = 4;
        } else {
            if (preg_match('/\/(\d{2})/', $transaction->no_invoice, $matches)) {
                $taxId = (int)$matches[1];
            }
        }
        $isPKP = in_array($taxId, [1, 3, 5, 7]);
        $taxStatus = $taxId ? ($isPKP ? 'PKP' : 'Non-PKP') : 'N/A';
        
        // Calculate percentages with custom rounding
        $persentase_diskon1 = $this->customRound($transaction->persentase_diskon1 ?? 0);
        $persentase_diskon2 = $this->customRound($transaction->persentase_diskon2 ?? 0);
        $persentase_diskon3 = $this->customRound($transaction->persentase_diskon3 ?? 0);
        $persentase_diskon4 = $this->customRound($transaction->persentase_diskon4 ?? 0);
        $persentase_diskon5 = $this->customRound($transaction->persentase_diskon5 ?? 0);
        $persentase_diskon6 = $this->customRound($transaction->persentase_diskon6 ?? 0);
        $persentase_diskon7 = $this->customRound($transaction->persentase_diskon7 ?? 0);
        $persentase_diskon8 = $this->customRound($transaction->persentase_diskon8 ?? 0);
        $persentase_diskon9 = $this->customRound($transaction->persentase_diskon9 ?? 0);
        $persentase_diskon10 = $this->customRound($transaction->persentase_diskon10 ?? 0);
        $persentase_diskon11 = $this->customRound($transaction->persentase_diskon11 ?? 0);
        $persentase_diskon12 = $this->customRound($transaction->persentase_diskon12 ?? 0);
        
        // Calculate adjustment percentage
        $adjustmentPercentage = 0;
        if ($transaction->nominal_harga > 0 && $transaction->adjustment != 0) {
            $adjustmentPercentage = $this->customRound(abs(($transaction->adjustment / $transaction->nominal_harga) * 100));
        }
        
        // Calculate total percentage
        $totalPercentage = $this->customRound($persentase_diskon1 + $persentase_diskon2 + $persentase_diskon3 + 
                          $persentase_diskon4 + $persentase_diskon5 + $persentase_diskon6 + $persentase_diskon7 + 
                          $persentase_diskon8 + $persentase_diskon9 + $persentase_diskon10 + $persentase_diskon11 + 
                          $persentase_diskon12 + ($transaction->adjustment < 0 ? $adjustmentPercentage : 0));
        
        return [
            $no++,
            $transaction->tanggal_order ? Carbon::parse($transaction->tanggal_order)->format('d/m/Y') : '-',
            (string)($transaction->no_order ?? '-'),
            (string)($transaction->no_invoice ?? '-'),
            $taxStatus,
            $transaction->nominal_harga ?? 0,
            $transaction->nominal_diskon1 ?? 0, // BIAYA ADMIN (display as stored - negative to match other platforms)
            $transaction->nominal_diskon2 ?? 0, // BIAYA LAYANAN (display as stored - negative to match other platforms)
            $transaction->nominal_diskon3 ?? 0, // BIAYA 3 (display as stored - negative to match other platforms)
            $transaction->nominal_diskon4 ?? 0, // BIAYA 4 (display as stored - negative to match other platforms)
            $transaction->nominal_diskon5 ?? 0, // BIAYA 5 (display as stored - negative to match other platforms)
            $transaction->nominal_diskon6 ?? 0, // BIAYA 6 (display as stored - negative to match other platforms)
            $transaction->nominal_diskon7 ?? 0, // BIAYA 7
            $transaction->nominal_diskon8 ?? 0, // BIAYA 8
            $transaction->nominal_diskon9 ?? 0, // BIAYA 9
            $transaction->nominal_diskon10 ?? 0, // BIAYA 10
            $transaction->nominal_diskon11 ?? 0, // BIAYA 11
            $transaction->nominal_diskon12 ?? 0, // BIAYA 12
            $transaction->adjustment ?? 0,
            $transaction->nominal_fix ?? 0,
            $transaction->saldo_masuk ?? 0,
            $transaction->tanggal_masuk_pembayaran ? Carbon::parse($transaction->tanggal_masuk_pembayaran)->format('d/m/Y') : '-',
            $transaction->outstanding ?? 0,
            $persentase_diskon1,
            $persentase_diskon2,
            $persentase_diskon3,
            $persentase_diskon4,
            $persentase_diskon5,
            $persentase_diskon6,
            $persentase_diskon7,
            $persentase_diskon8,
            $persentase_diskon9,
            $persentase_diskon10,
            $persentase_diskon11,
            $persentase_diskon12,
            $adjustmentPercentage,
            $totalPercentage
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
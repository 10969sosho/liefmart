<?php

namespace App\Exports;

use App\Models\LazadaFinancialTransaction;
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
use Illuminate\Support\Facades\DB;

class LazadaFinanceAnalyticsExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder
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
        $query = LazadaFinancialTransaction::select([
            'id', 'tanggal_order', 'hari_order', 'no_order', 'no_invoice',
            'nominal_harga', 'nominal_diskon1', 'nominal_diskon2', 'nominal_diskon3', 'nominal_diskon4',
            'nominal_diskon5', 'nominal_diskon6',
            'persentase_diskon1', 'persentase_diskon2', 'persentase_diskon3', 'persentase_diskon4',
            'persentase_diskon5', 'persentase_diskon6',
            'adjustment', 'adjustment_description', 'total_persentase', 'nominal_fix', 'saldo_masuk',
            'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran', 'outstanding'
        ])
        ->orderBy('tanggal_order', 'desc');

        // Apply filters - matching LazadaFinanceController logic
        
        // Filter by payment date range
        if (isset($this->request['from_date']) && !empty($this->request['from_date'])) {
            $query->whereDate('lazada_financial_transactions.tanggal_masuk_pembayaran', '>=', $this->request['from_date']);
        }
        if (isset($this->request['to_date']) && !empty($this->request['to_date'])) {
            $query->whereDate('lazada_financial_transactions.tanggal_masuk_pembayaran', '<=', $this->request['to_date']);
        }
        
        // Filter by order date range
        if (isset($this->request['from_order_date']) && !empty($this->request['from_order_date'])) {
            $query->whereDate('lazada_financial_transactions.tanggal_order', '>=', $this->request['from_order_date']);
        }
        if (isset($this->request['to_order_date']) && !empty($this->request['to_order_date'])) {
            $query->whereDate('lazada_financial_transactions.tanggal_order', '<=', $this->request['to_order_date']);
        }
        
        // Filter by order number
        if (isset($this->request['order_number']) && !empty($this->request['order_number'])) {
            $query->where('lazada_financial_transactions.no_order', 'like', '%' . $this->request['order_number'] . '%');
        }
        
        // Filter by invoice number
        if (isset($this->request['invoice_number']) && !empty($this->request['invoice_number'])) {
            $query->where('lazada_financial_transactions.no_invoice', 'like', '%' . $this->request['invoice_number'] . '%');
        }
        
        // Filter by nominal range
        if (isset($this->request['min_nominal']) && !empty($this->request['min_nominal'])) {
            $query->where('lazada_financial_transactions.nominal_fix', '>=', $this->request['min_nominal']);
        }
        if (isset($this->request['max_nominal']) && !empty($this->request['max_nominal'])) {
            $query->where('lazada_financial_transactions.nominal_fix', '<=', $this->request['max_nominal']);
        }

        // Filter by outstanding status
        if (isset($this->request['outstanding_status']) && $this->request['outstanding_status'] !== '') {
            if ($this->request['outstanding_status'] === '0') {
                $query->join(DB::raw('(
                    SELECT no_order
                    FROM lazada_financial_transactions
                    GROUP BY no_order
                    HAVING SUM(outstanding) = 0
                ) as outstanding_zero'), 'lazada_financial_transactions.no_order', '=', 'outstanding_zero.no_order');
            } elseif ($this->request['outstanding_status'] === '1') {
                $query->join(DB::raw('(
                    SELECT no_order
                    FROM lazada_financial_transactions
                    GROUP BY no_order
                    HAVING SUM(outstanding) != 0
                ) as outstanding_nonzero'), 'lazada_financial_transactions.no_order', '=', 'outstanding_nonzero.no_order');
            }
        }

        // Exclude orders with retur penjualan
        $query->whereNotExists(function($subQuery) {
            $subQuery->select(DB::raw(1))
                ->from('retur_penjualans as rp')
                ->join('orders as o', 'rp.order_id', '=', 'o.id')
                ->whereColumn('o.order_number', 'lazada_financial_transactions.no_order')
                ->whereIn('rp.status', ['draft', 'selesai'])
                ->whereNotNull('o.order_number')
                ->where('o.order_number', '!=', '');
        });

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
            'Harga (Rp)',
            'Voucher (Rp)',
            'Komisi (Rp)',
            'Biaya Admin (Rp)',
            'Biaya Layanan (Rp)',
            'Biaya 5 (Rp)',
            'Biaya 6 (Rp)',
            'Adjustment (Rp)',
            'Keterangan Adjustment',
            'Nominal Fix (Rp)',
            'Saldo Masuk (Rp)',
            'Tanggal Pembayaran',
            'Outstanding (Rp)',
            'Voucher (%)',
            'Komisi (%)',
            'Biaya Admin (%)',
            'Biaya Layanan (%)',
            'Biaya 5 (%)',
            'Biaya 6 (%)',
            'Adjustment (%)',
            'Total (%)'
        ];
    }

    public function map($transaction): array
    {
        static $no = 1;
        
        // Determine tax status (attempting to use same logic as Shopee if applicable)
        $taxId = null;
        if ($transaction->no_invoice && strpos($transaction->no_invoice, 'HPNSDA-OLK/01') !== false) {
            $taxId = 1; // PKP - Coffee
        } elseif ($transaction->no_invoice && strpos($transaction->no_invoice, 'HPNSDA-OLK/02') !== false) {
            $taxId = 2; // Non PKP - Coffee
        } elseif ($transaction->no_invoice && strpos($transaction->no_invoice, 'HGNSDA-OL/01') !== false) {
            $taxId = 3; // PKP - Skincare
        } elseif ($transaction->no_invoice && strpos($transaction->no_invoice, 'HGNSDA-OL/02') !== false) {
            $taxId = 4; // Non PKP - Skincare
        } else {
            // Extract last two digits if possible
            if ($transaction->no_invoice && preg_match('/\/(\d{2})/', $transaction->no_invoice, $matches)) {
                $taxId = (int)$matches[1];
            }
        }
        $isPKP = in_array($taxId, [1, 3, 5, 7]);
        $taxStatus = $taxId ? ($isPKP ? 'PKP' : 'Non-PKP') : '-';
        
        // Calculate percentages with custom rounding
        $persentase_diskon1 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon1 / $transaction->nominal_harga) * 100)) : 0;
        $persentase_diskon2 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon2 / $transaction->nominal_harga) * 100)) : 0;
        $persentase_diskon3 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon3 / $transaction->nominal_harga) * 100)) : 0;
        $persentase_diskon4 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon4 / $transaction->nominal_harga) * 100)) : 0;
        $persentase_diskon5 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon5 / $transaction->nominal_harga) * 100)) : 0;
        $persentase_diskon6 = $transaction->nominal_harga > 0 ? 
            $this->customRound(abs(($transaction->nominal_diskon6 / $transaction->nominal_harga) * 100)) : 0;
        
        // Calculate adjustment percentage
        $adjustmentPercentage = 0;
        if ($transaction->nominal_harga > 0 && $transaction->adjustment != 0) {
            $adjustmentPercentage = $this->customRound(abs(($transaction->adjustment / $transaction->nominal_harga) * 100));
        }
        
        // Calculate total percentage
        $totalPercentage = $this->customRound($persentase_diskon1 + $persentase_diskon2 + $persentase_diskon3 + 
                          $persentase_diskon4 + $persentase_diskon5 + $persentase_diskon6 + 
                          ($transaction->adjustment < 0 ? $adjustmentPercentage : 0));
        
        return [
            $no++,
            $transaction->tanggal_order ? Carbon::parse($transaction->tanggal_order)->format('d/m/Y') : '-',
            (string)($transaction->no_order ?? '-'),
            (string)($transaction->no_invoice ?? '-'),
            $taxStatus,
            $transaction->nominal_harga ?? 0,
            $transaction->nominal_diskon1 ?? 0,
            $transaction->nominal_diskon2 ?? 0,
            $transaction->nominal_diskon3 ?? 0,
            $transaction->nominal_diskon4 ?? 0,
            $transaction->nominal_diskon5 ?? 0,
            $transaction->nominal_diskon6 ?? 0,
            $transaction->adjustment ?? 0,
            $transaction->adjustment_description ?? '-',
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
            $adjustmentPercentage,
            $totalPercentage
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}

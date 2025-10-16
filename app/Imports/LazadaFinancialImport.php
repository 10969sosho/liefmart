<?php

namespace App\Imports;

use App\Models\LazadaFinancialTransaction;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class LazadaFinancialImport implements ToCollection, WithMultipleSheets
{
    protected $data = [];
    protected $invalidData = [];
    protected $headerIssues = [];
    protected $headerRowIndex = 0;
    protected $columnMapping = [];
    protected $totalRows = 0;
    protected $skippedRows = 0;
    protected $processedRows = 0;

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = count($rows);
        \Log::info('Total rows in Lazada Financial Excel:', ['count' => $this->totalRows]);

        try {
            $headerRowIndex = $this->findHeaderRow($rows);

            if ($headerRowIndex === false) {
                $this->headerIssues[] = 'Format header tidak ditemukan. Pastikan file memiliki header yang sesuai.';
                \Log::error('Header row not found in Lazada Financial Excel file');
                return;
            }

            $this->headerRowIndex = $headerRowIndex;
            $headers = $rows[$headerRowIndex];
            \Log::info('Lazada Financial Headers:', $headers->toArray());

            $this->mapColumns($headers);
        } catch (\Exception $e) {
            \Log::error('Error finding header row or mapping columns for Lazada Financial: ' . $e->getMessage());
            $this->headerIssues[] = 'Error saat mencari header atau mapping kolom: ' . $e->getMessage();
            return;
        }

        // Validate column mapping
        if (!$this->validateColumnMapping()) {
            return;
        }

        // Process data rows
        for ($i = $headerRowIndex + 1; $i < $this->totalRows; $i++) {
            $row = $rows[$i];
            if ($row->filter()->isEmpty()) { // Skip empty rows
                continue;
            }

            try {
                $processedRow = $this->processRow($row);

                if ($this->shouldSkipRow($processedRow)) {
                    $this->skippedRows++;
                    continue;
                }

                $this->data[] = $processedRow;
                $this->processedRows++;
            } catch (\Exception $e) {
                \Log::error("Error processing row $i for Lazada Financial: " . $e->getMessage());
                $this->invalidData[] = 'Baris #'.($i - $headerRowIndex).': Error: ' . $e->getMessage();
            }
        }

        \Log::info('Final Lazada Financial Data count:', ['count' => count($this->data)]);
    }

    protected function findHeaderRow(Collection $rows)
    {
        $expectedHeaders = [
            'NOMOR PESANAN', 'TANGGAL MASUK PEMBAYARAN', 'HARI MASUK PEMBAYARAN', 'JUMLAH MASUK PEMBAYARAN',
            'BIAYA PROSES FIX', 'GRATIS ONGKIR', 'BIAYA ADMIN', 'BIAYA TRANSAKSI', 'DISKON 5', 'DISKON 6', 
            'DISKON 7', 'DISKON 8', 'DISKON 9', 'DISKON 10', 'DISKON 11', 'DISKON 12'
        ];

        foreach ($rows as $index => $row) {
            $rowArray = array_map('strtoupper', array_map('trim', $row->filter()->toArray()));
            $matchedCount = count(array_intersect($expectedHeaders, $rowArray));
            if ($matchedCount >= 4) { // At least 4 core headers must match
                return $index;
            }
        }
        return false;
    }

    protected function mapColumns($headers)
    {
        $exactHeaderMapping = [
            'no_order' => 'NOMOR PESANAN',
            'tanggal_masuk_pembayaran' => 'TANGGAL MASUK PEMBAYARAN',
            'hari_masuk_pembayaran' => 'HARI MASUK PEMBAYARAN',
            'saldo_masuk' => 'JUMLAH MASUK PEMBAYARAN',
            'nominal_diskon1' => 'BIAYA PROSES FIX',
            'nominal_diskon2' => 'GRATIS ONGKIR',
            'nominal_diskon3' => 'BIAYA ADMIN',
            'nominal_diskon4' => 'BIAYA TRANSAKSI',
            'nominal_diskon5' => 'DISKON 5',
            'nominal_diskon6' => 'DISKON 6',
            'nominal_diskon7' => 'DISKON 7',
            'nominal_diskon8' => 'DISKON 8',
            'nominal_diskon9' => 'DISKON 9',
            'nominal_diskon10' => 'DISKON 10',
            'nominal_diskon11' => 'DISKON 11',
            'nominal_diskon12' => 'DISKON 12',
        ];

        $this->columnMapping = [];
        foreach ($exactHeaderMapping as $field => $exactHeader) {
            $index = $headers->search(function ($header) use ($exactHeader) {
                return strtoupper(trim($header)) === strtoupper(trim($exactHeader));
            });
            $this->columnMapping[$field] = $index !== false ? $index : null;
        }
    }

    protected function validateColumnMapping()
    {
        // Only require core columns
        $requiredColumns = ['no_order', 'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran', 'saldo_masuk'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $field) {
            if (!isset($this->columnMapping[$field]) || $this->columnMapping[$field] === null) {
                $missingColumns[] = $field;
            }
        }

        if (!empty($missingColumns)) {
            $this->headerIssues[] = 'Kolom wajib berikut tidak ditemukan: ' . implode(', ', $missingColumns);
            return false;
        }
        return true;
    }

    protected function processRow(Collection $row)
    {
        $processed = [];
        foreach ($this->columnMapping as $field => $index) {
            $value = ($index !== null && isset($row[$index])) ? $row[$index] : null;
            $processed[$field] = $value;
        }

        // Convert Excel date to Carbon date
        if (isset($processed['tanggal_masuk_pembayaran']) && is_numeric($processed['tanggal_masuk_pembayaran'])) {
            $processed['tanggal_masuk_pembayaran'] = Carbon::instance(Date::excelToDateTimeObject($processed['tanggal_masuk_pembayaran']));
        } else if (isset($processed['tanggal_masuk_pembayaran'])) {
            try {
                $processed['tanggal_masuk_pembayaran'] = Carbon::parse($processed['tanggal_masuk_pembayaran']);
            } catch (\Exception $e) {
                $processed['tanggal_masuk_pembayaran'] = null;
            }
        }

        // Ensure numeric values are correctly parsed
        $processed['saldo_masuk'] = (float) ($processed['saldo_masuk'] ?? 0);
        $processed['nominal_diskon1'] = (float) ($processed['nominal_diskon1'] ?? 0);
        $processed['nominal_diskon2'] = (float) ($processed['nominal_diskon2'] ?? 0);
        $processed['nominal_diskon3'] = (float) ($processed['nominal_diskon3'] ?? 0);
        $processed['nominal_diskon4'] = (float) ($processed['nominal_diskon4'] ?? 0);
        $processed['nominal_diskon5'] = (float) ($processed['nominal_diskon5'] ?? 0);
        $processed['nominal_diskon6'] = (float) ($processed['nominal_diskon6'] ?? 0);
        $processed['nominal_diskon7'] = (float) ($processed['nominal_diskon7'] ?? 0);
        $processed['nominal_diskon8'] = (float) ($processed['nominal_diskon8'] ?? 0);
        $processed['nominal_diskon9'] = (float) ($processed['nominal_diskon9'] ?? 0);
        $processed['nominal_diskon10'] = (float) ($processed['nominal_diskon10'] ?? 0);
        $processed['nominal_diskon11'] = (float) ($processed['nominal_diskon11'] ?? 0);
        $processed['nominal_diskon12'] = (float) ($processed['nominal_diskon12'] ?? 0);

        return $processed;
    }

    protected function shouldSkipRow($row)
    {
        // Skip if essential fields are missing
        if (empty($row['no_order']) || empty($row['saldo_masuk'])) {
            return true;
        }
        return false;
    }

    public function saveToDatabase()
    {
        DB::beginTransaction();
        try {
            $transactionsCreated = 0;
            $ordersNotFound = 0;

            foreach ($this->data as $rowData) {
                // Find the order by order number
                $order = Order::where('order_number', $rowData['no_order'])->first();
                
                if (!$order) {
                    $ordersNotFound++;
                    \Log::warning("Order not found for Lazada Financial: " . $rowData['no_order']);
                    continue;
                }

                // Create financial transaction with new structure
                $transaction = new LazadaFinancialTransaction();
                $transaction->setDataFromOrder($order);
                $transaction->tanggal_masuk_pembayaran = $rowData['tanggal_masuk_pembayaran'];
                $transaction->hari_masuk_pembayaran = $rowData['hari_masuk_pembayaran'];
                $transaction->saldo_masuk = $rowData['saldo_masuk'];
                $transaction->nominal_diskon1 = $rowData['nominal_diskon1'];
                $transaction->nominal_diskon2 = $rowData['nominal_diskon2'];
                $transaction->nominal_diskon3 = $rowData['nominal_diskon3'];
                $transaction->nominal_diskon4 = $rowData['nominal_diskon4'];
                $transaction->nominal_diskon5 = $rowData['nominal_diskon5'];
                $transaction->nominal_diskon6 = $rowData['nominal_diskon6'];
                $transaction->nominal_diskon7 = $rowData['nominal_diskon7'];
                $transaction->nominal_diskon8 = $rowData['nominal_diskon8'];
                $transaction->nominal_diskon9 = $rowData['nominal_diskon9'];
                $transaction->nominal_diskon10 = $rowData['nominal_diskon10'];
                $transaction->nominal_diskon11 = $rowData['nominal_diskon11'];
                $transaction->nominal_diskon12 = $rowData['nominal_diskon12'];
                
                // Calculate derived fields
                $transaction->calculateNominalFix();
                $transaction->calculateOutstanding();
                $transaction->calculatePercentages();
                
                $transaction->save();
                
                $transactionsCreated++;
            }

            DB::commit();
            return [
                'success' => true,
                'message' => "Import berhasil. {$transactionsCreated} transaksi keuangan berhasil ditambahkan." . 
                           ($ordersNotFound > 0 ? " {$ordersNotFound} order tidak ditemukan." : "")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving Lazada Financial import data to database: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data ke database: ' . $e->getMessage()
            ];
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getInvalidData()
    {
        return $this->invalidData;
    }

    public function getHeaderIssues()
    {
        return $this->headerIssues;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function getProcessedRows()
    {
        return $this->processedRows;
    }

    public function getSkippedRows()
    {
        return $this->skippedRows;
    }

    public function getStats()
    {
        return [
            'total_rows' => $this->totalRows,
            'processed_rows' => $this->processedRows,
            'skipped_rows' => $this->skippedRows,
            'invalid_data' => count($this->invalidData),
            'header_issues' => count($this->headerIssues)
        ];
    }
}

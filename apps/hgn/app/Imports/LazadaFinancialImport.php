<?php

namespace App\Imports;

use App\Models\LazadaFinancialTransaction;
use App\Models\Order;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class LazadaFinancialImport implements ToCollection, WithHeadingRow, WithStartRow
{
    protected $platform;
    protected $data = [];
    protected $issues = [];
    protected $previewData = [];
    
    public function __construct($platformId)
    {
        $this->platform = Platform::find($platformId);
        if (!$this->platform) {
            $this->platform = Platform::whereRaw('LOWER(name) = ?', ['lazada'])->first();
        }
    }
    
    /**
     * Start reading from row 1 (header row)
     */
    public function startRow(): int
    {
        return 1;
    }
    
    public function collection(Collection $rows)
    {
        $groupedData = [];
        $currentOrder = null;
        
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowArray))) {
                continue;
            }
            
            // Get column values (handle different possible column names - case insensitive)
            $namaBiaya = '';
            $nominalBiaya = 0;
            $tanggalPembayaran = '';
            $hariPembayaran = '';
            $nomorPesanan = '';
            $uangMasuk = 0;
            
            // Try to find columns with case-insensitive matching
            foreach ($rowArray as $key => $value) {
                $keyUpper = strtoupper(trim($key));
                $value = is_string($value) ? trim($value) : $value;
                
                if (strpos($keyUpper, 'NAMA') !== false && strpos($keyUpper, 'BIAYA') !== false) {
                    $namaBiaya = $value;
                } elseif (strpos($keyUpper, 'NOMINAL') !== false && strpos($keyUpper, 'BIAYA') !== false) {
                    $nominalBiaya = $this->parseNumber($value);
                } elseif (strpos($keyUpper, 'TANGGAL') !== false && strpos($keyUpper, 'PEMBAYARAN') !== false) {
                    $tanggalPembayaran = $value;
                } elseif (strpos($keyUpper, 'HARI') !== false && strpos($keyUpper, 'PEMBAYARAN') !== false) {
                    $hariPembayaran = $value;
                } elseif (strpos($keyUpper, 'NOMOR') !== false && (strpos($keyUpper, 'PESANAN') !== false || strpos($keyUpper, 'ORDER') !== false)) {
                    $nomorPesanan = $value;
                } elseif (strpos($keyUpper, 'UANG') !== false && strpos($keyUpper, 'MASUK') !== false) {
                    $uangMasuk = $this->parseNumber($value);
                }
            }
            
            // If we have order number, this is start of new group
            if (!empty($nomorPesanan)) {
                $currentOrder = $nomorPesanan;
                
                if (!isset($groupedData[$currentOrder])) {
                    $groupedData[$currentOrder] = [
                        'no_order' => $nomorPesanan,
                        'tanggal_masuk_pembayaran' => $tanggalPembayaran,
                        'hari_masuk_pembayaran' => $hariPembayaran,
                        'saldo_masuk' => $uangMasuk,
                        'biaya' => [],
                        'nominal_harga' => 0,
                        'nominal_diskon1' => 0, // Biaya Proses Fix
                        'nominal_diskon2' => 0, // Gratis Ongkir
                        'nominal_diskon3' => 0, // Biaya Admin
                        'nominal_diskon4' => 0, // Biaya Transaksi
                        'nominal_diskon5' => 0,
                        'nominal_diskon6' => 0,
                    ];
                } else {
                    // Update payment info if provided again
                    if (!empty($tanggalPembayaran)) {
                        $groupedData[$currentOrder]['tanggal_masuk_pembayaran'] = $tanggalPembayaran;
                    }
                    if (!empty($hariPembayaran)) {
                        $groupedData[$currentOrder]['hari_masuk_pembayaran'] = $hariPembayaran;
                    }
                    if ($uangMasuk > 0) {
                        $groupedData[$currentOrder]['saldo_masuk'] += $uangMasuk;
                    }
                }
            } elseif ($currentOrder && !empty($tanggalPembayaran)) {
                // Update payment info for current group if order number is empty but date is provided
                $groupedData[$currentOrder]['tanggal_masuk_pembayaran'] = $tanggalPembayaran;
                $groupedData[$currentOrder]['hari_masuk_pembayaran'] = $hariPembayaran;
                if ($uangMasuk > 0) {
                    $groupedData[$currentOrder]['saldo_masuk'] += $uangMasuk;
                }
            }
            
            // Process biaya based on nama biaya
            if (!empty($namaBiaya) && $currentOrder) {
                $namaBiayaUpper = strtoupper(trim($namaBiaya));
                $nominalAbs = abs($nominalBiaya);
                
                // Match exact or partial nama biaya
                if (strpos($namaBiayaUpper, 'HARGA SETELAH DISKON') !== false) {
                    $groupedData[$currentOrder]['nominal_harga'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'BIAYA PROSES FIX') !== false || 
                          $namaBiayaUpper === 'PROSES FIX') {
                    $groupedData[$currentOrder]['nominal_diskon1'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'GRATIS ONGKIR') !== false || 
                          strpos($namaBiayaUpper, 'ONGKIR') !== false && strpos($namaBiayaUpper, 'GRATIS') !== false) {
                    $groupedData[$currentOrder]['nominal_diskon2'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'BIAYA ADMIN') !== false || 
                          ($namaBiayaUpper === 'ADMIN' || $namaBiayaUpper === 'BIAYA ADMIN')) {
                    $groupedData[$currentOrder]['nominal_diskon3'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'BIAYA TRANSAKSI') !== false || 
                          ($namaBiayaUpper === 'TRANSAKSI' || $namaBiayaUpper === 'BIAYA TRANSAKSI')) {
                    $groupedData[$currentOrder]['nominal_diskon4'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'DISKON 5') !== false || 
                          strpos($namaBiayaUpper, 'BIAYA 5') !== false ||
                          $namaBiayaUpper === 'DISKON5' || $namaBiayaUpper === 'BIAYA5') {
                    $groupedData[$currentOrder]['nominal_diskon5'] += $nominalAbs;
                } elseif (strpos($namaBiayaUpper, 'DISKON 6') !== false || 
                          strpos($namaBiayaUpper, 'BIAYA 6') !== false ||
                          $namaBiayaUpper === 'DISKON6' || $namaBiayaUpper === 'BIAYA6') {
                    $groupedData[$currentOrder]['nominal_diskon6'] += $nominalAbs;
                }
                
                // Store biaya detail for preview
                $groupedData[$currentOrder]['biaya'][] = [
                    'nama' => $namaBiaya,
                    'nominal' => $nominalBiaya
                ];
            }
        }
        
        // Process grouped data into transactions
        foreach ($groupedData as $orderNumber => $orderData) {
            // Find order with orderItems
            $order = Order::with('orderItems')
                ->where('order_number', $orderNumber)
                ->where('platform_id', $this->platform->id)
                ->first();
            
            // Calculate nominal_fix
            $nominalFix = $orderData['nominal_harga'] 
                - $orderData['nominal_diskon1'] 
                - $orderData['nominal_diskon2'] 
                - $orderData['nominal_diskon3'] 
                - $orderData['nominal_diskon4']
                - $orderData['nominal_diskon5']
                - $orderData['nominal_diskon6'];
            
            // Calculate outstanding
            $outstanding = $nominalFix - $orderData['saldo_masuk'];
            
            // Parse tanggal
            $tanggalOrder = $order ? $order->tanggal : null;
            $hariOrder = $order ? $order->hari : null;
            
            try {
                $tanggalPembayaran = $this->parseDate($orderData['tanggal_masuk_pembayaran']);
            } catch (\Exception $e) {
                // Will be added to rowIssues in validation section
                $tanggalPembayaran = null;
            }
            
            // Validate
            $isValid = true;
            $rowIssues = [];
            
            if (empty($orderNumber)) {
                $isValid = false;
                $rowIssues[] = 'Nomor pesanan kosong';
            }
            
            if (!$order) {
                $isValid = false;
                $rowIssues[] = "Order dengan nomor {$orderNumber} tidak ditemukan";
            }
            
            if (empty($orderData['tanggal_masuk_pembayaran'])) {
                $isValid = false;
                $rowIssues[] = 'Tanggal pembayaran kosong';
            }
            
            if ($orderData['nominal_harga'] == 0) {
                $isValid = false;
                $rowIssues[] = 'Nominal harga tidak ditemukan atau 0';
            }
            
            if ($tanggalPembayaran === null && !empty($orderData['tanggal_masuk_pembayaran'])) {
                $isValid = false;
                $rowIssues[] = 'Format tanggal pembayaran tidak valid: ' . $orderData['tanggal_masuk_pembayaran'];
            }
            
            $previewRow = [
                '_valid' => $isValid,
                'no_order' => $orderNumber,
                'tanggal_order' => $tanggalOrder ? $tanggalOrder->format('Y-m-d') : null,
                'hari_order' => $hariOrder,
                'no_invoice' => $order ? ('PREVIEW-' . $order->order_number) : 'PREVIEW-N/A',
                'qty' => $order ? $order->orderItems->sum('quantity') : 0,
                'nominal_harga' => $orderData['nominal_harga'],
                'nominal_diskon1' => -$orderData['nominal_diskon1'],
                'nominal_diskon2' => -$orderData['nominal_diskon2'],
                'nominal_diskon3' => -$orderData['nominal_diskon3'],
                'nominal_diskon4' => -$orderData['nominal_diskon4'],
                'nominal_diskon5' => -$orderData['nominal_diskon5'],
                'nominal_diskon6' => -$orderData['nominal_diskon6'],
                'adjustment' => 0,
                'nominal_fix' => $nominalFix,
                'saldo_masuk' => $orderData['saldo_masuk'],
                'tanggal_masuk_pembayaran' => $tanggalPembayaran ? $tanggalPembayaran->format('Y-m-d') : null,
                'hari_masuk_pembayaran' => $orderData['hari_masuk_pembayaran'],
                'outstanding' => $outstanding,
                'tax_id' => $order ? ($order->tax_id ?? null) : null,
            ];
            
            if (!$isValid) {
                // Format issues as array with row number for compatibility
                $rowIndex = count($this->previewData) + 1;
                $this->issues[$rowIndex] = $rowIssues;
            }
            
            $this->previewData[] = $previewRow;
            $this->data[] = [
                '_valid' => $isValid,
                'order_id' => $order ? $order->id : null,
                'order_number' => $orderNumber,
                'order_data' => $orderData,
                'preview_row' => $previewRow,
            ];
        }
    }
    
    protected function parseNumber($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^\d.,-]/', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return (float) $cleaned;
    }
    
    protected function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        // Try different date formats
        $formats = [
            'd M Y',      // 10 Nov 2025
            'd-m-Y',      // 10-11-2025
            'd/m/Y',      // 10/11/2025
            'Y-m-d',      // 2025-11-10
            'd M Y H:i',  // 10 Nov 2025 00:00
        ];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateString));
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try Carbon parse as fallback
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Cannot parse date: {$dateString}");
        }
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getPreviewData()
    {
        return $this->previewData;
    }
    
    public function getIssues()
    {
        return $this->issues;
    }
}


<?php

namespace App\Imports;

use App\Models\ArusKasBlibli;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ArusKasBlibliImport implements ToCollection, WithHeadingRow
{
    private $validData = [];
    private $invalidData = [];
    private $duplicates = [];
    private $totalRecords = 0;

    public function collection(Collection $rows)
    {
        $this->totalRecords = count($rows);



        foreach ($rows as $row) {
            try {
                // Map Excel headers to expected keys with more variations
                $tanggalPembayaran = $row['tanggal_pembayaran'] ?? $row['tanggal pembayaran'] ?? null;
                $deskripsi = $row['deskripsi'] ?? null;
                $noPesanan = $row['no_pesanan'] ?? $row['no. pesanan'] ?? $row['no pesanan'] ?? null;
                $tanggalPesanan = $row['tanggal_pesanan'] ?? $row['tanggal pesanan'] ?? null;
                $pembayaran = $row['pembayaran'] ?? null;
                $saldoAkhir = $row['saldo_akhir'] ?? $row['saldo akhir'] ?? $row['saldoakhir'] ?? null;

                // Skip empty rows
                if (empty($tanggalPembayaran) && empty($deskripsi)) {
                    continue;
                }

                // Parse tanggal_pembayaran
                $tanggalPembayaranParsed = null;
                if (!empty($tanggalPembayaran)) {
                    try {
                        if (is_numeric($tanggalPembayaran)) {
                            $tanggalPembayaranParsed = Date::excelToDateTimeObject($tanggalPembayaran);
                        } else {
                            $tanggalPembayaranParsed = Carbon::parse($tanggalPembayaran);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error parsing tanggal_pembayaran: ' . $e->getMessage() . ' Value: ' . $tanggalPembayaran);
                        $tanggalPembayaranParsed = null;
                    }
                }

                // Parse tanggal_pesanan
                $tanggalPesananParsed = null;
                if (!empty($tanggalPesanan)) {
                    try {
                        if (is_numeric($tanggalPesanan)) {
                            $tanggalPesananParsed = Date::excelToDateTimeObject($tanggalPesanan);
                        } else {
                            $tanggalPesananParsed = Carbon::parse($tanggalPesanan);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error parsing tanggal_pesanan: ' . $e->getMessage() . ' Value: ' . $tanggalPesanan);
                        $tanggalPesananParsed = null;
                    }
                }

                // Parse numbers (robust for various formats)
                $pembayaranValue = 0;
                if (!is_null($pembayaran)) {
                    if (is_numeric($pembayaran)) {
                        $pembayaranValue = (float)$pembayaran;
                    } else {
                        $pembayaranStr = str_replace([' ', "\xC2\xA0"], '', (string)$pembayaran);
                        
                        // Jika ada koma dan titik
                        if (strpos($pembayaranStr, ',') !== false && strpos($pembayaranStr, '.') !== false) {
                            if (strpos($pembayaranStr, ',') > strpos($pembayaranStr, '.')) {
                                // Format Eropa: 146.091,00
                                $pembayaranStr = str_replace('.', '', $pembayaranStr);
                                $pembayaranStr = str_replace(',', '.', $pembayaranStr);
                            } else {
                                // Format US: 146,091.00
                                $pembayaranStr = str_replace(',', '', $pembayaranStr);
                            }
                        } else if (strpos($pembayaranStr, ',') !== false) {
                            // Hanya koma, asumsikan desimal
                            $pembayaranStr = str_replace(',', '.', $pembayaranStr);
                        } else if (strpos($pembayaranStr, '.') !== false) {
                            // Hanya titik, asumsikan desimal
                            // Tidak perlu diubah
                        }
                        $pembayaranValue = is_numeric($pembayaranStr) ? (float)$pembayaranStr : 0;
                    }
                }
                $saldoAkhirValue = 0;
                if (!is_null($saldoAkhir)) {
                    if (is_numeric($saldoAkhir)) {
                        $saldoAkhirValue = (float)$saldoAkhir;
                    } else {
                        $saldoAkhirStr = str_replace([' ', "\xC2\xA0"], '', (string)$saldoAkhir);
                        
                        if (strpos($saldoAkhirStr, ',') !== false && strpos($saldoAkhirStr, '.') !== false) {
                            if (strpos($saldoAkhirStr, ',') > strpos($saldoAkhirStr, '.')) {
                                $saldoAkhirStr = str_replace('.', '', $saldoAkhirStr);
                                $saldoAkhirStr = str_replace(',', '.', $saldoAkhirStr);
                            } else {
                                $saldoAkhirStr = str_replace(',', '', $saldoAkhirStr);
                            }
                        } else if (strpos($saldoAkhirStr, ',') !== false) {
                            $saldoAkhirStr = str_replace(',', '.', $saldoAkhirStr);
                        }
                        $saldoAkhirValue = is_numeric($saldoAkhirStr) ? (float)$saldoAkhirStr : 0;
                    }
                }

                // Validate required fields
                $invalidReasons = [];
                if (!$tanggalPembayaranParsed) {
                    $invalidReasons[] = 'Tanggal pembayaran tidak valid: ' . ($tanggalPembayaran ?: 'kosong');
                }
                if (empty($deskripsi)) {
                    $invalidReasons[] = 'Deskripsi kosong';
                }
                
                // Jangan validasi pembayaran/saldo akhir dulu
                // Biasanya nol juga valid untuk beberapa transaksi
                
                if (count($invalidReasons) > 0) {
                    Log::warning('Data invalid pada import ArusKasBlibli', [
                        'reasons' => $invalidReasons,
                        'row_data' => json_encode($row)
                    ]);
                    $this->invalidData[] = [
                        'data' => $row,
                        'issues' => $invalidReasons,
                    ];
                    continue;
                }

                // Check for duplicates
                $existingRecord = ArusKasBlibli::where('tanggal_pembayaran', $tanggalPembayaranParsed)
                    ->where('deskripsi', $deskripsi)
                    ->where('pembayaran', $pembayaranValue)
                    ->first();

                if ($existingRecord) {
                    $this->duplicates[] = $row;
                    continue;
                }



                // Format data for database
                $this->validData[] = [
                    'tanggal_pembayaran' => $tanggalPembayaranParsed,
                    'deskripsi' => $deskripsi,
                    'no_pesanan' => $noPesanan,
                    'tanggal_pesanan' => $tanggalPesananParsed,
                    'pembayaran' => $pembayaranValue,
                    'saldo_akhir' => $saldoAkhirValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                Log::error('Error processing row: ' . $e->getMessage());
                $this->invalidData[] = $row;
            }
        }
    }

    public function getValidData()
    {
        return $this->validData;
    }

    public function getInvalidData()
    {
        return $this->invalidData;
    }

    public function getDuplicates()
    {
        return $this->duplicates;
    }

    public function getTotalRecords()
    {
        return $this->totalRecords;
    }
} 
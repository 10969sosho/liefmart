<?php

namespace App\Imports;

use App\Models\ArusKasTiktok2Import;
use App\Models\Platform;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArusKasTiktok2Import implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $platform;
    protected $importedCount = 0;
    protected $errors = [];

    public function __construct()
    {
        $this->platform = Platform::where('name', 'tiktok2')->first();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                try {
                    $this->processRow($row);
                    $this->importedCount++;
                } catch (\Exception $e) {
                    $this->errors[] = "Row " . ($row->getIndex() + 2) . ": " . $e->getMessage();
                    Log::error('ArusKasTiktok2 Import Error', [
                        'row' => $row->toArray(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function processRow($row)
    {
        // Create cash flow record
        ArusKasTiktok2Import::create([
            'tanggal_pembayaran' => $row['tanggal_pembayaran'] ?? $row['payment_date'] ?? now(),
            'deskripsi' => $row['deskripsi'] ?? $row['description'] ?? '',
            'no_pesanan' => $row['no_pesanan'] ?? $row['order_number'] ?? '',
            'tanggal_pesanan' => $row['tanggal_pesanan'] ?? $row['order_date'] ?? null,
            'pembayaran' => $row['pembayaran'] ?? $row['payment'] ?? 0,
            'saldo_akhir' => $row['saldo_akhir'] ?? $row['ending_balance'] ?? 0,
            'platform_id' => $this->platform->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal_pembayaran' => 'required|date',
            'no_pesanan' => 'required|string',
            'pembayaran' => 'required|numeric',
            'saldo_akhir' => 'required|numeric',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'tanggal_pembayaran.required' => 'Tanggal pembayaran harus diisi',
            'tanggal_pembayaran.date' => 'Tanggal pembayaran harus berupa tanggal yang valid',
            'no_pesanan.required' => 'Nomor pesanan harus diisi',
            'pembayaran.required' => 'Pembayaran harus diisi',
            'pembayaran.numeric' => 'Pembayaran harus berupa angka',
            'saldo_akhir.required' => 'Saldo akhir harus diisi',
            'saldo_akhir.numeric' => 'Saldo akhir harus berupa angka',
        ];
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

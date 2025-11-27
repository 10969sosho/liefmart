<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ArusKasTiktok2Import extends Model
{
    use HasFactory;

    protected $table = 'arus_kas_tiktok2_imports';

    protected $fillable = [
        'tanggal_pembayaran',
        'deskripsi',
        'no_pesanan',
        'tanggal_pesanan',
        'pembayaran',
        'saldo_akhir',
        'platform_id',
        'raw_tanggal_pembayaran', // Store original Excel format
        'raw_tanggal_pesanan',    // Store original Excel format
        'raw_pembayaran',         // Store original Excel format
        'raw_saldo_akhir',        // Store original Excel format
        'excel_row_number',       // Store original Excel row number for sorting
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'tanggal_pesanan' => 'date',
        'pembayaran' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
    ];

    /**
     * Get the platform that this cash flow belongs to
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Get formatted tanggal pembayaran in readable format
     */
    public function getFormattedTanggalPembayaranAttribute()
    {
        // Try to format the raw value first if it exists
        if ($this->raw_tanggal_pembayaran && $this->raw_tanggal_pembayaran !== '-') {
            try {
                // Handle Excel serial date (numeric format)
                if (is_numeric($this->raw_tanggal_pembayaran)) {
                    return \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays($this->raw_tanggal_pembayaran)
                        ->format('d/m/Y');
                } else {
                    // Try to parse and format existing date
                    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                    foreach ($formats as $format) {
                        try {
                            return \Carbon\Carbon::createFromFormat($format, $this->raw_tanggal_pembayaran)->format('d/m/Y');
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    // Last resort
                    return \Carbon\Carbon::parse($this->raw_tanggal_pembayaran)->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Fall back to raw if parsing fails
                return $this->raw_tanggal_pembayaran;
            }
        }
        
        // Use the parsed date if available
        return $this->tanggal_pembayaran ? $this->tanggal_pembayaran->format('d/m/Y') : '';
    }

    /**
     * Get formatted tanggal pesanan in readable format
     */
    public function getFormattedTanggalPesananAttribute()
    {
        // Try to format the raw value first if it exists
        if ($this->raw_tanggal_pesanan && $this->raw_tanggal_pesanan !== '-') {
            try {
                // Handle Excel serial date (numeric format)
                if (is_numeric($this->raw_tanggal_pesanan)) {
                    return \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays($this->raw_tanggal_pesanan)
                        ->format('d/m/Y');
                } else {
                    // Try to parse and format existing date
                    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                    foreach ($formats as $format) {
                        try {
                            return \Carbon\Carbon::createFromFormat($format, $this->raw_tanggal_pesanan)->format('d/m/Y');
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    // Last resort
                    return \Carbon\Carbon::parse($this->raw_tanggal_pesanan)->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Fall back to raw if parsing fails
                return $this->raw_tanggal_pesanan;
            }
        }
        
        // Use the parsed date if available
        return $this->tanggal_pesanan ? $this->tanggal_pesanan->format('d/m/Y') : '-';
    }

    /**
     * Get formatted pembayaran as it appears in Excel
     */
    public function getFormattedPembayaranAttribute()
    {
        // Always format with dot as thousand separator
        $value = $this->raw_pembayaran ? (float)str_replace(['.', ','], ['', '.'], $this->raw_pembayaran) : $this->pembayaran;
        return number_format($value, 0, ',', '.');
    }

    /**
     * Get formatted saldo akhir as it appears in Excel
     */
    public function getFormattedSaldoAkhirAttribute()
    {
        // Always format with dot as thousand separator
        $value = $this->raw_saldo_akhir ? (float)str_replace(['.', ','], ['', '.'], $this->raw_saldo_akhir) : $this->saldo_akhir;
        return number_format($value, 0, ',', '.');
    }
}

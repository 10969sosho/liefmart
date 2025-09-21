<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArusKasShopeeImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_pemasukan',
        'tipe_transaksi',
        'deskripsi',
        'no_pesanan',
        'tanggal_pesanan',
        'jenis_transaksi',
        'pemasukan',
        'status',
        'saldo_akhir',
        'platform_id',
    ];

    protected $casts = [
        'tanggal_pemasukan' => 'date',
        'tanggal_pesanan' => 'date',
        'pemasukan' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
    ];

    /**
     * Get the platform that this cash flow belongs to
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
} 
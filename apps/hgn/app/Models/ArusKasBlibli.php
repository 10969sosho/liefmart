<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArusKasBlibli extends Model
{
    use HasFactory;

    protected $table = 'arus_kas_blibli';

    protected $fillable = [
        'tanggal_pembayaran',
        'deskripsi',
        'no_pesanan',
        'tanggal_pesanan',
        'pembayaran',
        'saldo_akhir'
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'tanggal_pesanan' => 'date',
        'pembayaran' => 'decimal:2',
        'saldo_akhir' => 'decimal:2'
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereDate('tanggal_pembayaran', '>=', $startDate)
                     ->whereDate('tanggal_pembayaran', '<=', $endDate);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'platform',
        'old_value',
        'new_value',
        'description',
        'user_id',
    ];

    // Relasi ke user yang melakukan adjustment
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke transaksi (fleksibel, bisa di-extend ke polymorphic jika multi platform)
    public function transaction()
    {
        // Default: relasi ke ShopeeFinancialTransaction, bisa diubah sesuai kebutuhan
        return $this->belongsTo(ShopeeFinancialTransaction::class, 'transaction_id');
    }
} 
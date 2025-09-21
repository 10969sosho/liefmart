<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArusKasTokopediaImport extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tanggal_masuk_pembayaran',
        'hari_masuk_pembayaran',
        'mutation_type',
        'description',
        'nominal',
        'balance',
        'platform_id',
    ];
    
    protected $casts = [
        'tanggal_masuk_pembayaran' => 'date',
        'nominal' => 'decimal:2',
        'balance' => 'decimal:2',
    ];
    
    /**
     * Get the platform that this cash flow belongs to
     */
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}

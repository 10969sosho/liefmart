<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPembelian extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelians';

    protected $fillable = [
        'kode_retur',
        'penerimaan_id',
        'user_id',
        'tanggal_retur',
        'catatan',
        'status',
        'tipe_retur',
    ];

    protected $casts = [
        'tanggal_retur' => 'date:Y-m-d',
    ];

    // Tipe retur constants
    const TIPE_SEBAGIAN = 'sebagian';
    const TIPE_FULL = 'full';

    /**
     * Get the penerimaan that owns the retur pembelian
     */
    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class);
    }

    /**
     * Get the user that created the retur pembelian
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the details for the retur pembelian
     */
    public function details(): HasMany
    {
        return $this->hasMany(ReturPembelianDetail::class);
    }

    /**
     * Generate kode retur based on current date and id
     */
    public static function generateKodeRetur()
    {
        $today = now()->format('Ymd');
        $lastRetur = self::whereDate('created_at', now())->latest('id')->first();
        
        $sequence = $lastRetur ? (intval(substr($lastRetur->kode_retur, -4)) + 1) : 1;
        
        return 'RP' . $today . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Determine if the retur is full or partial based on returned items vs total items in PO
     */
    public function determineTipeRetur()
    {
        if (!$this->penerimaan_id) {
            return self::TIPE_SEBAGIAN;
        }
        
        $totalPenerimaanItems = PenerimaanDetail::where('penerimaan_detail.penerimaan_id', $this->penerimaan_id)->count();
        $uniqueReturnedItems = $this->details()->distinct('retur_pembelian_details.product_id')->count('retur_pembelian_details.product_id');
        
        return ($uniqueReturnedItems >= $totalPenerimaanItems) ? self::TIPE_FULL : self::TIPE_SEBAGIAN;
    }
} 
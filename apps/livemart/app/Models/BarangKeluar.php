<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'barang_keluar';

    protected $fillable = [
        'kode_barang_keluar',
        'order_item_id',
        'offline_sale_item_id',
        'warehouse_stock_id',
        'finance_offline_id',
        'qty',
        'tanggal_keluar',
        'catatan',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
        'qty' => 'decimal:2',
    ];

    /**
     * Relasi ke order item
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Relasi ke offline sale item
     */
    public function offlineSaleItem(): BelongsTo
    {
        return $this->belongsTo(OfflineSaleItem::class);
    }

    /**
     * Relasi ke warehouse stock
     */
    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class);
    }

    /**
     * Relasi ke produk melalui warehouse stock
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relasi ke lokasi melalui warehouse stock
     */
    public function lokasi()
    {
        return $this->warehouseStock()->first() ? 
            $this->warehouseStock->lokasi : 
            null;
    }

    /**
     * Relasi ke finance offline
     */
    public function financeOffline()
    {
        return $this->belongsTo(FinanceOffline::class, 'finance_offline_id');
    }

    /**
     * Generate kode barang keluar otomatis
     * 
     * Uses hexadecimal format to support an extremely large number of codes
     * Supports up to 16^8 (4,294,967,296) items per day
     */
    public static function generateKode()
    {
        $today = now()->format('Ymd');
        $latestKode = self::where('kode_barang_keluar', 'like', "BK-{$today}%")
            ->orderBy('kode_barang_keluar', 'desc')
            ->value('kode_barang_keluar');

        if ($latestKode) {
            // Extract the hexadecimal part (after the last dash)
            $lastHexPart = substr($latestKode, strrpos($latestKode, '-') + 1);
            // Convert hex to decimal, add 1, then convert back to hex
            $newNumber = hexdec($lastHexPart) + 1;
        } else {
            $newNumber = 1;
        }

        // Convert to hexadecimal and pad to 8 characters
        // This allows for 16^8 = 4,294,967,296 unique codes per day
        $hexCode = strtoupper(dechex($newNumber));
        return "BK-{$today}-".str_pad($hexCode, 8, '0', STR_PAD_LEFT);
    }
}

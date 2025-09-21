<?php

namespace App\Models;

use App\Helpers\MainCategoryHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Penerimaan extends Model
{
    use HasFactory;

    protected $table = 'penerimaan';

    protected $fillable = [
        'kode_penerimaan',
        'main_category_id',
        'tax_category_id',
        'nomor_po',
        'tanggal_penerimaan',
        'metode_pembayaran',
        'tanggal_jatuh_tempo',
        'total_harga',
        'status',
        'catatan',
        'lokasi_id',
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'total_harga' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('mainCategory', function (Builder $builder) {
            $mainCategoryId = MainCategoryHelper::getSelectedMainCategoryId();
            if ($mainCategoryId) {
                $builder->where('main_category_id', $mainCategoryId);
            }
        });
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class);
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PenerimaanDetail::class);
    }

    // Helper method untuk mengambil nama kategori
    public function getKategoriNameAttribute()
    {
        return $this->mainCategory->name ?? 'Tanpa Kategori';
    }

    // Helper method untuk mengambil nama kategori pajak
    public function getTaxCategoryNameAttribute()
    {
        return $this->taxCategory->name ?? 'Tanpa Kategori Pajak';
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    /**
     * Get the activities for this penerimaan
     */
    public function activities()
    {
        return $this->hasMany(PenerimaanActivity::class);
    }

    /**
     * Recalculate total_harga from detail items
     * This ensures consistency between index and print views
     */
    public function recalculateTotalHarga()
    {
        $totalHarga = $this->details()->sum('subtotal');
        $this->update(['total_harga' => $totalHarga]);
        return $totalHarga;
    }

    /**
     * Get calculated total from detail items (for consistency check)
     */
    public function getCalculatedTotalAttribute()
    {
        return $this->details()->sum('subtotal');
    }

    /**
     * Check if stored total matches calculated total
     */
    public function isTotalConsistent()
    {
        return abs($this->total_harga - $this->calculated_total) < 0.01;
    }
}

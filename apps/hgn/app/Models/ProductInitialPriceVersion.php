<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductInitialPriceVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'version',
        'is_active',
        'valid_from',
        'valid_until',
        'parent_version_id',
        'change_reason',
        'created_by',
        'initial_price',
        'discount_percentage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'initial_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getVersionForDate(int $productId, $date): ?self
    {
        $effectiveAt = self::normalizeEffectiveAt($date);

        return self::where('product_id', $productId)
            ->whereRaw('COALESCE(valid_from, created_at) <= ?', [$effectiveAt])
            ->where(function ($q) use ($effectiveAt) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $effectiveAt);
            })
            ->orderByRaw('COALESCE(valid_from, created_at) DESC')
            ->first();
    }

    public static function getInitialPriceForDate(int $productId, $date): float
    {
        $version = self::getVersionForDate($productId, $date);
        if ($version) {
            return (float) $version->initial_price;
        }

        return (float) DB::table('products')->where('id', $productId)->value('initial_price') ?? 0.0;
    }

    public static function createNewVersionForProduct(Product $product, array $newData = [], ?string $reason = null, ?int $userId = null, $effectiveAt = null): self
    {
        $effectiveAt = self::normalizeEffectiveAt($effectiveAt ?? now());

        $currentActive = self::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();

        $latestVersion = (int) self::where('product_id', $product->id)->max('version');
        $nextVersion = $latestVersion > 0 ? $latestVersion + 1 : 1;

        if ($currentActive) {
            self::where('product_id', $product->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'valid_until' => $effectiveAt,
                    'updated_at' => now(),
                ]);
        }

        $payload = array_merge([
            'product_id' => $product->id,
            'version' => $nextVersion,
            'is_active' => true,
            'valid_from' => $effectiveAt,
            'valid_until' => null,
            'parent_version_id' => $currentActive?->id,
            'change_reason' => $reason,
            'created_by' => $userId,
            'initial_price' => $product->initial_price ?? 0,
            'discount_percentage' => $product->discount_percentage ?? 0,
        ], $newData);

        return self::create($payload);
    }

    private static function normalizeEffectiveAt($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return Carbon::parse($value);
    }
}


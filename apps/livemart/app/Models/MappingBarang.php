<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingBarang extends Model
{
    use HasFactory;

    // Menentukan nama tabel yang benar dengan 's' di akhir
    protected $table = 'mapping_barangs';

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'platform_product_id',
        'product_id',
        'quantity',
        'version',
        'is_active',
        'valid_from',
        'valid_until',
        'parent_mapping_id',
        'change_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    // Relasi-relasi tetap sama
    public function platformProduct()
    {
        return $this->belongsTo(PlatformProduct::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parent mapping (for versioning)
     */
    public function parentMapping()
    {
        return $this->belongsTo(MappingBarang::class, 'parent_mapping_id');
    }

    /**
     * Get child mappings (newer versions)
     */
    public function childMappings()
    {
        return $this->hasMany(MappingBarang::class, 'parent_mapping_id');
    }

    /**
     * Check if this mapping has been used in sales
     */
    public function hasBeenUsedInSales()
    {
        // Check if there are any order items using this mapping
        return \DB::table('order_items')
            ->where('platform_product_id', $this->platform_product_id)
            ->exists();
    }

    /**
     * Check if platform+variant combination already has active mapping
     */
    public static function hasActiveMappingForPlatformVariant($platformProductId, $excludeMappingId = null)
    {
        $query = static::where('platform_product_id', $platformProductId)
            ->where('is_active', true);
        
        if ($excludeMappingId) {
            $query->where('id', '!=', $excludeMappingId);
        }
        
        return $query->exists();
    }

    /**
     * Get active mapping for platform+variant combination
     */
    public static function getActiveMappingForPlatformVariant($platformProductId)
    {
        return static::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Get all active mappings for platform+variant combination
     */
    public static function getAllActiveMappingsForPlatformVariant($platformProductId)
    {
        return static::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get the latest active mapping for a platform product
     */
    public static function getLatestActive($platformProductId)
    {
        return static::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Create a new version of this mapping
     */
    public function createNewVersion($newData, $reason = null)
    {
        // Deactivate all active mappings for this platform product
        static::where('platform_product_id', $this->platform_product_id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_until' => now()
            ]);

        // Get the latest version number
        $latestVersion = static::where('platform_product_id', $this->platform_product_id)
            ->max('version');

        $newMapping = $this->replicate();
        $newMapping->version = $latestVersion + 1;
        $newMapping->is_active = true;
        $newMapping->valid_from = now();
        $newMapping->valid_until = null;
        $newMapping->parent_mapping_id = $this->id;
        $newMapping->change_reason = $reason;
        
        // Update with new data
        $newMapping->fill($newData);
        $newMapping->save();

        return $newMapping;
    }

    /**
     * Scope to get only active mappings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get mappings for a specific version
     */
    public function scopeVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Check if a product is already mapped for a platform product
     */
    public static function isProductAlreadyMapped($platformProductId, $productId, $excludeMappingId = null)
    {
        $query = static::where('platform_product_id', $platformProductId)
            ->where('product_id', $productId)
            ->where('is_active', true);
        
        if ($excludeMappingId) {
            $query->where('id', '!=', $excludeMappingId);
        }
        
        return $query->exists();
    }

    /**
     * Get mapping that was active on a specific date
     */
    public static function getMappingForDate($platformProductId, $date)
    {
        return static::where('platform_product_id', $platformProductId)
            ->where(function($query) use ($date) {
                $query->where(function($q) use ($date) {
                    // Mapping was active before the date and either still active or ended after the date
                    $q->where('valid_from', '<=', $date)
                      ->where(function($subQ) use ($date) {
                          $subQ->whereNull('valid_until')
                               ->orWhere('valid_until', '>=', $date);
                      });
                });
            })
            ->orderBy('valid_from', 'desc')
            ->first();
    }

    /**
     * Get all mappings that were active on a specific date for a platform product
     */
    public static function getMappingsForDate($platformProductId, $date)
    {
        return static::where('platform_product_id', $platformProductId)
            ->where(function($query) use ($date) {
                $query->where(function($q) use ($date) {
                    // Mapping was active before the date and either still active or ended after the date
                    $q->where('valid_from', '<=', $date)
                      ->where(function($subQ) use ($date) {
                          $subQ->whereNull('valid_until')
                               ->orWhere('valid_until', '>=', $date);
                      });
                });
            })
            ->orderBy('valid_from', 'desc')
            ->get();
    }

    public static function getEffectiveVersionForOrderCreatedAt($platformProductId, $orderCreatedAt)
    {
        if (is_string($orderCreatedAt)) {
            $orderCreatedAt = \Carbon\Carbon::parse($orderCreatedAt);
        }

        return static::where('platform_product_id', $platformProductId)
            ->where(function($query) use ($orderCreatedAt) {
                $query->where(function($q) use ($orderCreatedAt) {
                    $q->whereNotNull('valid_from')
                      ->where('valid_from', '<=', $orderCreatedAt);
                })
                ->orWhere(function($q) use ($orderCreatedAt) {
                    $q->whereNull('valid_from')
                      ->where('created_at', '<=', $orderCreatedAt);
                });
            })
            ->max('version');
    }

    /**
     * Get mappings based on version that was created before or on the order created date
     * This is the correct logic: find the latest version created before/on order created_at
     * 
     * @param int $platformProductId
     * @param \Carbon\Carbon|string $orderCreatedAt
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMappingsForOrderCreatedAt($platformProductId, $orderCreatedAt)
    {
        $latestVersion = static::getEffectiveVersionForOrderCreatedAt($platformProductId, $orderCreatedAt);
        
        // 2. If version found, get all mappings with that version
        if ($latestVersion !== null) {
            return static::where('platform_product_id', $platformProductId)
                ->where('version', $latestVersion)
                ->orderBy('id')
                ->get();
        }
        
        // 3. If no version found, fallback to active mappings
        return static::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Get duplicate product IDs in an array
     */
    public static function getDuplicateProductIds($productIds)
    {
        $counts = array_count_values($productIds);
        return array_keys(array_filter($counts, function($count) {
            return $count > 1;
        }));
    }
}

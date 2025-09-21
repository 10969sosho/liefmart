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
}

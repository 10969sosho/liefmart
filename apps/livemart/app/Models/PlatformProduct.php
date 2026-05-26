<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformProduct extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'platform_id',
        'platform_product_name',
        'variant', // Menambahkan kolom variant ke fillable
    ];

    // Relasi ke model Platform
    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    // Relasi ke model MappingBarang
    public function mappingBarang()
    {
        return $this->hasMany(MappingBarang::class);
    }

    // Relasi ke model Product melalui MappingBarang
    public function products()
    {
        return $this->belongsToMany(Product::class, 'mapping_barangs')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get mappings that were active on a specific date
     */
    public function getMappingsForDate($date)
    {
        return MappingBarang::getMappingsForDate($this->id, $date);
    }

    /**
     * Get mapping that was active on a specific date (single mapping)
     */
    public function getMappingForDate($date)
    {
        return MappingBarang::getMappingForDate($this->id, $date);
    }

    /**
     * Customize the array representation
     * 
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add both camelCase and snake_case forms of the relationships for JS compatibility
        if ($this->relationLoaded('mappingBarang')) {
            $array['mappingBarang'] = $this->mappingBarang->toArray();
            $array['mapping_barang'] = $array['mappingBarang']; // For snake_case JS access
        }
        
        return $array;
    }
}
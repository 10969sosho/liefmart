<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function taxCategories()
    {
        return $this->hasMany(TaxCategory::class);
    }
}

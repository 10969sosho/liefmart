<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'name',
    ];

    // Relasi ke model PlatformProduct
    public function platformProducts()
    {
        return $this->hasMany(PlatformProduct::class);
    }
}

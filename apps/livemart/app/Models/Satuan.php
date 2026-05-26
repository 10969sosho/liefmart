<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    use HasFactory;
    
    protected $table = 'satuans';
    
    protected $fillable = [
        'name',
        'kode',
        'description',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Relasi ke PenerimaanDetail
    public function penerimaanDetails(): HasMany
    {
        return $this->hasMany(PenerimaanDetail::class);
    }
}
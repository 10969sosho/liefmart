<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingBarangHistory extends Model
{
    use HasFactory;

    protected $table = 'mapping_barang_histories';

    protected $fillable = [
        'platform_product_id',
        'product_id',
        'quantity',
        'action',
        'user_id',
        'keterangan',
    ];

    public function platformProduct()
    {
        return $this->belongsTo(PlatformProduct::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_id',
        'user_id',
        'activity_type',
        'description',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Get the penerimaan that this activity belongs to
     */
    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class);
    }

    /**
     * Get the user that performed this activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

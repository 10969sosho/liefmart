<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportTemp extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'import_type',
        'session_id',
        'data',
        'issues',
        'summary'
    ];
    
    protected $casts = [
        'data' => 'array',
        'issues' => 'array',
        'summary' => 'array',
    ];
    
    /**
     * Cleanup old temp records (older than 24 hours)
     */
    public static function cleanup()
    {
        static::where('created_at', '<', now()->subHours(24))->delete();
    }
}

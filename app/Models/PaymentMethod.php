<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'requires_due_date',
        'is_active',
    ];

    protected $casts = [
        'requires_due_date' => 'boolean',
        'is_active' => 'boolean',
    ];
}

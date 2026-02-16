<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_range_id',
        'percentage',
        'fixed_amount',
        'base_type',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
    ];

    public function range()
    {
        return $this->belongsTo(PerformanceRange::class, 'performance_range_id');
    }
}


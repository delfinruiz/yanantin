<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_cycle_id',
        'user_id',
        'final_score',
        'performance_range_id',
        'bonus_amount',
        'details',
        'computed_at',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'details' => 'array',
        'computed_at' => 'datetime',
    ];

    public function cycle()
    {
        return $this->belongsTo(EvaluationCycle::class, 'evaluation_cycle_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function range()
    {
        return $this->belongsTo(PerformanceRange::class, 'performance_range_id');
    }
}


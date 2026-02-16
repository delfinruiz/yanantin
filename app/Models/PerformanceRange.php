<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_cycle_id',
        'name',
        'min_percentage',
        'max_percentage',
    ];

    public function cycle()
    {
        return $this->belongsTo(EvaluationCycle::class, 'evaluation_cycle_id');
    }

    public function bonusRules()
    {
        return $this->hasMany(BonusRule::class);
    }
}


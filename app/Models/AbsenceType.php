<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_vacation',
        'accrual_days_per_year',
        'requires_approval',
        'max_days_allowed',
        'allows_half_day',
    ];

    protected $casts = [
        'is_vacation' => 'boolean',
        'accrual_days_per_year' => 'float',
        'requires_approval' => 'boolean',
        'allows_half_day' => 'boolean',
    ];
}

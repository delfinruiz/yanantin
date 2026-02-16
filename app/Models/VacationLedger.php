<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationLedger extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'days',
        'type', // accrual, usage, adjustment
        'description',
        'reference_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }
}

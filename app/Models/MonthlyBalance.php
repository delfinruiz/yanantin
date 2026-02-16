<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyBalance extends Model
{
    /** @use HasFactory<\Database\Factories\MonthlyBalanceFactory> */
    use HasFactory;

        protected $fillable = [
        'user_id',
        'year',
        'month',
        'total_income',
        'total_expense',
        'balance',
        'calculated_at',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

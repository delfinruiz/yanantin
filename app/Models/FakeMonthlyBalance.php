<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakeMonthlyBalance extends Model
{
    protected $table = null;
    public $timestamps = false;

    protected $fillable = [
        'year',
        'month',
        'total_income',
        'total_expense',
        'balance',
    ];
}

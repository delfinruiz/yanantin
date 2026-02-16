<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    /** @use HasFactory<\Database\Factories\IncomeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'income_type_id',
        'year',
        'month',
        'amount',
        'notes'
    ];

    public function type()
    {
        return $this->belongsTo(IncomeType::class, 'income_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

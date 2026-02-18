<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HappinessSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'requested_by',
        'suggestion',
        'context',
        'model',
    ];

    protected $casts = [
        'date' => 'date',
        'context' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}


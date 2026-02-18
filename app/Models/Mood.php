<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mood extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'mood',
        'score',
        'message',
        'message_model',
        'message_generated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'message_generated_at' => 'datetime',
    ];

    public const CODES = ['sad','med_sad','neutral','med_happy','happy'];

    public static function scoreFor(string $mood): int
    {
        return match ($mood) {
            'happy' => 100,
            'med_happy' => 75,
            'neutral' => 50,
            'med_sad' => 25,
            default => 0, // sad
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


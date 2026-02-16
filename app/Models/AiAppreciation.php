<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAppreciation extends Model
{
    protected $table = 'survey_ai_appreciations';

    protected $fillable = [
        'survey_id',
        'content',
        'provider',
        'model',
        'usage_tokens',
        'reasoning_tokens',
        'error_message',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}

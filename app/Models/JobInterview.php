<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobInterview extends Model
{
    protected $fillable = [
        'job_application_id',
        'survey_id',
        'interviewer_id',
        'event_id',
        'scheduled_at',
        'status',
        'score',
        'ai_score',
        'comments',
        'ai_report',
        'ai_report_generated_at',
        'ai_report_version',
        'ai_report_source_hash',
        'ai_score_generated_at',
        'ai_score_version',
        'ai_score_source_hash',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'score' => 'decimal:2',
        'ai_score' => 'decimal:2',
        'ai_report_generated_at' => 'datetime',
        'ai_score_generated_at' => 'datetime',
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_offer_id',
        'user_id',
        'cv_snapshot',
        'status',
        'submitted_at',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'resume_path',
        'eligibility_status',
        'score',
        'rejection_reason',
        'auto_decision_log',
        'auto_processed_at',
        'ai_analysis',
    ];

    protected $casts = [
        'cv_snapshot' => 'array',
        'submitted_at' => 'datetime',
        'auto_decision_log' => 'array',
        'ai_analysis' => 'array',
        'auto_processed_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JobInterview::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(JobApplicationFile::class);
    }
}

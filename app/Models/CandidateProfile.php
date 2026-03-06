<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'country',
        'city',
        'rut',
        'birth_date',
        'relocation_availability',
        'modality_availability',
        'education',
        'work_experience',
        'languages',
        'technical_skills',
        'soft_skills',
        'references',
        'salary_expectation',
        'currency',
        'immediate_availability',
        'portfolio_url',
        'linkedin_url',
        'veracity_declaration',
        'ai_authorization',
        'automated_evaluation_consent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'relocation_availability' => 'boolean',
        'education' => 'array',
        'work_experience' => 'array',
        'languages' => 'array',
        'technical_skills' => 'array',
        'soft_skills' => 'array',
        'references' => 'array',
        'immediate_availability' => 'boolean',
        'veracity_declaration' => 'boolean',
        'ai_authorization' => 'boolean',
        'automated_evaluation_consent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

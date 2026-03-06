<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'department_id',
        'hierarchical_level',
        'criticality_level',
        'work_modality',
        'vacancies_count',
        'estimated_start_date',
        'cost_center',
        'opening_reason',
        'mission',
        'organizational_impact',
        'key_results',
        'description',
        'benefits',
        'location',
        'city',
        'country',
        'contract_type',
        'salary',
        'published_at',
        'deadline',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'deadline' => 'date',
        'is_active' => 'boolean',
        'salary' => 'decimal:2',
        'estimated_start_date' => 'date',
        'key_results' => 'array',
        'vacancies_count' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobOfferRequirements(): HasMany
    {
        return $this->hasMany(JobOfferRequirement::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(JobOfferChangeRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhereDate('deadline', '>=', now());
            });
    }

    protected static function booted(): void
    {
        static::saving(function (JobOffer $offer) {
            if ($offer->is_active && ! $offer->published_at) {
                $offer->published_at = now();
            }
        });
    }
}

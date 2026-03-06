<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOfferRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_offer_id',
        'category',
        'type',
        'level',
        'weight',
        'evidence',
    ];

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }
}

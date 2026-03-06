<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOfferHistory extends Model
{
    use HasFactory;

    public $timestamps = false; // Solo usamos created_at definido en migración

    protected $fillable = [
        'job_offer_id',
        'changed_by_id',
        'change_request_id',
        'snapshot_data',
        'change_reason',
        'created_at',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(JobOfferChangeRequest::class);
    }
}

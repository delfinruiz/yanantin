<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event as Dispatcher;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Calendar;
use App\Events\EventChanged as EventChangedBroadcast;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'calendar_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'color',
        'created_by',
        'caldav_uid',
        'caldav_etag',
        'caldav_last_sync_at',
        'attachments',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
        'caldav_last_sync_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user');
    }

    protected static function booted(): void
    {
        static::created(function (self $model) {
            Dispatcher::dispatch(new EventChangedBroadcast('created', $model->getKey()));
        });
        static::updated(function (self $model) {
            Dispatcher::dispatch(new EventChangedBroadcast('updated', $model->getKey()));
        });
        static::deleted(function (self $model) {
            Dispatcher::dispatch(new EventChangedBroadcast('deleted', $model->getKey()));
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Survey extends Model
{
    protected $fillable = [
        'title',
        'description',
        'active',
        'is_public',
        'public_enabled',
        'public_token',
        'deadline',
        'creator_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_public' => 'boolean',
        'public_enabled' => 'boolean',
        'deadline' => 'datetime',
    ];

    public function ensurePublicToken(): void
    {
        if ($this->public_enabled && empty($this->public_token)) {
            $this->public_token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $this->save();
        }
    }

    protected $appends = ['assign_all'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'survey_user')->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'survey_department')->withTimestamps();
    }

    public function aiAppreciation(): HasOne
    {
        return $this->hasOne(AiAppreciation::class);
    }

    public function getAssignAllAttribute(): bool
    {
        if (! $this->exists) {
            return false;
        }
        $totalUsers = \App\Models\User::count();
        $assigned = $this->users()->count();
        return $totalUsers > 0 && $assigned >= $totalUsers;
    }

    public function scopeAccessibleToUser($query, int $userId)
    {
        $deptIds = User::find($userId)?->departments()->pluck('departments.id') ?? collect();
        return $query->where(function ($q) use ($userId, $deptIds) {
            $q->whereHas('users', fn ($u) => $u->where('users.id', $userId));
            
            if ($deptIds->count() > 0) {
                $q->orWhereHas('departments', fn ($d) => $d->whereIn('departments.id', $deptIds));
            }
        });
    }

    public function scopePendingForUser($query, int $userId)
    {
        return $query->whereHas('questions', function ($q) use ($userId) {
            $q->where('required', true)
              ->whereDoesntHave('responses', fn ($r) => $r->where('user_id', $userId));
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FileItem extends Model
{
    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'name',
        'filename',
        'mime_type',
        'size',
        'is_folder',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
    ];

    /* =========================
     | Relaciones
     ========================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWith()
    {
        return $this->belongsToMany(
            User::class,
            'file_item_shares'
        )->withPivot([
            'permission',
            'requires_ack',
            'ack_code',
            'ack_code_expires_at',
            'ack_completed_at',
        ])->withTimestamps();
    }

    /* =========================
     | Scopes
     ========================= */

    public function scopeAccessible($query)
    {
        return $query
            ->where('user_id', Auth::id())
            ->orWhereHas('sharedWith', function ($q) {
                $q->where('users.id', Auth::id());
            });
    }

    /* =========================
     | Permisos
     ========================= */

    public function canEdit(): bool
    {
        if ($this->user_id === Auth::id()) {
            return true;
        }

        return $this->sharedWith()
            ->where('users.id', Auth::id())
            ->wherePivot('permission', 'edit')
            ->exists();
    }

        /* =========================
     | Accessor Permiso visible
     ========================= */

    protected $appends = ['permission_type'];

    public function getPermissionTypeAttribute(): string
    {
        // 👑 Propietario → full
        if ($this->user_id === Auth::id()) {
            return 'full';
        }

        // 🔗 Compartido conmigo
        $share = $this->sharedWith
            ->firstWhere('id', Auth::id());

        return $share?->pivot?->permission ?? '—';
    }

        //verifica si esta compartido
    public function isShared(): bool
{
    return $this->sharedWith()->exists();
}

public function sharedCount(): int
    {
        return $this->sharedWith()->count();
    }

    public function shareLinks()
    {
        return $this->hasMany(FileShareLink::class);
    }
}

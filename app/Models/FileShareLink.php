<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShareLink extends Model
{
    protected $fillable = [
        'file_item_id',
        'token',
        'permission',
        'expires_at',
        'password',
        'downloads',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'downloads'  => 'integer',
    ];

    /* =========================
     | Relaciones
     ========================= */

    public function fileItem(): BelongsTo
    {
        return $this->belongsTo(FileItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* =========================
     | Métodos
     ========================= */

    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}

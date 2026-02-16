<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileItemShare extends Model
{
    protected $fillable = [
        'file_item_id',
        'user_id',
        'permission',
    ];

    public function fileItem()
    {
        return $this->belongsTo(FileItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

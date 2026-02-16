<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusInventory extends Model
{
    use HasFactory;

    protected $table = 'status_inventories';

    protected $fillable = [
        'name',
        'color',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'status_id');
    }
}

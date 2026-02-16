<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionsTask extends Model
{
    use HasFactory;

    protected $table = 'permissions_task'; // Necesario si el modelo se llama PermissionsTask

    protected $fillable = [
        'title',
    ];

    //columna updated_at no se actualiza
    public $timestamps = false;



    /**
     * Relación: Un permiso de tarea pertenece a una tarea.
     */
    public function task(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $table = 'status';



    protected $fillable = [
        'title',

    ];

    //columna updated_at no se actualiza
    public $timestamps = false;


    

    /**
     * Relación: Un registro de estado pertenece a una tarea.
     */
    public function task(): HasMany
    {
        return $this->HasMany(Task::class);
    }
}

<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    //
    protected $fillable = [
        'created_by',
        'assigned_to',
        'meeting_id',
        'title',
        'description',
        'observation',
        'rating',
        'status_id',
        'permissions_id',
        'attachment',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];


    /**
     * Relación: La tarea fue creada por un usuario (created_by).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: La tarea está asignada a un usuario (assigned_to). Puede ser NULL.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    //relacion con statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    //relacion con permissions_tasks
    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionsTask::class, 'permissions_id');
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}


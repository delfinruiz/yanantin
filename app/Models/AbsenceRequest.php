<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceRequest extends Model
{
    protected $fillable = [
        'employee_profile_id',
        'absence_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status', // pending, approved_supervisor, approved_hr, rejected
        'supervisor_comment',
        'supervisor_id',
        'supervisor_approved_at',
        'hr_comment',
        'hr_user_id',
        'hr_approved_at',
        'attachments',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'supervisor_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'attachments' => 'array',
        'days_requested' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AbsenceType::class, 'absence_type_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function hrUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_user_id');
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->hasRole(['Super Admin', 'super_admin', 'aprobador_vacaciones'])) {
            return $query;
        }

        // Check if user manages any department
        if ($user->supervisedDepartments()->exists()) {
            // Get departments where the user is a SUPERVISOR
            $supervisedDeptIds = $user->supervisedDepartments()->pluck('departments.id')->toArray();
            
            return $query->where(function ($q) use ($user, $supervisedDeptIds) {
                $q->whereHas('employee.user', function ($subQ) use ($user) {
                    $subQ->where('id', $user->id); // Own requests
                })->orWhereHas('employee.user.departments', function ($subQ) use ($supervisedDeptIds) {
                    $subQ->whereIn('departments.id', $supervisedDeptIds); // Team requests
                });
            });
        }

        // Employee sees only own
        return $query->whereHas('employee', fn($q) => $q->where('user_id', $user->id));
    }
}

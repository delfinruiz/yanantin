<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'rut',
        'address',
        'profession',
        'birth_date',
        'health_insurance',
        'labor_inclusion',
        'phone',
        'emergency_contact_name',
        'emergency_phone',
        'cargo_id',
        'contract_type_id',
        'vacation_type_id',
        'contract_date',
        'contract_end_date',
        'gender',
        'children',
        'trainings',
        'bank_name',
        'account_type',
        'account_number',
        'reports_to',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contract_date' => 'date',
        'contract_end_date' => 'date',
        'disability' => 'boolean',
        'children' => 'array',
        'trainings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boss(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reports_to');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function vacationType(): BelongsTo
    {
        return $this->belongsTo(AbsenceType::class, 'vacation_type_id');
    }

    public function medicalLicenses()
    {
        return $this->hasMany(MedicalLicense::class, 'user_id', 'user_id');
    }

    public function absenceRequests()
    {
        return $this->hasMany(AbsenceRequest::class);
    }

    public function vacationLedgers()
    {
        return $this->hasMany(VacationLedger::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to', 'user_id');
    }

    public function evaluationObjectives()
    {
        return $this->hasMany(StrategicObjective::class, 'owner_user_id', 'user_id');
    }

    public function evaluationResults()
    {
        return $this->hasMany(EvaluationResult::class, 'user_id', 'user_id');
    }
}

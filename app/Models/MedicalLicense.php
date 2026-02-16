<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalLicense extends Model
{
    protected $fillable = [
        'user_id',
        'absence_type',
        'start_date',
        'end_date',
        'duration_days',
        'reason',
        'diagnosis',
        'code',
        'attachments',
        'status',
        'professional_lastname_father',
        'professional_lastname_mother',
        'professional_names',
        'professional_rut',
        'professional_specialty',
        'professional_type',
        'professional_registry_code',
        'professional_email',
        'professional_phone',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

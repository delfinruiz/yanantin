<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'definition_starts_at',
        'definition_ends_at',
        'followup_periods_count',
        'followup_periods',
        'status',
        'is_published',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'definition_starts_at' => 'datetime',
        'definition_ends_at' => 'datetime',
        'followup_periods' => 'array',
        'is_published' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($cycle) {
            $cycle->updateStatus();
        });
    }

    public function updateStatus(): void
    {
        $now = now();
        
        // Si no está publicado, forzar estado borrador
        if (!$this->is_published) {
            $this->status = 'draft';
            return;
        }

        // 1. Cerrado: Si ya pasó la fecha de fin
        if ($this->ends_at && $this->ends_at->endOfDay()->isPast()) {
            $this->status = 'closed';
            return;
        }

        // 2. Definición: Si está en rango de definición
        if ($this->definition_starts_at && $this->definition_ends_at) {
            if ($now->between($this->definition_starts_at->startOfDay(), $this->definition_ends_at->endOfDay())) {
                $this->status = 'definition';
                return;
            }
        }

        // 3. Seguimiento (Check-in): Si está en algún periodo de seguimiento
        if (!empty($this->followup_periods) && is_array($this->followup_periods)) {
            foreach ($this->followup_periods as $period) {
                if (isset($period['start_date'], $period['end_date'])) {
                    $start = \Carbon\Carbon::parse($period['start_date'])->startOfDay();
                    $end = \Carbon\Carbon::parse($period['end_date'])->endOfDay();
                    
                    if ($now->between($start, $end)) {
                        $this->status = 'followup';
                        return;
                    }
                }
            }
        }
        
        // 4. Ejecución (Active): Si ha iniciado pero no está en los anteriores
        if ($this->starts_at && $this->starts_at->startOfDay()->isPast()) {
                $this->status = 'active'; 
                return;
        }

        // 5. Borrador: Por defecto o futuro
        $this->status = 'draft';
    }

    public function strategicObjectives()
    {
        return $this->hasMany(StrategicObjective::class, 'evaluation_cycle_id');
    }

    public function results()
    {
        return $this->hasMany(EvaluationResult::class, 'evaluation_cycle_id');
    }

    public function ranges()
    {
        return $this->hasMany(PerformanceRange::class, 'evaluation_cycle_id');
    }
}


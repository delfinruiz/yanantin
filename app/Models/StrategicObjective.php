<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrategicObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_cycle_id',
        'title',
        'description',
        'type',
        'target_value',
        'current_value',
        'progress_percentage',
        'unit',
        'weight',
        'due_date',
        'parent_id',
        'owner_user_id',
        'status',
        'execution_status',
        'rejection_reason',
        'approved_by',
        'rejected_by',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'weight' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cycle()
    {
        return $this->belongsTo(EvaluationCycle::class, 'evaluation_cycle_id');
    }

    public function parent()
    {
        return $this->belongsTo(StrategicObjective::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(StrategicObjective::class, 'parent_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function checkins()
    {
        return $this->hasMany(ObjectiveCheckin::class, 'strategic_objective_id');
    }

    public function latestCheckin()
    {
        return $this->hasOne(ObjectiveCheckin::class, 'strategic_objective_id')->latestOfMany();
    }

    public function updateProgress(): void
    {
        $checkins = $this->checkins()->get();

        if ($this->type === 'quantitative') {
            // Tomar el último check-in APROBADO
            $latest = $checkins->where('review_status', 'approved')->sortByDesc('period_index')->first();
            $current = (float) ($latest->numeric_value ?? 0.0);
            $target = (float) ($this->target_value ?? 0.0);
            
            $this->current_value = $current;
            
            if ($target > 0) {
                $ratio = ($current / $target) * 100.0;
                $this->progress_percentage = round(min(100.0, max(0.0, $ratio)), 2);
            } else {
                $this->progress_percentage = 0.0;
            }
        } else {
            // Qualitative
            $cycle = $this->cycle; 
            
            $approved = $checkins->where('review_status', 'approved')->count();
            
            $expected = 1;
            if ($cycle) {
                $expected = (int) ($cycle->followup_periods_count ?? 1);
            } else {
                $expected = max(1, $checkins->count()); 
            }
            
            $ratio = ($approved / max(1, $expected)) * 100.0;
            $this->progress_percentage = round(min(100.0, max(0.0, $ratio)), 2);
            $this->current_value = $approved; 
        }

        // Automatic Execution Status Logic
        if ($this->progress_percentage >= 100) {
            $this->execution_status = 'completed';
        } elseif ($this->progress_percentage > 0) {
            $this->execution_status = 'in_progress';
        } else {
            $this->execution_status = 'pending';
        }

        $this->save();
    }
}

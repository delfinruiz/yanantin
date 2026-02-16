<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Mail\CheckinSubmitted;
use App\Mail\CheckinStatusUpdated;

class ObjectiveCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategic_objective_id',
        'period_index',
        'period_date',
        'numeric_value',
        'narrative',
        'activities',
        'evidence_paths',
        'review_status',
        'reviewer_id',
        'review_comment',
    ];

    protected $casts = [
        'period_date' => 'date',
        'numeric_value' => 'decimal:2',
        'evidence_paths' => 'array',
    ];

    public function objective()
    {
        return $this->belongsTo(StrategicObjective::class, 'strategic_objective_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    protected static function booted()
    {
        static::created(function ($checkin) {
            $objective = $checkin->objective;
            
            // 1. Try to get supervisor from Parent Objective owner
            $supervisor = $objective->parent ? $objective->parent->owner : null;

            // 2. If no parent objective, fallback to Employee Profile 'reports_to'
            if (!$supervisor && $objective->owner && $objective->owner->employeeProfile) {
                $supervisor = $objective->owner->employeeProfile->boss;
            }

            if ($objective && $supervisor && $objective->owner_user_id !== $supervisor->id) {
                // Database Notification
                Notification::make()
                    ->title('Nuevo reporte de avance')
                    ->body("El colaborador {$objective->owner->name} ha registrado un avance en el objetivo: {$objective->title}")
                    ->icon('heroicon-o-document-check')
                    ->success()
                    ->actions([
                        Action::make('view')
                            ->label('Revisar')
                            ->url(\App\Filament\Resources\Evaluations\StrategicObjectiveResource::getUrl('edit', ['record' => $objective->id]))
                            ->button(),
                    ])
                    ->sendToDatabase($supervisor);

                // Email Notification
                if ($supervisor->email) {
                    Mail::to($supervisor->email)->send(new CheckinSubmitted($checkin));
                }
            }
        });

        static::updating(function ($checkin) {
            if ($checkin->isDirty('review_status') && $checkin->review_status !== 'pending_review') {
                $checkin->reviewer_id = \Illuminate\Support\Facades\Auth::id();
            }
        });

        static::updated(function ($checkin) {
            // Notificar al empleado si el estado de revisión cambió
            if ($checkin->wasChanged('review_status')) {
                $objective = $checkin->objective;
                $owner = $objective->owner;
                
                // Evitar notificar si quien edita es el mismo dueño (aunque no debería poder aprobarse a sí mismo)
                if (\Illuminate\Support\Facades\Auth::id() !== $owner->id) {
                    $statusLabel = match($checkin->review_status) {
                        'approved' => 'Aprobado',
                        'rejected_with_correction' => 'Rechazado (Requiere corrección)',
                        'incumplido' => 'Marcado como Incumplido',
                        'pending_review' => 'Pendiente de Revisión',
                        default => 'Actualizado',
                    };

                    $color = match($checkin->review_status) {
                        'approved' => 'success',
                        'rejected_with_correction', 'incumplido' => 'danger',
                        default => 'info',
                    };

                    Notification::make()
                        ->title("Revisión de avance: {$statusLabel}")
                        ->body("Tu reporte de avance en el objetivo '{$objective->title}' ha sido actualizado por " . \Illuminate\Support\Facades\Auth::user()->name)
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color($color)
                        ->actions([
                            Action::make('view')
                                ->label('Ver Detalles')
                                ->url(\App\Filament\Resources\Evaluations\StrategicObjectiveResource::getUrl('edit', ['record' => $objective->id]))
                                ->button(),
                        ])
                        ->sendToDatabase($owner);
                    
                    // Email Notification
                    if ($owner->email) {
                        Mail::to($owner->email)->send(new CheckinStatusUpdated($checkin));
                    }
                } elseif ($checkin->review_status === 'pending_review') {
                    // Notificar al supervisor si el empleado reenvía (status pasa a pending_review)
                    $supervisor = $objective->parent ? $objective->parent->owner : null;
                    if (!$supervisor && $owner->employeeProfile) {
                        $supervisor = $owner->employeeProfile->boss;
                    }

                    if ($supervisor) {
                        Notification::make()
                            ->title('Avance corregido')
                            ->body("{$owner->name} ha corregido y reenviado el avance en: {$objective->title}")
                            ->icon('heroicon-o-arrow-path')
                            ->info()
                            ->actions([
                                Action::make('view')
                                    ->label('Revisar')
                                    ->url(\App\Filament\Resources\Evaluations\StrategicObjectiveResource::getUrl('edit', ['record' => $objective->id]))
                                    ->button(),
                            ])
                            ->sendToDatabase($supervisor);

                        if ($supervisor->email) {
                            Mail::to($supervisor->email)->send(new CheckinSubmitted($checkin));
                        }
                    }
                }
            }
        });

        static::saved(function ($checkin) {
            $checkin->objective->updateProgress();
        });

        static::deleted(function ($checkin) {
            $checkin->objective->updateProgress();
        });
    }
}

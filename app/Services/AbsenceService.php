<?php

namespace App\Services;

use App\Models\EmployeeProfile;
use App\Models\Holiday;
use App\Models\VacationLedger;
use App\Models\AbsenceRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;

class AbsenceService
{
    /**
     * Calculate business days between two dates, excluding weekends and holidays.
     */
    public function calculateBusinessDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        $days = 0;
        $period = CarbonPeriod::create($start, $end);
        
        // Load holidays within the range to avoid N+1 queries in loop
        // Also consider recurring holidays
        // For simplicity, we fetch all holidays and filter in PHP or improve query if performance issues arise
        // Since holidays table is small, fetching logic is fine for now
        
        $holidays = Holiday::all()->map(function ($holiday) use ($start, $end) {
            if ($holiday->is_recurring) {
                // Check if the recurring date falls within the period in any year involved
                // For now, simpler approach: create holiday date for the year(s) of the request
                $years = range($start->year, $end->year);
                $dates = [];
                foreach ($years as $year) {
                    $dates[] = Carbon::createFromDate($year, $holiday->date->month, $holiday->date->day)->format('Y-m-d');
                }
                return $dates;
            }
            return [$holiday->date->format('Y-m-d')];
        })->flatten()->toArray();

        foreach ($period as $date) {
            // Exclude Weekends (Sat=6, Sun=0 in Carbon default? No, isWeekend() handles it)
            if ($date->isWeekend()) {
                continue;
            }

            // Exclude Holidays
            if (in_array($date->format('Y-m-d'), $holidays)) {
                continue;
            }

            $days++;
        }

        return $days;
    }

    /**
     * Get current vacation balance for an employee.
     */
    public function getVacationBalance(EmployeeProfile $employee): float
    {
        return $employee->vacationLedgers()->sum('days');
    }

    /**
     * Accrue vacation for an employee based on their contract.
     * This should be called monthly via a scheduled job.
     */
    public function accrueVacation(EmployeeProfile $employee): void
    {
        if (!$employee->contract_date) {
            return;
        }

        // Determine accrual rate based on employee's vacation type or default policy
        $yearlyDays = 15; // Default standard
        if ($employee->vacationType) {
            $yearlyDays = $employee->vacationType->accrual_days_per_year ?? 15;
        }
        
        $daysToAccrue = $yearlyDays / 12;

        VacationLedger::create([
            'employee_profile_id' => $employee->id,
            'days' => $daysToAccrue,
            'type' => 'accrual',
            'description' => 'Acumulación mensual automática',
            'created_at' => now(),
        ]);
    }

    /**
     * Calculate theoretical vacation days accrued since contract start until now.
     * Standard: 1.25 days per month (or based on vacation type).
     */
    public function calculateTheoreticalAccrual(EmployeeProfile $employee, ?Carbon $until = null): float
    {
        if (!$employee->contract_date) {
            return 0.0;
        }

        $start = Carbon::parse($employee->contract_date);
        $end = $until ? Carbon::parse($until) : now();

        if ($start->gt($end)) {
            return 0.0;
        }

        // Calculate full months passed
        $months = $start->diffInMonths($end);
        
        // Determine accrual rate
        $yearlyDays = 15; // Default standard
        if ($employee->vacationType) {
            $yearlyDays = $employee->vacationType->accrual_days_per_year ?? 15;
        }
        
        return $months * ($yearlyDays / 12);
    }

    /**
     * Check if a requested period overlaps with existing approved requests.
     */
    public function hasOverlap(EmployeeProfile $employee, string $startDate, string $endDate, ?int $excludeRequestId = null): bool
    {
        $query = $employee->absenceRequests()
            ->whereIn('status', ['approved_supervisor', 'approved_hr', 'pending']) // Check pending too? User said "aprobadas o pendientes"
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            });

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }

    /**
     * Notify supervisors about absence request changes.
     */
    public function notifySupervisors(AbsenceRequest $record, string $action = 'created'): void
    {
        if (!$record->employee || !$record->employee->user) {
            return;
        }

        $employeeUser = $record->employee->user;
        
        // Get supervisors from the user's departments
        $supervisors = collect();
        $departments = $employeeUser->departments()->with('supervisors')->get();
        
        $orphanDepartments = [];

        foreach ($departments as $department) {
            if ($department->supervisors->isEmpty()) {
                $orphanDepartments[] = $department->name;
            }
            $supervisors = $supervisors->merge($department->supervisors);
        }
        
        // Filter out the employee themselves and ensure uniqueness
        $supervisors = $supervisors->unique('id')->reject(fn($user) => $user->id === $employeeUser->id);

        // Also filter out the current user (if they are a supervisor making the change)
        $currentUserId = Auth::id();
        if ($currentUserId) {
            $supervisors = $supervisors->reject(fn($user) => $user->id === $currentUserId);
        }

        foreach ($supervisors as $sup) {
            $title = match($action) {
                'created' => 'Nueva solicitud de ausencia',
                'updated' => 'Solicitud de ausencia actualizada',
                'cancelled' => 'Solicitud anulada por empleado',
                'approved_hr' => 'Solicitud aprobada finalmente por RRHH',
                'rejected_hr' => 'Solicitud rechazada por RRHH',
                default => 'Novedad en solicitud de ausencia',
            };
            
            // Get Job Title and Department
            $jobTitle = $record->employee->cargo ? $record->employee->cargo->name : 'Sin cargo';
            $departmentNames = $employeeUser->departments->pluck('name')->implode(', ');
            $departmentText = $departmentNames ? " ($departmentNames)" : '';

            $body = match($action) {
                'created' => "{$employeeUser->name} ({$jobTitle}){$departmentText} ha solicitado {$record->type->name}.",
                'updated' => "{$employeeUser->name} ({$jobTitle}){$departmentText} ha actualizado su solicitud de {$record->type->name}.",
                'cancelled' => "{$employeeUser->name} ({$jobTitle}){$departmentText} ha anulado su solicitud de {$record->type->name}.",
                'approved_hr' => "La solicitud de {$employeeUser->name} ha sido aprobada finalmente por RRHH.",
                'rejected_hr' => "La solicitud de {$employeeUser->name} ha sido rechazada por RRHH.",
                default => "{$employeeUser->name} ({$jobTitle}){$departmentText} tiene una novedad en su solicitud.",
            };

            // DB Notification
            Notification::make()
                ->title($title)
                ->body($body)
                ->status(match($action) {
                    'approved_hr' => 'success',
                    'rejected_hr' => 'danger',
                    default => 'info',
                })
                ->actions([
                    Action::make('review')
                        ->label('Revisar')
                        ->button()
                        ->url(AbsenceRequestResource::getUrl('index')),
                ])
                ->sendToDatabase($sup);

            // Email Notification
            if ($sup->email) {
                try {
                    $subject = $title . ': ' . $employeeUser->name;
                    $mailBody = "Hola {$sup->name},\n\n" .
                            $body . "\n" .
                            "Por favor, ingresa al sistema para revisarla.\n\n" .
                            "Ir a solicitudes: " . AbsenceRequestResource::getUrl('index');
                    
                    Mail::raw($mailBody, function ($message) use ($sup, $subject) {
                        $message->to($sup->email)->subject($subject);
                    });
                } catch (\Exception $e) {
                    Log::error('Error sending absence email to ' . $sup->email . ': ' . $e->getMessage());
                }
            }
        }

        // Check for orphan departments and notify HR
        if (!empty($orphanDepartments) && $action === 'created') {
            $this->notifyHROrphanAlert($record, $orphanDepartments);
        }
    }

    /**
     * Notify HR about departments without supervisors when a request is created.
     */
    public function notifyHROrphanAlert(AbsenceRequest $record, array $orphanDepartments): void
    {
        // Find users with the 'aprobador_vacaciones' role.
        // If no one has this role, fallback to 'recursos humanos' role as a failsafe.
        $approvers = \App\Models\User::role('aprobador_vacaciones')->get();
        
        if ($approvers->isEmpty()) {
            $approvers = \App\Models\User::role('recursos humanos')->get();
        }

        $employeeName = $record->employee?->user?->name ?? 'Empleado';
        $deptList = implode(', ', $orphanDepartments);

        foreach ($approvers as $approver) {
            $title = 'Alerta: Departamentos sin Supervisor';
            $body = "El empleado {$employeeName} ha creado una solicitud, pero los siguientes departamentos no tienen supervisor asignado: {$deptList}.";

            // DB Notification
            Notification::make()
                ->title($title)
                ->body($body)
                ->warning()
                ->actions([
                    Action::make('review')
                        ->label('Ver Solicitud')
                        ->button()
                        ->url(AbsenceRequestResource::getUrl('index')),
                ])
                ->sendToDatabase($approver);

            // Email Notification
            if ($approver->email) {
                try {
                    $subject = $title . ': ' . $employeeName;
                    $mailBody = "Hola {$approver->name},\n\n" .
                            $body . "\n" .
                            "Se ha notificado a los supervisores existentes, pero es necesario revisar la asignación de supervisores para estos departamentos.\n\n" .
                            "Ir al sistema: " . AbsenceRequestResource::getUrl('index');
                    
                    Mail::raw($mailBody, function ($message) use ($approver, $subject) {
                        $message->to($approver->email)->subject($subject);
                    });
                } catch (\Exception $e) {
                    Log::error('Error sending HR orphan alert email to ' . $approver->email . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Notify the employee about the status change of their absence request.
     */
    public function notifyEmployee(AbsenceRequest $record, string $action = 'approved'): void
    {
        if (!$record->employee || !$record->employee->user) {
            return;
        }

        $employeeUser = $record->employee->user;
        
        $title = match($action) {
            'approved_supervisor' => 'Solicitud aprobada por supervisor',
            'approved_hr' => 'Solicitud aprobada finalmente',
            'rejected' => 'Solicitud rechazada',
            'cancelled' => 'Solicitud anulada',
            default => 'Actualización de solicitud',
        };

        $body = match($action) {
            'approved_supervisor' => "Tu solicitud de {$record->type->name} ha sido aprobada por tu supervisor.",
            'approved_hr' => "Tu solicitud de {$record->type->name} ha sido aprobada por RRHH.",
            'rejected' => "Tu solicitud de {$record->type->name} ha sido rechazada.",
            'cancelled' => "Tu solicitud de {$record->type->name} ha sido anulada por tu supervisor.",
            default => "Hay una actualización en tu solicitud de {$record->type->name}.",
        };

        // DB Notification
        Notification::make()
            ->title($title)
            ->body($body)
            ->status(match($action) {
                'approved_supervisor', 'approved_hr' => 'success',
                'rejected' => 'danger',
                'cancelled' => 'warning',
                default => 'info',
            })
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->button()
                    ->url(AbsenceRequestResource::getUrl('index')),
            ])
            ->sendToDatabase($employeeUser);

        // Email Notification
        if ($employeeUser->email) {
            try {
                $subject = $title;
                $mailBody = "Hola {$employeeUser->name},\n\n" .
                        $body . "\n\n" .
                        "Puedes ver los detalles en el sistema.\n\n" .
                        "Ir a mis solicitudes: " . AbsenceRequestResource::getUrl('index');
                
                Mail::raw($mailBody, function ($message) use ($employeeUser, $subject) {
                    $message->to($employeeUser->email)->subject($subject);
                });
            } catch (\Exception $e) {
                Log::error('Error sending status email to ' . $employeeUser->email . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Notify HR/Vacation Approver about approved requests.
     */
    public function notifyHR(AbsenceRequest $record, string $action = 'created'): void
    {
        // Find users with the 'aprobador_vacaciones' role.
        // If no one has this role, fallback to 'recursos humanos' role as a failsafe.
        $approvers = \App\Models\User::role('aprobador_vacaciones')->get();
        
        if ($approvers->isEmpty()) {
            $approvers = \App\Models\User::role('recursos humanos')->get();
        }

        foreach ($approvers as $approver) {
            $title = match($action) {
                'approved_supervisor' => 'Solicitud aprobada por supervisor',
                default => 'Novedad en solicitud de ausencia',
            };

            $employeeName = $record->employee?->user?->name ?? 'Empleado';
            $body = match($action) {
                'approved_supervisor' => "La solicitud de {$employeeName} ha sido aprobada por su supervisor y requiere tu aprobación final.",
                default => "Hay una novedad con la solicitud de {$employeeName}.",
            };

            // DB Notification
            Notification::make()
                ->title($title)
                ->body($body)
                ->success() // Green color for positive action
                ->actions([
                    Action::make('review')
                        ->label('Revisar')
                        ->button()
                        ->url(AbsenceRequestResource::getUrl('index')),
                ])
                ->sendToDatabase($approver);

            // Email Notification
            if ($approver->email) {
                try {
                    $subject = $title . ': ' . $employeeName;
                    $mailBody = "Hola {$approver->name},\n\n" .
                            $body . "\n" .
                            "Por favor, ingresa al sistema para dar la aprobación final.\n\n" .
                            "Ir a solicitudes: " . AbsenceRequestResource::getUrl('index');
                    
                    Mail::raw($mailBody, function ($message) use ($approver, $subject) {
                        $message->to($approver->email)->subject($subject);
                    });
                } catch (\Exception $e) {
                    Log::error('Error sending HR absence email to ' . $approver->email . ': ' . $e->getMessage());
                }
            }
        }
    }
}

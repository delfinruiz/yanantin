<?php

namespace App\Filament\Resources\MyAbsences\Pages;

use App\Filament\Resources\MyAbsences\MyAbsenceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Log;

class CreateMyAbsence extends CreateRecord
{
    protected static string $resource = MyAbsenceResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;
        $employeeId = $data['employee_profile_id'] ?? null;

        if (!$employeeId) {
            return;
        }

        $employee = EmployeeProfile::with('user.departments.supervisors')->find($employeeId);

        if (!$employee || !$employee->user) {
            return;
        }

        $user = $employee->user;

        // 1. Validar si el usuario pertenece a algún departamento
        if ($user->departments->isEmpty()) {
            Notification::make()
                ->title(__('my_absences.notifications.cannot_create.title'))
                ->body(__('my_absences.notifications.no_department.body'))
                ->danger()
                ->send();

            $this->halt();
        }

        // 2. Validar si el departamento tiene supervisor asignado
        $hasSupervisor = $user->departments->contains(function ($department) {
            return $department->supervisors->isNotEmpty();
        });

        if (!$hasSupervisor) {
            Notification::make()
                ->title(__('my_absences.notifications.cannot_create.title'))
                ->body(__('my_absences.notifications.no_supervisor.body'))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        Log::info('AbsenceRequest Created (MyAbsences). ID: ' . $record->id);

        // Notify Department Supervisors
        try {
            $service = new \App\Services\AbsenceService();
            $service->notifySupervisors($record, 'created');
            Log::info('Notifications sent via AbsenceService.');
        } catch (\Exception $e) {
            Log::error('Error sending notifications: ' . $e->getMessage());
        }
    }
}

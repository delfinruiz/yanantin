<?php

namespace App\Filament\Resources\AbsenceRequests\Pages;

use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;
use Filament\Resources\Pages\CreateRecord;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Models\EmployeeProfile;

use Illuminate\Support\Facades\Log;

class CreateAbsenceRequest extends CreateRecord
{
    protected static string $resource = AbsenceRequestResource::class;

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
                ->title('No se puede crear la solicitud')
                ->body('No perteneces a ningún departamento, por lo que no puedes enviar solicitudes.')
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
                ->title('No se puede crear la solicitud')
                ->body('El departamento al que perteneces no tiene un supervisor asignado para autorizar la solicitud.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        Log::info('AbsenceRequest Created. ID: ' . $record->id);

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

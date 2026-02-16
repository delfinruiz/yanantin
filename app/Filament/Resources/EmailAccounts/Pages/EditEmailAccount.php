<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Services\CPanelEmailService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class EditEmailAccount extends EditRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (Model $record) {
                    try {
                        app(CPanelEmailService::class)->delete($record->email);
                    } catch (\Exception $e) {
                         Notification::make()
                            ->title(__('error_delete_cpanel'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        
                        return false;
                    }
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(CPanelEmailService::class);

        try {
            // Cambio de password
            if (!empty($data['password'])) {
                $service->changePassword($record->email, $data['password']);
            }

            // Cambio de quota
            if (isset($data['quota']) && $data['quota'] != $record->quota) {
                $service->changeQuota($record->email, (int) $data['quota']);
            }

            // Actualizar local
            // Password no se guarda en local
            unset($data['password']);
            
            $record->update($data);

            return $record;

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('error_update_cpanel'))
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            $this->halt();

            return $record;
        }
    }
}

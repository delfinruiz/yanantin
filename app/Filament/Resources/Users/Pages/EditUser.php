<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailAccount;
use Filament\Support\Enums\Width;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected ?string $plainPassword = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function fillForm(): void
    {
        // Verificar si el usuario es interno y no tiene perfil
        // Si es así, creamos el perfil vacío para que el Repeater lo muestre
        if ($this->record->is_internal && !$this->record->employeeProfile) {
            $this->record->employeeProfile()->create([]);
            $this->record->refresh();
        }

        parent::fillForm();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password']) && filled($data['password'])) {
            $this->plainPassword = $data['password'];
            // No hashear aquí porque el modelo User tiene cast 'hashed'
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->plainPassword) {
            // Sincronizar con EmailAccount
            $emailAccount = EmailAccount::where('user_id', $this->record->id)->first();
            
            if ($emailAccount) {
                try {
                    // 1. Actualizar hash en DB local
                    $emailAccount->update([
                        'password' => $this->record->password, // Usar el hash guardado en usuario
                        'encrypted_password' => \Illuminate\Support\Facades\Crypt::encryptString($this->plainPassword),
                    ]);

                    // 2. Actualizar cPanel
                    app(\App\Services\CPanelEmailService::class)->changePassword($emailAccount->email, $this->plainPassword);

                     Notification::make()
                        ->title('Contraseña sincronizada con cuenta de correo')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                     Notification::make()
                        ->title('Error al sincronizar contraseña con cPanel')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }
        }
    }
}

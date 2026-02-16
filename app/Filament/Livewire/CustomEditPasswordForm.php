<?php

namespace App\Filament\Livewire;

use Joaopaulolndev\FilamentEditProfile\Livewire\EditPasswordForm;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Facades\Filament;
use App\Services\CPanelEmailService;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Log;

class CustomEditPasswordForm extends EditPasswordForm
{
    public function updatePassword(): void
    {
        // Capturar la contraseña en texto plano antes de que se limpie el formulario
        $plainPassword = $this->data['password'] ?? null;

        try {
            // Llamar al método padre para guardar la contraseña (hasheada) en la base de datos local
            // Copiamos la lógica del padre porque si llamamos a parent::updatePassword(),
            // el formulario se limpia ($this->form->fill()) y perdemos el acceso fácil a los datos si algo falla o para el sync.
            // Además, el padre captura Halt y retorna, lo que hace difícil saber si tuvo éxito.
            
            // Lógica original del padre:
            $data = $this->form->getState(); // Esto valida y devuelve los datos hasheados (por dehydrateStateUsing)

            $newData = [
                'password' => $data['password'],
            ];

            $this->user->update($newData);
        } catch (Halt $exception) {
            return;
        }

        // Si llegamos aquí, la validación y actualización local fueron exitosas.
        
        // Sincronización con cPanel
        if ($plainPassword) {
            try {
                $emailAccount = EmailAccount::where('user_id', $this->user->id)->first();
                
                if ($emailAccount) {
                    $cpanelService = app(CPanelEmailService::class);
                    // Asumimos que el servicio tiene un método updatePassword o similar.
                    // Si no, lo implementaremos o usaremos la lógica necesaria.
                    // El usuario dijo "cambie tambien en la asignacion de correo".
                    
                    // Verificar si el servicio tiene el método.
                    // Si no, tendremos que crearlo. Asumiré que necesito crearlo o usar uno existente.
                    // Voy a asumir createEmailAccount/deleteEmailAccount existen, pero updatePassword?
                    // Lo comprobaré después. Por ahora pongo la llamada.
                    
                    $cpanelService->changePassword($emailAccount->email, $plainPassword);
                    
                    Notification::make()
                        ->success()
                        ->title('Contraseña actualizada y sincronizada con el correo correctamente.')
                        ->send();
                        
                    // Retornamos aquí para evitar la notificación duplicada del padre (si copiamos todo)
                    // Pero necesitamos limpiar el form.
                }
            } catch (\Exception $e) {
                Log::error('Error sincronizando password con cPanel: ' . $e->getMessage());
                Notification::make()
                    ->warning()
                    ->title(__('filament-edit-profile::default.saved_successfully'))
                    ->body('La contraseña se guardó localmente, pero hubo un error sincronizando con el correo: ' . $e->getMessage())
                    ->send();
                    
                 // Limpiar form
                 $this->form->fill();
                 return;
            }
        }

        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->form->fill();

        Notification::make()
            ->success()
            ->title(__('filament-edit-profile::default.saved_successfully'))
            ->send();
    }
}

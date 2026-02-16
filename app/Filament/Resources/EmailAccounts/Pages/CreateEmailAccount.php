<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Services\CPanelEmailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateEmailAccount extends CreateRecord
{
    protected static string $resource = EmailAccountResource::class;

    /**
     * @param array $data
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model
    {
        $service = app(CPanelEmailService::class);
        
        try {
            // El dominio ya viene concatenado desde el formulario (dehydrateStateUsing)
            // o lo aseguramos aquí por si acaso, aunque el form ya lo hace.
            // La lógica en EmailAccountForm asegura que $data['email'] ya tenga el @dominio
            
            // Llamar API cPanel
            $service->create($data['email'], $data['password'], (int) $data['quota']);
            
            // Preparar datos para BD local
            $domain = substr(strrchr($data['email'], '@'), 1);
            $username = substr($data['email'], 0, strrpos($data['email'], '@'));
            
            $modelData = [
                'email' => $data['email'],
                'username' => $username,
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'encrypted_password' => $data['password'],
                'domain' => $domain,
                'quota' => $data['quota'],
                'used' => 0,
            ];

            return static::getModel()::create($modelData);

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('error_create_cpanel'))
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            // Relanzar para detener la creación en Filament si es crítico, 
            // o simplemente detener el proceso.
            $this->halt();

            return new (static::getModel());
        }
    }
}

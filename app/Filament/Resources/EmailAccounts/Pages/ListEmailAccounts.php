<?php

namespace App\Filament\Resources\EmailAccounts\Pages;

use App\Filament\Resources\EmailAccounts\EmailAccountResource;
use App\Models\EmailAccount;
use App\Services\CPanelEmailService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class ListEmailAccounts extends ListRecords
{
    protected static string $resource = EmailAccountResource::class;

    //ancho de la tabla
        public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount(): void
    {
        parent::mount();
        // Sincronizar automáticamente al cargar (silencioso)
        $this->syncEmails(true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label(__('sync'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->syncEmails()),
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    $service = app(CPanelEmailService::class);
                    
                    try {
                        // El dominio ya viene concatenado desde el formulario (dehydrateStateUsing)
                        // o lo aseguramos aquí por si acaso, aunque el form ya lo hace.
                        // La lógica en EmailAccountForm asegura que $data['email'] ya tenga el @dominio
                        
                        // Llamar API cPanel
                        $service->create($data['email'], $data['password'], (int) $data['quota']);
                        
                        // Preparar datos para BD local
                        $domain = substr(strrchr($data['email'], '@'), 1);
                        
                        $modelData = [
                            'email' => $data['email'],
                            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                            'encrypted_password' => $data['password'],
                            'domain' => $domain,
                            'quota' => $data['quota'],
                            'used' => 0,
                        ];

                        return $model::create($modelData);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('error_create_cpanel'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        
                        throw $e;
                    }
                }),
        ];
    }

    public function syncEmails(bool $silent = false)
    {
        try {
            $service = app(CPanelEmailService::class);
            $emails = $service->list();
            
            // Obtener lista de emails de la API para comparar
            $apiEmailsList = [];

            foreach ($emails as $emailData) {
                // Manejar si $emailData es un string (algunas versiones de API pueden devolver solo emails)
                if (is_string($emailData)) {
                    $emailData = ['email' => $emailData];
                }

                $fullEmail = $emailData['email'] ?? null;
                
                if (!$fullEmail) {
                    continue; 
                }

                // Si no contiene @, concatenar domain?
                if (!str_contains($fullEmail, '@') && isset($emailData['domain'])) {
                    $fullEmail .= '@' . $emailData['domain'];
                }

                $apiEmailsList[] = $fullEmail;

                // Quota viene en MB. _diskquota en bytes.
                $usedMb = 0;
                if (isset($emailData['diskused'])) {
                    $usedMb = (float) $emailData['diskused'];
                } elseif (isset($emailData['_diskused'])) {
                    $usedMb = round((float) $emailData['_diskused'] / 1024 / 1024, 2);
                }

                // Obtener quota (puede venir como quota o diskquota)
                $quota = 0;
                if (isset($emailData['diskquota']) && $emailData['diskquota'] !== 'unlimited') {
                    $quota = (int) $emailData['diskquota'];
                } elseif (isset($emailData['quota']) && $emailData['quota'] !== 'unlimited') {
                    $quota = (int) $emailData['quota'];
                }

                EmailAccount::updateOrCreate(
                    ['email' => $fullEmail],
                    [
                        'domain' => $emailData['domain'] ?? substr(strrchr($fullEmail, '@'), 1),
                        'quota' => $quota,
                        'used' => $usedMb,
                    ]
                );
            }

            // Eliminar locales que no estan en la API
            EmailAccount::whereNotIn('email', $apiEmailsList)->delete();

            if (!$silent) {
                Notification::make()
                    ->title(__('sync_completed'))
                    ->success()
                    ->send();
                
                // Recargar tabla?
                // ListRecords livewire component deberia refrescarse si se modifica la BD? 
                // Tal vez no automaticamente la vista.
                $this->resetTable(); 
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('error_sync'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

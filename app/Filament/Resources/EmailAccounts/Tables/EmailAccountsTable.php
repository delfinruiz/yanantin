<?php

namespace App\Filament\Resources\EmailAccounts\Tables;

use App\Models\EmailAccount;
use App\Services\CPanelEmailService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;

class EmailAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('assigned_to'))
                    ->placeholder(__('unassigned'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('encrypted_password')->hidden(), // Forzar selección para el accessor
                TextColumn::make('password')
                    ->label(__('password'))
                    ->formatStateUsing(fn () => '••••••••••••')
                    ->action(
                        Action::make('viewPassword')
                            ->modalHeading(__('view_password'))
                            ->modalCancelActionLabel(__('close'))
                            ->modalSubmitAction(fn ($action) => $action
                                ->label(__('copy'))
                                ->icon('heroicon-o-clipboard-document')
                                ->color('primary')
                                ->extraAttributes(function (?EmailAccount $record) {
                                    $password = $record?->decrypted_password ?? '';
                                    
                                    // Escapamos comillas dobles para que no rompa el atributo HTML data-password
                                    $safePassword = htmlspecialchars($password, ENT_QUOTES);

                                    return [
                                        'data-password' => $safePassword,
                                        'x-on:click.prevent' => "
                                            const val = \$el.getAttribute('data-password');
                                            
                                            const copyToClipboard = async (text) => {
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    try {
                                                        await navigator.clipboard.writeText(text);
                                                        return true;
                                                    } catch (err) {
                                                        console.warn('Clipboard API failed', err);
                                                    }
                                                }
                                                
                                                try {
                                                    const textArea = document.createElement('textarea');
                                                    textArea.value = text;
                                                    textArea.style.position = 'fixed';
                                                    textArea.style.left = '-9999px';
                                                    textArea.style.top = '0';
                                                    textArea.setAttribute('readonly', '');
                                                    document.body.appendChild(textArea);
                                                    textArea.focus();
                                                    textArea.select();
                                                    const success = document.execCommand('copy');
                                                    document.body.removeChild(textArea);
                                                    return success;
                                                } catch (err) {
                                                    console.error('Fallback failed', err);
                                                    return false;
                                                }
                                            };
                                            
                                            copyToClipboard(val).then((success) => {
                                                if (success) {
                                                    new FilamentNotification()
                                                        .title('" . __('password_copied') . "')
                                                        .success()
                                                        .send();
                                                    \$wire.unmountTableAction();
                                                } else {
                                                    new FilamentNotification()
                                                        .title('" . __('error') . "')
                                                        .body('No se pudo copiar automáticamente. Por favor copie manualmente.')
                                                        .danger()
                                                        .send();
                                                }
                                            });
                                        ",
                                    ];
                                })
                            )
                            ->schema(function (EmailAccount $record) {
                                $password = $record->decrypted_password ?? '';

                                return [
                                    TextInput::make('password_view')
                                        ->label(__('password'))
                                        ->default($password)
                                        ->readOnly(),
                                ];
                            })
                    ),
                TextColumn::make('quota')
                    ->label(__('quota'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state == 0 ? __('unlimited') : $state . ' MB'),
                TextColumn::make('used')
                    ->label(__('used'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' MB'),
            ])
            ->poll('60s')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('status'))
                    ->options([
                        'assigned' => __('assigned'),
                        'unassigned' => __('unassigned'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'assigned') {
                            $query->whereNotNull('user_id');
                        } elseif ($data['value'] === 'unassigned') {
                            $query->whereNull('user_id');
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (EmailAccount $record, array $data): EmailAccount {
                        $service = app(CPanelEmailService::class);
                        
                        try {
                            // Cambio de quota
                            if (isset($data['quota']) && (int)$data['quota'] !== (int)$record->quota) {
                                $service->changeQuota($record->email, (int) $data['quota']);
                            }

                            $record->update($data);
                            return $record;

                        } catch (\Exception $e) {
                             Notification::make()
                                ->title(__('error_update_cpanel'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            
                            throw $e;
                        }
                    }),
                Action::make('changePassword')
                    ->label(__('password'))
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->schema([
                        TextInput::make('new_password')
                            ->label(__('new_password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(12)
                            ->confirmed()
                            ->suffixAction(
                                Action::make('generatePassword')
                                    ->icon('heroicon-o-key')
                                    ->action(function (Set $set) {
                                        $password = Str::password(12, true, true, false, false);
                                        $set('new_password', $password);
                                        $set('new_password_confirmation', $password);
                                    })
                            ),
                        TextInput::make('new_password_confirmation')
                            ->label(__('confirm_password'))
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (EmailAccount $record, array $data) {
                        try {
                            app(CPanelEmailService::class)->changePassword($record->email, $data['new_password']);
                            
                            // Guardar password en DB local (Hasheada y Encriptada)
                            $hashedPassword = \Illuminate\Support\Facades\Hash::make($data['new_password']);
                            $record->update([
                                'password' => $hashedPassword,
                                'encrypted_password' => $data['new_password'],
                            ]);

                            // Sincronizar con Usuario si está asignado
                            if ($record->user_id) {
                                \App\Models\User::where('id', $record->user_id)->update([
                                    'password' => $hashedPassword
                                ]);
                                
                                Notification::make()
                                    ->title(__('password_updated_synced'))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title(__('password_updated'))
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('error_change_password'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->before(function (EmailAccount $record) {
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction no es trivial porque necesita llamar a la API para cada uno.
                ]),
            ]);
    }
}

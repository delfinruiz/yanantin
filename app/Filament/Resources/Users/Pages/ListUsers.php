<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailAccount;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return __('users');
    }
    
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('7xl')
                ->using(function (array $data, string $model): Model {
                    $emailAccountId = $data['email_account_id'] ?? null;
                    unset($data['email_account_id']);

                    // Set is_internal based on email account presence
                    $data['is_internal'] = !empty($emailAccountId);
                    
                    $passwordFromEmailAccount = null;

                    if ($emailAccountId) {
                        $emailAccount = EmailAccount::find($emailAccountId);
                        if ($emailAccount) {
                            $data['email'] = $emailAccount->email;
                            $passwordFromEmailAccount = $emailAccount->password;
                            
                            // If password is not set (because it was hidden/optional), set a random one
                            if (empty($data['password'])) {
                                $data['password'] = \Illuminate\Support\Str::random(32);
                            }
                        }
                    }

                    return DB::transaction(function () use ($data, $model, $emailAccountId, $passwordFromEmailAccount) {
                        $user = $model::create($data);
                        
                        // Restore password from email account (avoid double hashing if it was already hashed)
                        if ($passwordFromEmailAccount) {
                            DB::table('users')->where('id', $user->id)->update(['password' => $passwordFromEmailAccount]);
                        }

                        if ($emailAccountId) {
                            $emailAccount = EmailAccount::where('id', $emailAccountId)
                                ->lockForUpdate()
                                ->first();

                            if (!$emailAccount) {
                                throw new \Exception("La cuenta de correo seleccionada no existe.");
                            }
                            
                            if ($emailAccount->user_id) {
                                throw new \Exception("La cuenta de correo ya ha sido asignada a otro usuario.");
                            }

                            $emailAccount->update([
                                'user_id' => $user->id,
                                'assigned_at' => now(),
                            ]);
                        }

                        return $user;
                    });
                }),
        ];
    }
    
}

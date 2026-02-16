<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\EmailAccount;
use Filament\Support\Enums\Width;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $emailAccountId = $data['email_account_id'] ?? null;
        unset($data['email_account_id']); // Eliminar para que no intente guardar en users

        $passwordFromEmailAccount = null;

        // Si se seleccionó una cuenta de correo, usar su password (hash) y asegurar email
        if ($emailAccountId) {
            $emailAccount = EmailAccount::find($emailAccountId);
            if ($emailAccount) {
                $data['email'] = $emailAccount->email;
                $passwordFromEmailAccount = $emailAccount->password;
                // Asignamos una contraseña temporal para cumplir con la restricción NOT NULL de la BD
                // y permitir que el modelo se cree. Luego la sobrescribiremos con el hash real.
                $data['password'] = \Illuminate\Support\Str::random(32);
            }
        } 
        // Si no es cuenta de correo, dejamos $data['password'] tal cual (texto plano),
        // el cast 'hashed' del modelo se encargará de hashearlo.

        return DB::transaction(function () use ($data, $emailAccountId, $passwordFromEmailAccount) {
            $user = static::getModel()::create($data);

            // Si venía de una cuenta de correo, insertamos el hash directamente para evitar doble hash
            if ($passwordFromEmailAccount) {
                DB::table('users')->where('id', $user->id)->update(['password' => $passwordFromEmailAccount]);
                // Opcional: actualizar la instancia si se fuera a usar después, 
                // pero cuidado con save() posterior que podría re-hashear.
            }

            if ($emailAccountId) {
                // Bloquear para evitar condiciones de carrera
                $emailAccount = EmailAccount::where('id', $emailAccountId)
                    ->lockForUpdate()
                    ->first();

                if (!$emailAccount) {
                    // Si no existe (raro pues lo buscamos arriba), ignorar o lanzar
                    // throw new \Exception("La cuenta de correo seleccionada no existe.");
                } elseif ($emailAccount->user_id) {
                    throw new \Exception("La cuenta de correo ya ha sido asignada a otro usuario.");
                } else {
                    $emailAccount->update([
                        'user_id' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            return $user;
        });
    }
}

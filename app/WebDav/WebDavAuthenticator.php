<?php

namespace App\WebDav;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebDavAuthenticator
{
    public function authenticate(): ?User
    {
        $login = request()->getUser();
        $password = request()->getPassword();

        if (! $login || ! $password) {
            return null;
        }

        $user = User::where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if (! $user) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        // 🔥 CLAVE: autenticación sin sesión
        Auth::onceUsingId($user->id);

        return $user;
    }
}

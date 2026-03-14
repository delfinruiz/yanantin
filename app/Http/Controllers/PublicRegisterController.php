<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class PublicRegisterController extends Controller
{
    protected array $dontFlash = [];

    public function create()
    {
        if (Auth::check()) {
            return redirect('/mi-panel');
        }

        return view('pages.portada.signup');
    }

    public function store(Request $request)
    {
        if (Auth::check()) {
            return redirect('/mi-panel');
        }

        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'email.unique' => 'Este correo ya está registrado.',
            ]
        );

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_internal' => false,
        ]);

        $verificationStatus = 'verification-link-sent';
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de verificación al registrar usuario.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
            $verificationStatus = 'verification-link-failed';
        }

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', $verificationStatus);
    }
}

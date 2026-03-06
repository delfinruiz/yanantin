<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

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
        
        event(new Registered($user));
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            // silent: mail may be disabled in local env
        }

        return redirect()->route('filament.public.auth.login')
            ->with('status', 'verification-link-sent');
    }
}

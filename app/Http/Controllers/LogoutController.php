<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Filament\Facades\Filament;

class LogoutController
{
    public function __invoke(Request $request)
    {
      
        Filament::auth()->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }
}
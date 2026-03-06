<?php

namespace App\Http\Middleware;

use App\Filament\Pages\PublicCandidateDashboard;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectPublicFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('public')) {
            return redirect()->to(PublicCandidateDashboard::getUrl(panel: 'public'));
        }

        return $next($request);
    }
}


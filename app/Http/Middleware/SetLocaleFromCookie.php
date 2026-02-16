<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class SetLocaleFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    
    public function handle(Request $request, Closure $next): Response
    {
        $pluginCookieLocale = $request->cookie('filament_language_switch_locale');
        
        // Solo aplicar si hay una cookie válida del plugin. Si no, dejar que Filament/Laravel decidan.
        // O si hay sesión, usar sesión.
        $locale = $pluginCookieLocale ?? $request->session()->get('locale');

        if ($locale && in_array($locale, ['en', 'es'])) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);

            // Sincronizar sesión si es diferente
            if ($request->session()->get('locale') !== $locale) {
                $request->session()->put('locale', $locale);
            }
        }

        return $next($request);
    }
}

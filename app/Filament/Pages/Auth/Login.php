<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\PublicCandidateDashboard;
use App\Filament\Pages\PanelLanding;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';
    
    // Override layout to use a blank layout so we can define full HTML
    protected static string $layout = 'filament.pages.auth.layout';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('Iniciar sesión');
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return parent::getAuthenticateFormAction()
            ->label(__('Iniciar sesión'));
    }

    protected function getRedirectUrl(): string
    {
        $user = Auth::user();

        if ($user instanceof \App\Models\User && $user->hasRole('public')) {
            // Siempre enviar usuarios "public" al panel público, sin importar desde qué login entraron
            return PublicCandidateDashboard::getUrl(panel: 'public');
        }

        // Cualquier otro rol: enviar al landing del panel admin
        return PanelLanding::getUrl(panel: 'admin');
    }
}

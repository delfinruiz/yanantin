<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Pages\Page;

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
}

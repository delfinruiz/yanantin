<?php

namespace App\Filament\Plugins;

use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailAccount;
use Filament\Panel;
use Livewire\Livewire;
use App\Filament\Livewire\CustomEditPasswordForm;

class CustomFilamentEditProfilePlugin extends FilamentEditProfilePlugin
{
    public function boot(Panel $panel): void
    {
        parent::boot($panel);

        // Si se debe mostrar el formulario de contraseña, reemplazar el componente vendor con el nuestro
        if ($this->getShouldShowEditPasswordForm()) {
            Livewire::component('edit_password_form', CustomEditPasswordForm::class);
            
            // Actualizar también la lista interna de componentes para que la página use nuestra clase si es necesario
            // (aunque Livewire resuelve por nombre, esto es por consistencia)
            $this->registeredCustomProfileComponents['edit_password_form'] = CustomEditPasswordForm::class;
        }
    }

    public function getRegisteredCustomProfileComponents(): array
    {
        $components = parent::getRegisteredCustomProfileComponents();

        $user = Auth::user();
        
        // Ocultar formulario de contraseña si el usuario tiene cuenta de correo
        if ($user && EmailAccount::where('user_id', $user->id)->exists()) {
            unset($components['edit_password_form']);
        }

        return $components;
    }
}

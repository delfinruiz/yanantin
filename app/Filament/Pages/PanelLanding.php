<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\PublicCandidateDashboard;

class PanelLanding extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.panel-landing';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->redirectRoute('filament.admin.auth.login');
            return;
        }

        if ($user instanceof \App\Models\User && $user->hasRole('public')) {
            $this->redirect(PublicCandidateDashboard::getUrl(panel: 'public'));
            return;
        }

        $this->redirect(Dashboard::getUrl(panel: 'admin'));
    }
}

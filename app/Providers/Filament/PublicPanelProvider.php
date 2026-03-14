<?php

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\SettingService;
use App\Filament\Plugins\CustomFilamentEditProfilePlugin;
use Moataz01\FilamentNotificationSound\FilamentNotificationSoundPlugin;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Blade;

class PublicPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = app(SettingService::class)->getSettings();

        return $panel
            ->id('public')
            ->path('mi-panel')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName($settings?->company_name ?? 'Yanantin')
            ->favicon($settings?->favicon ? Storage::url($settings->favicon) : asset('/asset/images/favicon.ico'))
            ->brandLogo($settings?->logo_light ? Storage::url($settings->logo_light) : asset('/asset/images/logo-light.png'))
            ->darkModeBrandLogo($settings?->logo_dark ? Storage::url($settings->logo_dark) : asset('/asset/images/logo-dark.png'))
            ->brandLogoHeight('100px')
            ->font('poppins')
            ->profile(isSimple: false)
            ->colors([
                'primary' => '#288cfa',
                'secondary' => '#103766',
                'gray' => Color::Slate,
                'success' => '#2E865F',
                'danger' => '#ef4444',
                'warning' => Color::Orange,
                'info' => '#7ebcf9',
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->globalSearch(false)
            ->pages([
                \App\Filament\Pages\PublicCandidateDashboard::class,
            ])
            ->homeUrl(fn () => \App\Filament\Pages\PublicCandidateDashboard::getUrl(panel: 'public'))
            ->userMenuItems([
                'profile' => fn (Action $action) => $action
                    ->label(fn() => \Illuminate\Support\Facades\Auth::user()?->name ?? __('Perfil'))
                    ->url(fn (): string => \Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
                'logout' => fn (Action $action) => $action
                    ->label(__('Logout'))
                    ->url(route('logout')),
            ])
            ->plugins([
                FilamentNotificationSoundPlugin::make(),
                CustomFilamentEditProfilePlugin::make()
                    ->slug('profile-plugin')
                    ->setTitle(fn() => __('Profile'))
                    ->setNavigationLabel(fn() => __('Profile'))
                    ->shouldShowAvatarForm(
                        value: true,
                        directory: 'avatars',
                        rules: 'mimes:jpeg,png,jpg|max:1024'
                    )
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm(true)
                    ->shouldShowEditPasswordForm(true)
                    ->shouldRegisterNavigation(false),
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render("@vite(['resources/js/app.js'])"),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => view('filament.footer')->render(), // insertar footer
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

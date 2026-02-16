<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Plugins\CustomFilamentEditProfilePlugin;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\Dashboard;
use App\Livewire\GraficoResumenAnualWidgetPrincipal;
use App\Livewire\PrincipalMarcadoresWidget;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\SettingService;
use Filament\Navigation\NavigationGroup;
use Moataz01\FilamentNotificationSound\FilamentNotificationSoundPlugin;

class AdminPanelProvider extends PanelProvider
{

    public function panel(Panel $panel): Panel
    {
        $settings = app(SettingService::class)->getSettings();

        return $panel
            //->sidebarFullyCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            //->sidebarWidth('300px')
            ->default()
            ->brandName($settings?->company_name ?? 'Finanzas Personales')
            ->favicon($settings?->favicon ? Storage::url($settings->favicon) : asset('/asset/images/favicon.ico'))
            ->brandLogo($settings?->logo_light ? Storage::url($settings->logo_light) : asset('/asset/images/logo-light.png'))
            ->darkModeBrandLogo($settings?->logo_dark ? Storage::url($settings->logo_dark) : asset('/asset/images/logo-dark.png'))
            ->brandLogoHeight('100px')
            ->font('poppins')
            ->id('admin')
            ->path('admin')
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->profile(isSimple: false)
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->colors([
                'primary' => '#288cfa', // Classic Blue
                'secondary' => '#103766', // Deep Blue
                'gray' => Color::Slate, // Bluish Gray for neutral tones
                'success' => '#2E865F', // Performance Green
                'danger' => '#ef4444', // Red (Standard Tailwind Red-500 is often better than pure #FF0000 for UI)
                'warning' => Color::Orange,
                'info' => '#7ebcf9', // Lighter Blue
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('filament-navigation.my_apps'))
                    ->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make()
                    ->label(__('filament-navigation.hr'))
                    ->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make()
                    ->label(__('evaluations.navigation_group'))
                    ->icon('heroicon-o-trophy'),
                NavigationGroup::make()
                    ->label(__('filament-navigation.planning'))
                    ->icon('heroicon-o-calendar'),
                NavigationGroup::make()
                    ->label(__('filament-navigation.tools'))
                    ->icon('heroicon-o-wrench-screwdriver'),
                NavigationGroup::make()
                    ->label(__('filament-navigation.settings'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(
                'panels::head.end',
                fn(): string => Blade::render("@vite(['resources/js/app.js'])"),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => view('filament.footer')->render(), // insertar footer
            )

            ->renderHook('panels::head.end', fn() => Blade::render('@wirechatStyles'))
            ->renderHook('panels::body.end', fn() => Blade::render('@wirechatAssets'))
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'chat.unread-counter\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'surveys.pending-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'event-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'surveys.pending-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'meetings.meetings-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'tasks.tasks-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'webmail.webmail-badge-poll\')'),
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@livewire(\'absences.pending-approvals-badge-poll\')'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PrincipalMarcadoresWidget::class,
                GraficoResumenAnualWidgetPrincipal::class,

            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Mis Correos')
                    ->icon('heroicon-o-envelope')
                    ->group('Mis Aplicaciones')
                    ->badge(function () {
                        $user = Auth::user();
                        if (! $user) return null;
                        $account = EmailAccount::where('user_id', $user->id)->first();
                        if (! $account) return null;
                        try {
                            $count = app(\App\Services\ImapService::class)->unreadCount($account);
                            return $count > 0 ? (string) $count : null;
                        } catch (\Throwable $e) {
                            return null;
                        }
                    }, 'danger')
                    ->url(function () {
                        $settings = app(\App\Services\SettingService::class)->getSettings();
                        $user = Auth::user();
                        if (! $user) return '#';

                        $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                        if (! $emailAccount || empty($emailAccount->encrypted_password)) {
                            return '#';
                        }

                        $password = $emailAccount->decrypted_password;
                        if (! $password) {
                            return '#';
                        }

                        $domain = $emailAccount->domain ?? substr(strrchr($emailAccount->email, '@'), 1);
                        $host = ($settings?->cpanel_host) ?: config('cpanel.host') ?: $domain;

                        return "https://{$host}:2096/login?user={$emailAccount->email}&pass={$password}";
                    })
                    ->openUrlInNewTab()
                    ->visible(function () {
                        $user = Auth::user();
                        if (!$user) return false;

                        $emailAccount = EmailAccount::where('user_id', $user->id)->first();
                        return $emailAccount && !empty($emailAccount->encrypted_password);
                    })
                    ->sort(0),
            ])
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
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label(fn() => Auth::user()?->name ?? __('Perfil'))
                    ->url(fn(): string => \Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle')
                    ->visible(fn(): bool => Auth::check()),
                'logout' => fn(Action $action) => $action
                    ->label(__('Logout'))
                    ->url(route('logout')),
            ])
            ->plugins([
                FilamentNotificationSoundPlugin::make(),
                FilamentShieldPlugin::make() //personalizar plugins de FilamentShield
                    ->navigationLabel(fn() => __('label_roles'))
                    ->navigationGroup(fn() => __('conf')),
                CustomFilamentEditProfilePlugin::make()
                    ->slug('profile-plugin')
                    ->setTitle(fn() => __('Profile'))
                    ->setNavigationLabel(fn() => __('Profile'))
                    ->setNavigationGroup(fn() => __('conf'))
                    ->setIcon('heroicon-o-user-circle')
                    ->shouldRegisterNavigation(false)
                    ->shouldShowAvatarForm(
                        value: true,
                        directory: 'avatars',
                        rules: 'mimes:jpeg,png,jpg|max:1024'
                    )
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm(true)
                    ->shouldShowEditPasswordForm(true)
                    ->shouldShowEmailForm(function () {
    $user = Auth::user();
    return $user && EmailAccount::where('user_id', $user->id)->exists() === false;
})
            ])
            // La página Webmail se descubre automáticamente y muestra badge de navegación
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

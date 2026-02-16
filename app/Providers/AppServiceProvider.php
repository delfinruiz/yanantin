<?php

namespace App\Providers;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\WebDav\FileDeleted;
use App\WebDav\SyncFileDeleted;
use Wirechat\Wirechat\Models\Group;
use App\Policies\GroupPolicy;
use App\Models\EmailAccount;
use App\Policies\EmailAccountPolicy;
use App\Services\CPanelEmailService;
use App\Models\Calendar;
use App\Models\Meeting;
use App\Policies\CalendarPolicy;
use App\Policies\MeetingPolicy;
use App\Models\Event as EventModel;
use App\Policies\EventPolicy;
use App\Models\Department;
use App\Policies\DepartmentPolicy;
use App\Models\Survey;
use App\Policies\SurveyPolicy;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;


use App\Models\User;
use App\Observers\UserObserver;
use App\Services\SettingService;
use Illuminate\Auth\Events\Login;
use App\Listeners\JoinGlobalChat;
use Wirechat\Wirechat\Models\Message as WirechatMessage;
use App\Events\ChatMessageCreated;
use Wirechat\Wirechat\Models\Group as WirechatGroup;
use Wirechat\Wirechat\Enums\GroupType as WirechatGroupType;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Group::class => GroupPolicy::class,
        EmailAccount::class => EmailAccountPolicy::class,
        Calendar::class => CalendarPolicy::class,
        EventModel::class => EventPolicy::class,
        Department::class => DepartmentPolicy::class,
        Survey::class => SurveyPolicy::class,
        Meeting::class => MeetingPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el servicio cPanel
        $this->app->singleton(CPanelEmailService::class, function ($app) {
            try {
                $settings = app(\App\Services\SettingService::class);
                $host = $settings->get('cpanel_host');
                $username = $settings->get('cpanel_username');
                $token = $settings->get('cpanel_token');
            } catch (\Throwable $e) {
                $host = null;
                $username = null;
                $token = null;
            }

            $host = $host ?: config('cpanel.host') ?: '';
            $username = $username ?: config('cpanel.username') ?: '';
            $token = $token ?: config('cpanel.token') ?: '';

            return new CPanelEmailService($host, $username, $token);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            $settings = app(SettingService::class);
            $timezone = $settings->get('timezone');
            
            if ($timezone) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            $companyName = $settings->get('company_name');
            if ($companyName) {
                config(['app.name' => $companyName]);
            }

            $logoLight = $settings->get('logo_light');
            $logoDark = $settings->get('logo_dark');
            
            View::share('logo_light', $logoLight ? asset('storage/' . $logoLight) : asset('/asset/images/portada/logo-light.svg'));
            View::share('logo_dark', $logoDark ? asset('storage/' . $logoDark) : asset('/asset/images/portada/logo-dark.svg'));
            View::share('company_name', $companyName ?? 'Finanzas Personales');
        } catch (\Exception $e) {
            // Silently fail if DB is not available or settings table doesn't exist yet
        }

        $this->registerPolicies();

        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()
                ->numbers();
        });

        User::observe(UserObserver::class);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['es', 'en'])
                ->flags([
                    'es' => asset('/asset/flags/es.png'),
                    'en' => asset('/asset/flags/en.png'),
                ]);
        });

        Event::listen(
            FileDeleted::class,
            SyncFileDeleted::class
        );

        Event::listen(
            Login::class,
            JoinGlobalChat::class
        );

        // Eliminado: indicador de no leídos en topbar (se muestra como badge en el menú Webmail)

        WirechatMessage::created(function (WirechatMessage $message) {
            try {
                $conversation = $message->conversation;
                if (! $conversation || ! $conversation->isPrivate()) {
                    return;
                }
                event(new ChatMessageCreated($conversation->id, $message->sendable_id));
            } catch (\Throwable $e) {
                // silent
            }
        });

        try {
            $general = WirechatGroup::where('name', 'General')->first();
            if ($general && $general->type !== WirechatGroupType::PUBLIC) {
                $general->forceFill(['type' => WirechatGroupType::PUBLIC->value])->save();
            }
        } catch (\Throwable $e) {
            // silent
        }
    }
}

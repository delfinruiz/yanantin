<?php

namespace App\Filament\Pages;

use App\Services\SettingService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use DateTimeZone;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $slug = 'settings';

    public static function getNavigationLabel(): string
    {
        return __('settings.navigation_label');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-navigation.settings');
    }

    public static function getNavigationSort(): ?int
    {
        return 999;
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('settings.title');
    }

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingService::class)->getSettings();
        if ($settings) {
            $this->form->fill($settings->toArray());
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('settings.general'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('settings.company_name'))
                            ->required()
                            ->maxLength(255),
                        
                        Select::make('timezone')
                            ->label(__('settings.timezone'))
                            ->options(array_combine(
                                DateTimeZone::listIdentifiers(),
                                DateTimeZone::listIdentifiers()
                            ))
                            ->required()
                            ->searchable(),
                    ])->columns(2),
                
                Section::make(__('settings.appearance'))
                    ->description(__('settings.appearance_desc'))
                    ->schema([
                        FileUpload::make('logo_light')
                            ->label(__('settings.logo_light'))
                            ->disk('public')
                            ->image()
                            ->directory('logos')
                            ->visibility('public')
                            ->preserveFilenames(false)
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml']),
                            
                        FileUpload::make('logo_dark')
                            ->label(__('settings.logo_dark'))
                            ->disk('public')
                            ->image()
                            ->directory('logos')
                            ->visibility('public')
                            ->preserveFilenames(false)
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml']),

                        FileUpload::make('favicon')
                            ->label(__('settings.favicon'))
                            ->disk('public')
                            ->image()
                            ->directory('logos')
                            ->visibility('public')
                            ->preserveFilenames(false)
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/vnd.microsoft.icon', 'image/x-icon']),
                    ])->columns(3),

                Section::make(__('settings.cpanel'))
                    ->schema([
                        TextInput::make('cpanel_host')
                            ->label(__('settings.cpanel_host'))
                            ->maxLength(255),
                        TextInput::make('cpanel_username')
                            ->label(__('settings.cpanel_username'))
                            ->maxLength(255),
                        TextInput::make('cpanel_token')
                            ->label(__('settings.cpanel_token'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make(__('settings.tokens'))
                    ->schema([
                        TextInput::make('token_ai')
                            ->label(__('settings.token_ai'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('token_zadarma')
                            ->label(__('settings.token_zadarma'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('token_sms')
                            ->label(__('settings.token_sms'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('token_email_marketing')
                            ->label(__('settings.token_email_marketing'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        app(SettingService::class)->update($data);

        Notification::make()
            ->title(__('settings.saved_success'))
            ->success()
            ->send();
            
        // Forzar recarga para aplicar cambios visuales si es necesario
        $this->redirect(static::getUrl());
    }
}

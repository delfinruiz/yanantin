<?php

namespace App\Filament\Resources\EmailAccounts\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use App\Services\SettingService;

class EmailAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = app(SettingService::class);
        $domain = $settings->get('cpanel_host'); // Fallback o manejo si es null

        return $schema
            ->components([
                TextInput::make('email')
                    ->label(__('email'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText(__('email_helper'))
                    ->suffix('@' . $domain)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Set $set) use ($domain) {
                        // Si el usuario escribe el dominio, lo quitamos visualmente
                        if ($state && $domain && str_ends_with($state, '@' . $domain)) {
                            $set('email', substr($state, 0, -strlen('@' . $domain)));
                        }
                    })
                    ->formatStateUsing(function ($state) use ($domain) {
                        // Al editar, quitar el dominio para mostrar solo el usuario
                        if ($state && $domain && str_ends_with($state, '@' . $domain)) {
                            return substr($state, 0, -strlen('@' . $domain));
                        }
                        return $state;
                    })
                    ->dehydrateStateUsing(function ($state) use ($domain) {
                        if (!$state || !$domain) return $state;

                        // Si ya termina correctamente, devolver
                        if (str_ends_with($state, '@' . $domain)) {
                            return $state;
                        }

                        // Si tiene @ (ej: usuario@otro.com), usar solo parte local
                        if (str_contains($state, '@')) {
                            $local = strstr($state, '@', true);
                            return $local . '@' . $domain;
                        }

                        // Si es solo usuario
                        return $state . '@' . $domain;
                    }),

                TextInput::make('password')
                    ->label(__('password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(12)
                    ->hiddenOn('edit')
                    ->suffixAction(
                        Action::make('generatePassword')
                            ->icon('heroicon-o-key')
                            ->label(__('generate'))
                            ->action(function (Set $set) {
                                // Alfanumerica, mayusculas, minusculas, 12 digitos
                                $password = Str::password(12, true, true, false, false);
                                $set('password', $password);
                            })
                    ),

                TextInput::make('quota')
                    ->label(__('quota'))
                    ->numeric()
                    ->default(250)
                    ->helperText(__('quota_helper'))
                    ->required(),
            ]);
    }
}


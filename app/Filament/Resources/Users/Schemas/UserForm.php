<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\EmailAccount;
use App\Models\Department;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Account Information'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('name'))
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('email')
                                ->label(__('email address'))
                                ->email()
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->disabled(fn (Get $get) => filled($get('email_account_id')))
                                ->columnSpan(1),
                            Select::make('email_account_id')
                                ->label(__('email_account'))
                                ->options(fn () => EmailAccount::whereNull('user_id')->pluck('email', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $email = EmailAccount::find($state)?->email;
                                        if ($email) {
                                            $set('email', $email);
                                        }
                                    }
                                })
                                ->visible(fn (string $operation): bool => $operation === 'create')
                                ->helperText(__('email_account_helper'))
                                ->columnSpan(1),
                            TextInput::make('password')
                                ->label(__('password'))
                                ->password()
                                ->rule('min:8')
                                ->required(fn (string $operation, Get $get): bool => $operation === 'create' && ! filled($get('email_account_id')))
                                ->dehydrated(fn ($state) => filled($state))
                                ->hidden(fn (Get $get) => filled($get('email_account_id')))
                                ->columnSpan(1),
                            Select::make('roles')
                                ->label(__('roles'))
                                ->options(fn () => Role::pluck('name', 'id')->toArray())
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->saveRelationshipsUsing(function (Select $component, $record, $state) {
                                    $record->roles()->sync($state ?? []);
                                })
                                ->afterStateHydrated(function (Select $component) {
                                    $record = $component->getRecord();
                                    if ($record) {
                                        $component->state($record->roles()->pluck('id')->toArray());
                                    }
                                })
                                ->columnSpan(1),
                            Select::make('departments')
                                ->label(__('departments'))
                                ->options(fn () => Department::pluck('name', 'id')->toArray())
                                ->getOptionLabelsUsing(fn (array $values): array => Department::whereIn('id', $values)->pluck('name', 'id')->toArray())
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->helperText(__('department_helper'))
                                ->columnSpan(1)
                                ->afterStateHydrated(function (Select $component) {
                                    $record = $component->getRecord();
                                    if ($record) {
                                        $component->state($record->departments()->pluck('departments.id')->toArray());
                                    }
                                })
                                ->saveRelationshipsUsing(function (Select $component, $record, $state) {
                                    $record->departments()->sync($state ?? []);
                                }),
                            TextInput::make('assigned_email')
                                ->label(__('assigned_email_account'))
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->formatStateUsing(function ($record) {
                                    return $record->emailAccount?->email ?? __('unassigned');
                                })
                                ->columnSpan(1)
                                ->columnStart(2),
                        ]),
                    ]),
            ]);
    }
}

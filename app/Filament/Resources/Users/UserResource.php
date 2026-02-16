<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserPlus;

    protected static ?string $recordTitleAttribute = 'Users';

    public static function getNavigationGroup(): ?string
    {
        return __('conf');
    }

    //traducir title label
    public static function getNavigationLabel(): string
    {
        return __('users');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
       //     'create' => CreateUser::route('/create'),
       //     'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string //traduccion del modelo
    {
        return __('User');
    }

    // Agrega este método para especificar las columnas de búsqueda
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',    
            'email',
            // ... otras columnas que quieras buscar globalmente
        ];
    }
    
}

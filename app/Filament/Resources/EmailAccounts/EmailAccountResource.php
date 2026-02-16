<?php

namespace App\Filament\Resources\EmailAccounts;

use App\Filament\Resources\EmailAccounts\Pages\CreateEmailAccount;
use App\Filament\Resources\EmailAccounts\Pages\EditEmailAccount;
use App\Filament\Resources\EmailAccounts\Pages\ListEmailAccounts;
use App\Filament\Resources\EmailAccounts\Schemas\EmailAccountForm;
// use App\Filament\Resources\EmailAccounts\Tables\EmailAccountsTable;
use App\Models\EmailAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmailAccountResource extends Resource
{
    protected static ?string $model = EmailAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationGroup(): ?string
    {
        return __('conf');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return __('email_account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('email_accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return EmailAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        /** @var class-string<EmailAccountsTable> $tableClass */
        return \App\Filament\Resources\EmailAccounts\Tables\EmailAccountsTable::configure($table)
        ->poll('30s');
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
            'index' => ListEmailAccounts::route('/'),
           // 'create' => CreateEmailAccount::route('/create'),
           // 'edit' => EditEmailAccount::route('/{record}/edit'),
        ];
    }
}

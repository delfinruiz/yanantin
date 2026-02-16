<?php

namespace App\Filament\Resources\Calendars;

use App\Filament\Resources\Calendars\CalendarResource\Pages;
use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class CalendarResource extends Resource
{
    protected static ?string $model = Calendar::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getNavigationLabel(): string
    {
        return __('calendars_admin.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('calendars_admin.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('calendars_admin.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('conf');
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_public', true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('calendars_admin.field.name'))
                            ->required(),
                        Forms\Components\ColorPicker::make('color')
                            ->label(__('calendars_admin.field.color')),
                        Forms\Components\Hidden::make('is_public')->default(true),
                        Forms\Components\Hidden::make('is_personal')->default(false),
                        Forms\Components\Select::make('manager_user_id')
                            ->label(__('calendars_admin.column.manager'))
                            ->relationship('manager', 'name')
                            ->options(function () {
                                return User::query()->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('calendars_admin.field.description'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('manager.name')->label(__('calendars_admin.column.manager'))->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label(__('calendars_admin.column.updated_at')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->modalWidth('5xl'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalendars::route('/'),
           // 'create' => Pages\CreateCalendar::route('/create'),
           // 'edit' => Pages\EditCalendar::route('/{record}/edit'),
        ];
    }
}

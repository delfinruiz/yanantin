<?php

namespace App\Filament\Resources\MyAbsences;

use App\Filament\Resources\MyAbsences\Pages\CreateMyAbsence;
use App\Filament\Resources\MyAbsences\Pages\ListMyAbsences;
use App\Filament\Resources\MyAbsences\Pages\EditMyAbsence;
use App\Filament\Resources\AbsenceRequests\Schemas\AbsenceRequestForm;
use App\Models\AbsenceRequest;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class MyAbsenceResource extends Resource
{
    protected static ?string $model = AbsenceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $slug = 'mis-ausencias';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('my_absences.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.my_apps');
    }

    public static function getModelLabel(): string
    {
        return __('my_absences.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('my_absences.plural_model_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('employee', function ($query) {
                $query->where('user_id', Auth::id());
            });
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return AbsenceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type.name')
                    ->label(__('my_absences.columns.type'))
                    ->badge()
                    ->color(fn ($record) => $record->type->color ?? 'gray'),
                TextColumn::make('start_date')
                    ->label(__('my_absences.columns.start_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('my_absences.columns.end_date'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('days_requested')
                    ->label(__('my_absences.columns.days'))
                    ->numeric(),
                TextColumn::make('status')
                    ->label(__('my_absences.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_supervisor' => 'info',
                        'approved_hr' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('my_absences.status.pending'),
                        'approved_supervisor' => __('my_absences.status.approved_supervisor'),
                        'approved_hr' => __('my_absences.status.approved_hr'),
                        'rejected' => __('my_absences.status.rejected'),
                        default => $state,
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyAbsences::route('/'),
        //    'create' => CreateMyAbsence::route('/create'),
        //    'edit' => EditMyAbsence::route('/{record}/edit'),
        ];
    }
}

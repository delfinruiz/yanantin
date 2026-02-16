<?php

namespace App\Filament\Resources\Nominas\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VacationLedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'vacationLedgers';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('nominas.vacation_ledger.title');
    }

    protected $listeners = ['refreshVacationLedger' => '$refresh'];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('days')
                    ->label(__('nominas.vacation_ledger.field_days'))
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->options([
                        'accrual' => __('nominas.vacation_ledger.type.accrual'),
                        'usage' => __('nominas.vacation_ledger.type.usage'),
                        'adjustment' => __('nominas.vacation_ledger.type.adjustment'),
                    ])
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Placeholder::make('help_alert')
                    ->label('')
                    ->content(new HtmlString(__('nominas.vacation_ledger.help_alert')))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('nominas.vacation_ledger.column_date'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('nominas.vacation_ledger.column_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accrual' => 'success',
                        'usage' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accrual' => __('nominas.vacation_ledger.type.accrual'),
                        'usage' => __('nominas.vacation_ledger.type.usage'),
                        'adjustment' => __('nominas.vacation_ledger.type.adjustment_short'),
                        default => $state,
                    }),
                TextColumn::make('description')
                    ->label(__('nominas.vacation_ledger.column_description'))
                    ->searchable(),
                TextColumn::make('days')
                    ->label(__('nominas.vacation_ledger.column_days'))
                    ->numeric(2)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('nominas.vacation_ledger.column_type'))
                    ->options([
                        'accrual' => __('nominas.vacation_ledger.type.accrual'),
                        'usage' => __('nominas.vacation_ledger.type.usage'),
                        'adjustment' => __('nominas.vacation_ledger.type.adjustment'),
                    ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label(__('nominas.absence_requests.filter_date_from')),
                        DatePicker::make('created_until')->label(__('nominas.absence_requests.filter_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('nominas.vacation_ledger.action_manual_adjustment'))
                    ->modalHeading(__('nominas.vacation_ledger.modal_adjustment_heading'))
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        return $user?->hasRole(['super_admin', 'Super Admin']) ?? false;
                    }),
            ]);
    }
}

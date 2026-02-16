<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\CycleResource\Pages\CreateCycle;
use App\Filament\Resources\Evaluations\CycleResource\Pages\EditCycle;
use App\Filament\Resources\Evaluations\CycleResource\Pages\ListCycles;
use App\Filament\Resources\Evaluations\CycleResource\Pages\ViewCycle;
use App\Models\EvaluationCycle;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;

use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CycleResource extends Resource
{
    protected static ?string $model = EvaluationCycle::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function getNavigationGroup(): ?string
    {
        return __('evaluations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('evaluations.cycle.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluations.cycle.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('evaluations.cycle.title'))->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label(__('evaluations.cycle.fields.name'))
                    ->required()->maxLength(255),
                \Filament\Forms\Components\DatePicker::make('starts_at')
                    ->label(__('evaluations.cycle.fields.starts_at')),
                \Filament\Forms\Components\DatePicker::make('ends_at')
                    ->label(__('evaluations.cycle.fields.ends_at')),
                \Filament\Forms\Components\DatePicker::make('definition_starts_at')
                    ->label(__('evaluations.cycle.fields.definition_starts_at')),
                \Filament\Forms\Components\DatePicker::make('definition_ends_at')
                    ->label(__('evaluations.cycle.fields.definition_ends_at')),
                \Filament\Forms\Components\TextInput::make('followup_periods_count')
                    ->label(__('evaluations.cycle.fields.followup_periods_count'))
                    ->numeric()->minValue(0),
                \Filament\Forms\Components\Repeater::make('followup_periods')
                    ->label(__('evaluations.cycle.fields.followup_periods'))
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Nombre del Periodo')
                            ->required()
                            ->live(onBlur: true),
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha Inicio')
                            ->required()
                            ->live(onBlur: true),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha Fin')
                            ->required()
                            ->live(onBlur: true),
                    ])
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ? $state['name'] . ' (Del ' . \Carbon\Carbon::parse($state['start_date'])->translatedFormat('d \d\e F') . ' al ' . \Carbon\Carbon::parse($state['end_date'])->translatedFormat('d \d\e F') . ')' : null)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Hidden::make('status')
                    ->default('draft'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('evaluations.cycle.fields.name'))
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('evaluations.cycle.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'definition' => 'Definición',
                        'active' => 'En Ejecución',
                        'followup' => 'Seguimiento',
                        'closed' => 'Cerrado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'definition' => 'info',
                        'active' => 'primary',
                        'followup' => 'warning',
                        'closed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_published')
                    ->label('Publicado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Publicado' : 'No Publicado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('evaluations.cycle.fields.starts_at'))
                    ->date(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('evaluations.cycle.fields.ends_at'))
                    ->date(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (EvaluationCycle $record) => !$record->is_published),
                DeleteAction::make()
                    ->visible(fn (EvaluationCycle $record) => !$record->is_published),
                Action::make('publish')
                    ->label('Publicar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publicar Ciclo de Evaluación')
                    ->modalDescription('¿Estás seguro de que deseas publicar este ciclo? Una vez publicado, será visible para todos los usuarios y comenzará a operar según las fechas definidas.')
                    ->modalSubmitActionLabel('Sí, publicar')
                    ->action(function (EvaluationCycle $record) {
                        // 1. Validar fechas principales
                        if (!$record->starts_at || !$record->ends_at || !$record->definition_starts_at || !$record->definition_ends_at) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede publicar')
                                ->body('Todas las fechas principales (Inicio, Fin, Definición) son obligatorias.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2. Validar cantidad de periodos de seguimiento
                        $expectedCount = (int) $record->followup_periods_count;
                        $actualPeriods = is_array($record->followup_periods) ? $record->followup_periods : [];
                        
                        if (count($actualPeriods) !== $expectedCount) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede publicar')
                                ->body("Se definieron {$expectedCount} periodos de seguimiento, pero se han configurado " . count($actualPeriods) . ".")
                                ->danger()
                                ->send();
                            return;
                        }

                        // 3. Validar integridad de cada periodo
                        foreach ($actualPeriods as $index => $period) {
                            if (empty($period['name']) || empty($period['start_date']) || empty($period['end_date'])) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se puede publicar')
                                    ->body("El periodo de seguimiento #" . ($index + 1) . " tiene campos incompletos.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        $record->update(['is_published' => true]);
                        \Filament\Notifications\Notification::make()
                            ->title('Ciclo publicado exitosamente')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (EvaluationCycle $record) => !$record->is_published),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCycles::route('/'),
            'create' => CreateCycle::route('/create'),
          //  'view' => ViewCycle::route('/{record}'),
            'edit' => EditCycle::route('/{record}/edit'),
        ];
    }
}

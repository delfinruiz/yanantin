<?php

namespace App\Filament\Resources\Evaluations\StrategicObjectiveResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CheckinsRelationManager extends RelationManager
{
    protected static string $relationship = 'checkins';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('evaluations.checkin.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('evaluations.checkin.title'))->schema([
                Forms\Components\Hidden::make('period_index')
                    ->default(fn ($livewire) => $livewire->getOwnerRecord()->checkins()->max('period_index') + 1),
                Forms\Components\DatePicker::make('period_date')
                    ->label(__('evaluations.checkin.fields.period_date'))
                    ->helperText('Fecha de corte del reporte')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('numeric_value')
                    ->label(__('evaluations.checkin.fields.numeric_value'))
                    ->helperText('Valor cuantitativo alcanzado hasta la fecha (si aplica)')
                    ->numeric()
                    ->minValue(0)
                    ->required(fn ($livewire) => $livewire->getOwnerRecord()->type === 'quantitative')
                    ->disabled(fn (?Model $record, $livewire) => 
                        ($record && $livewire->getOwnerRecord()->owner_user_id !== Auth::id()) || 
                        $livewire->getOwnerRecord()->type === 'qualitative' ||
                        ($record && in_array($record->review_status, ['approved', 'incumplido']))
                    ),
                Forms\Components\Textarea::make('narrative')
                    ->label(__('evaluations.checkin.fields.narrative'))
                    ->helperText('Descripción detallada del progreso, logros y obstáculos encontrados')
                    ->columnSpanFull()
                    ->disabled(fn (?Model $record, $livewire) => 
                        ($record && $livewire->getOwnerRecord()->owner_user_id !== Auth::id()) ||
                        ($record && in_array($record->review_status, ['approved', 'incumplido']))
                    ),
                Forms\Components\Textarea::make('activities')
                    ->label(__('evaluations.checkin.fields.activities'))
                    ->helperText('Lista de actividades específicas realizadas durante el periodo')
                    ->columnSpanFull()
                    ->disabled(fn (?Model $record, $livewire) => 
                        ($record && $livewire->getOwnerRecord()->owner_user_id !== Auth::id()) ||
                        ($record && in_array($record->review_status, ['approved', 'incumplido']))
                    ),
                Forms\Components\FileUpload::make('evidence_paths')
                    ->label(__('evaluations.checkin.fields.evidence_paths'))
                    ->helperText('Archivos adjuntos que respalden el avance reportado (PDF, Imágenes, etc.)')
                    ->multiple()->maxFiles(10)->downloadable()->columnSpanFull()
                    ->disabled(fn (?Model $record, $livewire) => 
                        ($record && $livewire->getOwnerRecord()->owner_user_id !== Auth::id()) ||
                        ($record && in_array($record->review_status, ['approved', 'incumplido']))
                    ),
                Forms\Components\Select::make('review_status')
                    ->label(__('evaluations.checkin.fields.review_status'))
                    ->options([
                        'pending_review' => __('evaluations.checkin.enums.pending_review'),
                        'approved' => __('evaluations.checkin.enums.approved'),
                        'rejected_with_correction' => __('evaluations.checkin.enums.rejected_with_correction'),
                        'incumplido' => __('evaluations.checkin.enums.incumplido'),
                    ])
                    ->required()
                    ->default('pending_review')
                    // Disabled if I am the owner (employee) AND I have a boss. 
                    // Enabled if I am NOT the owner (supervisor/admin) OR if I am the owner but have no boss (CEO).
                    ->disabled(fn ($livewire) => 
                        $livewire->getOwnerRecord()->owner_user_id === Auth::id() && 
                        optional(Auth::user()->employeeProfile)->reports_to !== null
                    )
                    ->dehydrated(),
                Forms\Components\TextInput::make('reviewer_name')
                    ->label(__('evaluations.checkin.fields.reviewer_id'))
                    ->formatStateUsing(fn ($record) => $record?->reviewer?->name ?? 'Sin revisión')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('review_comment')
                    ->label(__('evaluations.checkin.fields.review_comment'))
                    ->columnSpanFull()
                    ->disabled(fn ($livewire) => 
                        $livewire->getOwnerRecord()->owner_user_id === Auth::id() && 
                        optional(Auth::user()->employeeProfile)->reports_to !== null
                    )
                    ->dehydrated(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_index')
                    ->label(__('evaluations.checkin.fields.period_index')),
                Tables\Columns\TextColumn::make('period_date')
                    ->label(__('evaluations.checkin.fields.period_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('numeric_value')
                    ->label(__('evaluations.checkin.fields.numeric_value')),
                Tables\Columns\TextColumn::make('review_status')
                    ->label(__('evaluations.checkin.fields.review_status'))
                    ->formatStateUsing(fn ($state) => __('evaluations.checkin.enums.' . $state))
                    ->badge()
                    ->colors([
                        'warning' => 'pending_review',
                        'success' => 'approved',
                        'danger' => ['rejected_with_correction', 'incumplido'],
                    ]),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label(__('evaluations.checkin.fields.reviewer_id')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('evaluations.checkin.actions.create_checkin'))
                    ->visible(fn ($livewire) => $livewire->getOwnerRecord()->owner_user_id === Auth::id()),
            ])
            ->actions([
                EditAction::make()
                    ->label('Ver/Editar')
                    ->mutateFormDataUsing(function (array $data, Model $record): array {
                        if ($record->objective && $record->objective->owner_user_id === Auth::id()) {
                            if ($record->review_status === 'rejected_with_correction') {
                                $data['review_status'] = 'pending_review';
                            }
                        }
                        return $data;
                    }),
                DeleteAction::make()
                     ->visible(fn ($record) => $record->review_status === 'pending_review' && $record->objective->owner_user_id === Auth::id()),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
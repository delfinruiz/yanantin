<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\StrategicObjectiveResource\Pages;
use App\Models\StrategicObjective;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Actions\ViewAction;

use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

use App\Filament\Resources\Evaluations\StrategicObjectiveResource\RelationManagers\CheckinsRelationManager;

class StrategicObjectiveResource extends Resource
{
    protected static ?string $model = StrategicObjective::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getRelations(): array
    {
        return [
            CheckinsRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('evaluations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('evaluations.strategic_objective.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('evaluations.strategic_objective.plural');
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    protected static function isCurrentUserSuperAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(fn ($record) => ($record ? $record->title : __('evaluations.strategic_objective.title')) . ($record && $record->status !== 'draft' ? ' - ' . match($record->status) {
                'pending_approval' => ' (Por Aprobar)',
                'approved' => ' (Aprobado)',
                'rejected' => ' (Rechazado)',
                default => ''
            } : ''))
            ->collapsible()
            ->collapsed(fn ($record) => $record && in_array($record->status, ['approved', 'pending_approval']))
            ->schema([
                \Filament\Schemas\Components\Grid::make(2)
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label(__('evaluations.strategic_objective.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->readOnly(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                            ->helperText(__('evaluations.strategic_objective.helpers.title')),

                        \Filament\Forms\Components\Select::make('evaluation_cycle_id')
                            ->label(__('evaluations.strategic_objective.fields.evaluation_cycle_id'))
                            ->relationship('cycle', 'name', function (Builder $query) {
                                // Solo ciclos publicados
                                $query->where('is_published', true)
                                      ->where(function ($q) {
                                          // Y que estén en etapa de definición (según fechas)
                                          $now = now();
                                          $q->whereDate('definition_starts_at', '<=', $now)
                                            ->whereDate('definition_ends_at', '>=', $now);
                                      });
                            })
                            ->required()
                            ->disabled(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                            ->helperText(__('evaluations.strategic_objective.helpers.evaluation_cycle_id')),
                    ])
                    ->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('rejection_reason')
                    ->label('Motivo de Rechazo')
                    ->columnSpanFull()
                    ->disabled()
                    ->visible(fn ($record) => $record && $record->status === 'rejected'),
                
                \Filament\Forms\Components\Textarea::make('description')
                    ->label(__('evaluations.strategic_objective.fields.description'))
                    ->columnSpanFull()
                    ->readOnly(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                    ->helperText(__('evaluations.strategic_objective.helpers.description')),
                
                \Filament\Schemas\Components\Fieldset::make(__('evaluations.employee_objective.fieldset.metrics'))
                        ->columnSpanFull()
                        ->schema([
                            \Filament\Forms\Components\Select::make('type')
                                ->label(__('evaluations.strategic_objective.fields.type'))
                                ->options([
                                    'quantitative' => __('evaluations.strategic_objective.enums.quantitative'),
                                    'qualitative' => __('evaluations.strategic_objective.enums.qualitative'),
                                ])
                                ->required()
                                ->disabled(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                                ->live(),

                            \Filament\Forms\Components\TextInput::make('weight')
                                ->label(__('evaluations.strategic_objective.fields.weight'))
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->readOnly(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                                ->required(),
                                
                            \Filament\Forms\Components\TextInput::make('target_value')
                                ->label(__('evaluations.strategic_objective.fields.target_value'))
                                ->numeric()
                                ->minValue(0)
                                ->disabled(fn (Get $get) => $get('type') !== 'quantitative')
                                ->readOnly(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                                ->required(fn (Get $get) => $get('type') === 'quantitative'),

                            \Filament\Forms\Components\TextInput::make('current_value')
                                ->label(__('evaluations.employee_objective.fields.current_value'))
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record !== null),

                            \Filament\Forms\Components\TextInput::make('progress_percentage')
                                ->label(__('evaluations.employee_objective.fields.progress_percentage'))
                                ->numeric()
                                ->default(0)
                                ->suffix('%')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record !== null),
                                
                            \Filament\Forms\Components\Select::make('execution_status')
                                ->label('Estado de Ejecución')
                                ->options([
                                    'pending' => 'Pendiente',
                                    'in_progress' => 'En Progreso',
                                    'completed' => 'Completado',
                                    'cancelled' => 'Cancelado',
                                ])
                                ->default('pending')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record !== null),

                            \Filament\Forms\Components\TextInput::make('unit')
                                ->label(__('evaluations.strategic_objective.fields.unit'))
                                ->maxLength(50)
                                ->disabled(fn (Get $get) => $get('type') !== 'quantitative')
                                ->readOnly(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                                ->helperText(__('evaluations.strategic_objective.helpers.unit')),
                        ])
                        ->columns(3),
                
                \Filament\Forms\Components\Select::make('parent_id')
                    ->label(__('evaluations.strategic_objective.fields.parent_id'))
                    ->relationship('parent', 'title', modifyQueryUsing: function ($query, $record) {
                        // Evitar recursividad
                        if ($record) {
                            $query->where('id', '!=', $record->id);
                        }

                        // Filtro jerárquico: Si no es Super Admin, solo ver objetivos APROBADOS del JEFE DIRECTO
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        if ($user && !$user->hasRole('super_admin')) {
                            $bossId = $user->employeeProfile?->reports_to;
                            if ($bossId) {
                                $query->where('owner_user_id', $bossId)
                                      ->where('status', 'approved');
                            } else {
                                // Si no tiene jefe asignado, no debería poder ver objetivos para enlazar (o manejar excepción)
                                $query->where('id', -1); 
                            }
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn () => !self::isCurrentUserSuperAdmin())
                    ->hidden(fn () => self::isCurrentUserSuperAdmin())
                    ->disabled(fn ($record) => $record && ($record->owner_user_id !== Auth::id() || in_array($record->status, ['approved', 'pending_approval'])))
                    ->placeholder('Seleccione un objetivo')
                    ->helperText('Seleccione un objetivo de su jefatura para alinear.'),
                \Filament\Forms\Components\Hidden::make('owner_user_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles del Objetivo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label(__('evaluations.strategic_objective.fields.title')),
                                TextEntry::make('cycle.name')
                                    ->label(__('evaluations.strategic_objective.fields.evaluation_cycle_id')),
                            ]),
                        TextEntry::make('description')
                            ->label(__('evaluations.strategic_objective.fields.description'))
                            ->html()
                            ->columnSpanFull(),
                        
                        Section::make(__('evaluations.employee_objective.fieldset.metrics'))
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label(__('evaluations.strategic_objective.fields.type'))
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'quantitative' => __('evaluations.strategic_objective.enums.quantitative'),
                                                'qualitative' => __('evaluations.strategic_objective.enums.qualitative'),
                                                default => $state,
                                            }),
                                        TextEntry::make('weight')
                                            ->label(__('evaluations.strategic_objective.fields.weight')),
                                        TextEntry::make('unit')
                                            ->label(__('evaluations.strategic_objective.fields.unit'))
                                            ->visible(fn ($record) => $record->type === 'quantitative'),
                                        
                                        TextEntry::make('target_value')
                                            ->label(__('evaluations.strategic_objective.fields.target_value'))
                                            ->visible(fn ($record) => $record->type === 'quantitative'),
                                        TextEntry::make('current_value')
                                            ->label(__('evaluations.employee_objective.fields.current_value')),
                                        TextEntry::make('progress_percentage')
                                            ->label(__('evaluations.employee_objective.fields.progress_percentage'))
                                            ->suffix('%'),
                                    ]),
                            ]),
                            
                        TextEntry::make('parent.title')
                            ->label(__('evaluations.strategic_objective.fields.parent_id'))
                            ->placeholder('Objetivo Raíz'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                \Filament\Tables\Grouping\Group::make('department')
                    ->label('Departamento')
                    ->getKeyFromRecordUsing(fn (StrategicObjective $record) => $record->owner->departments->first()?->id ?? '0')
                    ->getTitleFromRecordUsing(fn (StrategicObjective $record) => $record->owner->departments->first()?->name ?? 'Sin Departamento')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query
                        ->leftJoin('department_user', 'users.id', '=', 'department_user.user_id')
                        ->leftJoin('departments', 'department_user.department_id', '=', 'departments.id')
                        ->orderBy('departments.name', $direction)
                    )
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('cycle_department')
                    ->label('Ciclo y Departamento')
                    ->getKeyFromRecordUsing(fn (StrategicObjective $record) => $record->evaluation_cycle_id . '-' . ($record->owner->departments->first()?->id ?? '0'))
                    ->getTitleFromRecordUsing(fn (StrategicObjective $record) => $record->cycle->name . ' - ' . ($record->owner->departments->first()?->name ?? 'Sin Departamento'))
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('evaluation_cycle_id', $direction))
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('cycle.name')
                    ->label('Ciclo de Evaluación')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('parent.title')
                    ->label('Objetivo Principal')
                    ->getKeyFromRecordUsing(fn (StrategicObjective $record) => $record->parent ? $record->parent->id : $record->id)
                    ->getTitleFromRecordUsing(fn (StrategicObjective $record) => $record->parent ? $record->parent->title : $record->title)
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('parent_id', $direction))
                    ->collapsible(),
            ])
            ->defaultGroup('cycle.name')
            ->groupsOnly(false) // Asegurar que no oculte registros si no hay grupo (opcional)
            ->groupingSettingsHidden(false) // Mostrar configuración de agrupación
            ->collapsedGroupsByDefault(true)
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['owner.departments', 'owner.employeeProfile', 'cycle', 'parent', 'latestCheckin'])
                ->withCount(['checkins as pending_checkins_count' => fn ($query) => $query->where('review_status', 'pending_review')])
                ->withCount('children')
                ->leftJoin('users', 'strategic_objectives.owner_user_id', '=', 'users.id') // Ensure users table is joined for owner access if needed
                ->select('strategic_objectives.*') // Select main table columns to avoid ambiguity
                ->orderByRaw('COALESCE(parent_id, strategic_objectives.id) ASC')
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END ASC')
                ->orderBy('created_at', 'ASC')
            )
            ->recordClasses(function (StrategicObjective $record) {
                if ($record->parent_id) {
                    // Es un hijo/nieto. Encontrar el ID del objetivo raíz.
                    $root = $record;
                    $depth = 0;
                    while ($root->parent && $depth < 10) { // Limite seguridad
                        $root = $root->parent;
                        $depth++;
                    }
                    return 'child-of-' . $root->id;
                }
                return 'root-row-' . $record->id;
            })
            ->recordUrl(null)
            ->recordAction(fn () => 'view_details')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('evaluations.strategic_objective.fields.title'))
                    ->description(fn (StrategicObjective $record) => $record->parent 
                        ? '↳ Alineado a: ' . $record->parent->title 
                        : 'Objetivo Raíz')
                    ->extraAttributes(function (StrategicObjective $record) {
                        // Calcular nivel de profundidad (recursivo o iterativo si es necesario, aquí simple)
                        $depth = 0;
                        $parent = $record->parent;
                        while($parent) {
                            $depth++;
                            $parent = $parent->parent; // Requiere eager loading recursivo o lazy loading
                        }
                        
                        $padding = $depth * 3; // 3rem por nivel
                        
                        return [
                            'style' => $depth > 0 ? "padding-left: {$padding}rem !important;" : '',
                            'class' => $depth > 0 ? 'ml-6 border-l-2 border-gray-300 pl-4' : '',
                        ];
                    })
                    ->icon(fn (StrategicObjective $record) => $record->parent ? 'heroicon-m-arrow-turn-down-right' : 'heroicon-m-globe-alt')
                    ->iconColor(fn (StrategicObjective $record) => $record->parent ? 'gray' : 'primary')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.departments.name')
                    ->label('Departamento')
                    ->listWithLineBreaks()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'pending_approval' => 'Por Aprobar',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pending_checkins_count')
                    ->label('Revisiones Pendientes')
                    ->badge()
                    ->color('danger')
                    ->alignCenter()
                    ->sortable()
                    ->visible(fn ($state) => $state > 0)
                    ->tooltip('Número de reportes de avance pendientes de revisión'),
                Tables\Columns\TextColumn::make('approver_or_rejector')
                    ->label('Aprobado/Rechazado por')
                    ->state(function (StrategicObjective $record) {
                        if ($record->status === 'approved') {
                            return $record->approver?->name;
                        } elseif ($record->status === 'rejected') {
                            return $record->rejector?->name;
                        }
                        return null;
                    })
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evaluation_cycle_id')
                    ->label('Ciclo de Evaluación')
                    ->relationship('cycle', 'name'),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Departamento')
                    ->relationship('owner.departments', 'name'),
            ])
            ->recordActions([
                Action::make('toggle_children')
                    ->label('')
                    ->icon('heroicon-m-chevron-up-down')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(function (StrategicObjective $record) {
                        return [
                            'x-on:click.stop' => "
                                document.querySelectorAll('.child-of-{$record->id}').forEach(el => {
                                    el.classList.toggle('hidden');
                                });
                            ",
                            'title' => 'Expandir/Contraer Desglose',
                            'class' => 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full p-1',
                        ];
                    })
                    ->action(fn() => null)
                    ->visible(fn (StrategicObjective $record) => $record->parent_id === null && $record->children_count > 0),

                ViewAction::make('view_details')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->modalWidth('4xl'),
                EditAction::make()
                    ->label(fn (StrategicObjective $record) => 
                        in_array($record->status, ['approved', 'in_progress', 'completed']) 
                        ? ($record->owner_user_id === Auth::id() ? 'Gestionar Avance' : 'Revisar Avance')
                        : 'Editar'
                    )
                    ->icon(fn (StrategicObjective $record) => in_array($record->status, ['approved', 'in_progress', 'completed']) ? 'heroicon-m-chart-bar' : 'heroicon-m-pencil-square')
                    ->color(fn (StrategicObjective $record) => match ($record->latestCheckin?->review_status) {
                        'pending_review' => 'warning',
                        'rejected_with_correction', 'incumplido' => 'danger',
                        default => 'success',
                    })
                    ->url(fn (StrategicObjective $record) => Pages\EditStrategicObjective::getUrl([$record->id]))
                    ->visible(function (StrategicObjective $record) {
                    // El propietario siempre puede ver el botón (para editar o gestionar avance)
                    if ($record->owner_user_id === Auth::id()) {
                        return true;
                    }
                    // La jefatura directa ve el botón SOLO si hay revisiones pendientes
                    if ($record->owner?->employeeProfile?->reports_to === Auth::id()) {
                        // Usar el atributo cargado en la consulta de la tabla si está disponible
                        if ($record->pending_checkins_count !== null) {
                            return $record->pending_checkins_count > 0;
                        }
                        // Fallback por si se usa en otro contexto
                        return $record->checkins()->where('review_status', 'pending_review')->exists();
                    }
                    return false;
                }),
                
                DeleteAction::make()
                    ->visible(fn (StrategicObjective $record) => ($record->status === 'draft' || $record->status === 'rejected') && $record->owner_user_id === Auth::id()),

                Action::make('submit_approval')
                    ->label(fn () => self::isCurrentUserSuperAdmin() ? 'Publicar y Aprobar' : 'Enviar a Aprobación')
                    ->icon(fn () => self::isCurrentUserSuperAdmin() ? 'heroicon-o-check-badge' : 'heroicon-o-paper-airplane')
                    ->color(fn () => self::isCurrentUserSuperAdmin() ? 'success' : 'primary')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => self::isCurrentUserSuperAdmin() ? 'Publicar Objetivo Estratégico' : 'Enviar a Aprobación')
                    ->modalDescription(fn () => self::isCurrentUserSuperAdmin() 
                        ? 'Al ser Administrador/CEO, este objetivo se aprobará automáticamente y será visible para la organización.' 
                        : '¿Estás seguro de enviar este objetivo a aprobación? No podrás editarlo mientras esté pendiente.')
                    ->action(function (StrategicObjective $record) {
                        if (self::isCurrentUserSuperAdmin()) {
                            $record->update([
                                'status' => 'approved',
                                'approved_by' => Auth::id(),
                                'rejection_reason' => null
                            ]);
                            Notification::make()
                                ->title('Objetivo publicado exitosamente')
                                ->success()
                                ->send();
                        } else {
                            $record->update(['status' => 'pending_approval']);
                            Notification::make()
                                ->title('Enviado a aprobación correctamente')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn (StrategicObjective $record) => ($record->status === 'draft' || $record->status === 'rejected') && $record->owner_user_id === Auth::id()),

                \Filament\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (StrategicObjective $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'rejection_reason' => null
                        ]);
                        
                        Notification::make()
                            ->title('Tu objetivo ha sido aprobado')
                            ->body('El objetivo "' . $record->title . '" ha sido aprobado por tu jefatura.')
                            ->success()
                            ->sendToDatabase($record->owner);
                    })
                    ->visible(function (StrategicObjective $record) {
                        // Visible si está pendiente Y soy el jefe del dueño
                        if ($record->status !== 'pending_approval') return false;
                        $ownerProfile = $record->owner->employeeProfile;
                        return $ownerProfile && $ownerProfile->reports_to === Auth::id();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motivo del rechazo')
                            ->required()
                    ])
                    ->action(function (StrategicObjective $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['reason'],
                            'rejected_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Tu objetivo ha sido rechazado')
                            ->body('Motivo: ' . $data['reason'])
                            ->danger()
                            ->actions([
                                \Filament\Actions\Action::make('view')
                                    ->label('Ver Objetivo')
                                    ->url(StrategicObjectiveResource::getUrl('index')), // Redirige al index donde puede editarlo
                            ])
                            ->sendToDatabase($record->owner);
                    })
                    ->visible(function (StrategicObjective $record) {
                         if ($record->status !== 'pending_approval') return false;
                         $ownerProfile = $record->owner->employeeProfile;
                         return $ownerProfile && $ownerProfile->reports_to === Auth::id();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicObjectives::route('/'),
            'create' => Pages\CreateStrategicObjective::route('/create'),
            'edit' => Pages\EditStrategicObjective::route('/{record}/edit'),
        ];
    }
}

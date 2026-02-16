<?php

namespace App\Filament\Resources\Surveys;

use App\Filament\Resources\Surveys\SurveyResource\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\SurveyResource\Pages\EditSurvey;
use App\Filament\Resources\Surveys\SurveyResource\Pages\ListSurveys;
use App\Models\Survey;
use App\Models\User;
use App\Models\Response;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-navigation.tools');
    }

    public static function getNavigationLabel(): string
    {
        return __('surveys.resource.navigation_label');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function getModelLabel(): string
    {
        return __('surveys.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('surveys.resource.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Forms\Components\Hidden::make('survey_id')
                        ->default(fn (?Survey $record) => $record?->id)
                        ->dehydrated(false),
                    Forms\Components\Select::make('title')
                        ->label(__('surveys.fields.select_survey'))
                        ->required()
                        ->reactive()
                        ->options(function () {
                            $names = \App\Models\Dimension::query()
                                ->whereNotNull('survey_name')
                                ->select('survey_name', DB::raw('MAX(created_at) as last'))
                                ->groupBy('survey_name')
                                ->orderByDesc('last')
                                ->pluck('survey_name', 'survey_name')
                                ->toArray();
                            return $names;
                        })
                        ->placeholder(__('surveys.fields.no_surveys_in_catalog'))
                        ->disabled(fn () => \App\Models\Dimension::whereNotNull('survey_name')->count() === 0)
                        ->columnSpan(2),
                    Forms\Components\Textarea::make('description')
                        ->label(__('surveys.fields.description'))
                        ->required()
                        ->autosize()
                        ->columnSpan(2),
                    Forms\Components\Toggle::make('active')
                        ->label(__('surveys.fields.active')),
                    Forms\Components\Toggle::make('is_public')
                        ->label(__('surveys.fields.is_public'))
                        ->helperText(__('surveys.fields.is_public_helper')),
                    Forms\Components\DateTimePicker::make('deadline')
                        ->label(__('surveys.fields.deadline')),
                ]),
            ])->columnSpanFull(),
            Section::make(__('surveys.fields.questions_builder'))->schema([
                Forms\Components\Repeater::make('questions')
                    ->relationship()
                    ->hiddenLabel()
                    ->defaultItems(0)
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderable()
                    ->extraAttributes([
                        'class' => 'relative',
                        'wire:loading.class' => 'opacity-50 pointer-events-none after:content-["' . __('surveys.fields.processing_order') . '"] after:absolute after:-top-5 after:left-0 after:text-xs after:text-gray-400 dark:after:text-gray-300'
                    ])
                    ->orderColumn('order')
                    ->addActionLabel(__('surveys.fields.add_question'))
                    ->itemLabel(function (array $state) {
                        $dim = isset($state['item']) ? (string) $state['item'] : '';
                        $content = isset($state['content']) ? (string) $state['content'] : '';
                        $label = trim($dim !== '' ? $dim.' — '.$content : $content);
                        if ($label === '') $label = __('surveys.fields.question');
                        return mb_strimwidth($label, 0, 80, '…');
                    })
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('item')
                            ->label(__('surveys.fields.dimension_item'))
                            ->nullable()
                            ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $selected = $get('../../title') ?? $get('title');
                                $catalog = \App\Models\Dimension::query()
                                    ->when($selected, fn ($q) => $q->where('survey_name', $selected))
                                    ->orderBy('item')
                                    ->pluck('item', 'item')
                                    ->toArray();
                                return $catalog;
                            })
                            ->placeholder(__('surveys.fields.select_dimension'))
                            ->reactive()
                            ->native(false)
                            ->searchable()
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get) => empty($get('../../title') ?? $get('title')))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('content')
                            ->label(__('surveys.fields.question'))
                            ->required()
                            ->reactive()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label(__('surveys.fields.type'))
                            ->required()
                            ->reactive()
                            ->options([
                                'text' => __('surveys.types.text'),
                                'bool' => __('surveys.types.bool'),
                                'scale_5' => __('surveys.types.scale_5'),
                                'scale_10' => __('surveys.types.scale_10'),
                                'likert' => __('surveys.types.likert'),
                                'multi' => __('surveys.types.multi'),
                            ]),
                        Forms\Components\Toggle::make('required')
                            ->label(__('surveys.fields.required'))
                            ->default(false),
                        Forms\Components\KeyValue::make('options')
                            ->label(__('surveys.fields.options'))
                            ->columnSpanFull()
                            ->dehydrated(true)
                            ->afterStateHydrated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    if (is_array($decoded)) {
                                        $set('options', $decoded);
                                    }
                                }
                            })
                            ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $type = $get('type');
                                return in_array($type, ['likert', 'multi']);
                            }),
                        Forms\Components\Hidden::make('order')
                            ->default(0),
                    ]),
            ])->columnSpanFull(),
            Section::make(__('surveys.fields.distribution'))->schema([
                Forms\Components\Hidden::make('public_token'),
                Forms\Components\Toggle::make('assign_all')
                    ->label(__('surveys.fields.assign_all'))
                    ->inline(false)
                    ->reactive()
                    ->dehydrated(false)
                    ->default(null),
                Forms\Components\Select::make('departments')
                    ->label(__('surveys.fields.departments'))
                    ->relationship('departments', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('assign_all'))
                    ->helperText(__('surveys.fields.departments_helper')),
                Forms\Components\Toggle::make('public_enabled')
                    ->label(__('surveys.fields.public_distribution'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                        if ($state && !$get('public_token')) {
                            $set('public_token', rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
                        }
                    })
                    ->columnSpan(2)
                    ->helperText(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $enabled = (bool) $get('public_enabled');
                        $token = (string) ($get('../../public_token') ?? $get('public_token'));
                        if (! $enabled) return __('surveys.fields.public_distribution_helper');
                        $base = url('/surveys/public');
                        $link = $token ? $base . '/' . $token : $base;
                        return $link;
                    }),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('surveys.columns.title'))->searchable()->sortable(),
                Tables\Columns\IconColumn::make('active')->label(__('surveys.columns.active'))->boolean()->alignCenter(),
                Tables\Columns\TextColumn::make('deadline')->dateTime()->label(__('surveys.columns.deadline'))->sortable()->alignCenter(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('surveys.columns.questions'))
                    ->counts('questions')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('responded_users')
                    ->label(__('surveys.columns.responded'))
                    ->getStateUsing(function (Survey $record) {
                        $qIds = $record->questions()->pluck('id');
                        $userCount = Response::whereIn('question_id', $qIds)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
                        $guestCount = Response::whereIn('question_id', $qIds)->whereNull('user_id')->distinct('guest_email')->count('guest_email');
                        return $userCount + $guestCount;
                    })
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\ViewColumn::make('ai_indicator')
                    ->label(__('surveys.columns.ai_indicator'))
                    ->view('filament.tables.columns.ai-indicator')
                    ->alignCenter()
                    ->toggleable()
                    ->action(
                        \Filament\Actions\Action::make('view_ai_column_content')
                            ->label(__('surveys.actions.view_ai'))
                            ->icon('heroicon-o-cpu-chip')
                            ->modalContent(fn (Survey $record) => view('filament.pages.ai-appreciation-modal', ['content' => $record->aiAppreciation?->content ?? '']))
                            ->modalHeading(__('surveys.actions.view_ai_modal_heading'))
                            ->modalSubmitAction(false)
                            ->visible(fn (Survey $record) => (bool) $record->aiAppreciation)
                    ),
                Tables\Columns\TextColumn::make('distribution')
                    ->label(__('surveys.columns.distribution'))
                    ->getStateUsing(function (Survey $record) {
                        if ($record->public_enabled) {
                            return __('surveys.columns.public_label');
                        }
                        $depts = $record->departments()->pluck('name')->all();
                        if (!empty($depts)) {
                            return implode(', ', $depts);
                        }
                        $totalUsers = User::count();
                        $assigned = $record->users()->count();
                        if ($totalUsers > 0 && $assigned >= $totalUsers) {
                            return __('surveys.columns.all');
                        }
                        return $assigned > 0 ? __('surveys.columns.specific_users') : __('surveys.columns.unassigned');
                    })
                    ->wrap()
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()->modalWidth('7xl'),
                EditAction::make(),
                DeleteAction::make(),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('ai_appreciation')
                        ->label(__('surveys.actions.view_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->visible(function (Survey $record) {
                            $qIds = $record->questions()->pluck('id');
                            $has = \App\Models\Response::whereIn('question_id', $qIds)->exists();
                            return $has && ! $record->aiAppreciation;
                        })
                        ->action(function (Survey $record) {
                            $provider = app(\App\Services\AiProviderService::class);
                            if (! $provider->hasToken()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Token IA no configurado')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $appreciation = $record->aiAppreciation;
                            $shouldGenerate = ! $appreciation || $record->updated_at->gt($appreciation->updated_at);
                            if (! $shouldGenerate) {
                                \Filament\Notifications\Notification::make()
                                    ->title('La apreciación ya está actualizada')
                                    ->success()
                                    ->send();
                                return;
                            }
                            try {
                                app(\App\Services\SurveyAiAppreciationService::class)->generate($record);
                                \Filament\Notifications\Notification::make()
                                    ->title('Apreciación generada')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error al generar la apreciación')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    \Filament\Actions\Action::make('view_ai')
                        ->label(__('surveys.actions.view_ai'))
                        ->icon('heroicon-o-cpu-chip')
                        ->visible(fn (Survey $record) => (bool) $record->aiAppreciation)
                        ->action(function () {
                            \Filament\Notifications\Notification::make()
                                ->title('Apreciación IA Generada')
                                ->success()
                                ->send();
                        }),
                    \Filament\Actions\Action::make('report_pdf')
                        ->label(__('surveys.actions.report_pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Survey $record) {
                            $qIds = $record->questions()->pluck('id');
                            $has = \App\Models\Response::whereIn('question_id', $qIds)->exists();
                            if (! $has) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('surveys.notifications.no_responses'))
                                    ->warning()
                                    ->send();
                                return;
                            }
                            return redirect()->to(route('surveys.report.pdf', $record));
                        }),
                    \Filament\Actions\Action::make('export_responses')
                        ->label(__('surveys.actions.export_responses'))
                        ->icon('heroicon-o-table-cells')
                        ->action(function (Survey $record) {
                            $qIds = $record->questions()->pluck('id');
                            $has = \App\Models\Response::whereIn('question_id', $qIds)->exists();
                            if (! $has) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('surveys.notifications.no_responses'))
                                    ->warning()
                                    ->send();
                                return;
                            }
                            return redirect()->to(route('surveys.responses.export', $record));
                        }),
                    \Filament\Actions\Action::make('clear_responses')
                        ->label(__('surveys.actions.clear_responses'))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->action(function (Survey $record) {
                            $qIds = $record->questions()->pluck('id');
                            \App\Models\Response::whereIn('question_id', $qIds)->delete();
                        }),
                ])->label(__('surveys.actions.operations')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'edit' => EditSurvey::route('/{record}/edit'),
            'respond' => \App\Filament\Resources\Surveys\SurveyResource\Pages\RespondSurvey::route('/{record}/respond'),
            'report' => \App\Filament\Resources\Surveys\SurveyResource\Pages\SurveyReport::route('/{record}/report'),
        ];
    }
}

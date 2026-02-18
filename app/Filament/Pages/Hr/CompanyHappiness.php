<?php

namespace App\Filament\Pages\Hr;

use App\Filament\Widgets\MoodMonthlyDistributionChart;
use App\Filament\Widgets\MoodTodayPieChart;
use App\Models\HappinessSuggestion;
use App\Models\Mood;
use App\Services\AiMessageService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Widgets\AiSuggestionsPanel;

class CompanyHappiness extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable {
        removeTableFilters as protected baseRemoveTableFilters;
    }

    public int $filtersTick = 0;

    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-face-smile';
    protected static ?string $navigationLabel = 'Felicidad Organizacional';
    protected static ?string $title = 'Felicidad Organizacional';
    protected string $view = 'filament.pages.hr.company-happiness';

    public function mount(): void
    {
        $this->ensureYearSuggestion();
    }

    public function getHeading(): ?string
    {
        return static::$title;
    }

    public function removeTableFilters(): void
    {
        $this->baseRemoveTableFilters();
        $this->filtersTick++;
        $this->dispatch(
            'filters-changed',
            filters: $this->currentPageFilters(),
        )->to(\App\Filament\Widgets\MoodFilteredPieChart::class);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-navigation.hr');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MoodMonthlyDistributionChart::class,
            AiSuggestionsPanel::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getSideWidgets(): array
    {
        return [
            MoodTodayPieChart::class,
            \App\Filament\Widgets\MoodFilteredPieChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function currentPageFilters(): array
    {
        $data = $this->getTableFilterFormState('periodo') ?? [];
        return [
            'year' => $data['year'] ?? now()->year,
            'month' => $data['month'] ?? (string) now()->format('n'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Mood::query()->with(['user.departments']);
                $data = $this->getTableFilterFormState('periodo') ?? [];
                if (!empty($data['year'])) {
                    $query->whereYear('date', (int) $data['year']);
                }
                if (!empty($data['month'])) {
                    $query->whereMonth('date', (int) $data['month']);
                }
                return $query;
            })
            ->columns([
                TextColumn::make('user.name')->label('Nombre')->searchable(),
                TextColumn::make('user.departments.name')
                    ->label('Departamento')
                    ->formatStateUsing(function ($state, $record) {
                        return optional($record->user)
                            ? $record->user->departments->pluck('name')->join(', ')
                            : '';
                    })
                    ->searchable(),
                TextColumn::make('date')->label('Fecha')->date('Y-m-d'),
                TextColumn::make('created_at')->label('Hora')->time('H:i:s'),
                TextColumn::make('mood')
                    ->label('Estado Ánimo')
                    ->formatStateUsing(fn ($state, $record) => $this->labelForState($state, $record)),
            ])
            ->filters([
                Filter::make('periodo')
                    ->label('Periodo')
                    ->schema([
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Año')
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->dispatch('$refresh');
                                $this->dispatch(
                                    'filters-changed',
                                    filters: $this->currentPageFilters(),
                                )->to(\App\Filament\Widgets\MoodFilteredPieChart::class);
                            })
                            ->options(fn () => Mood::query()
                                ->selectRaw('YEAR(date) as y')
                                ->distinct()
                                ->orderByDesc('y')
                                ->pluck('y', 'y')
                                ->mapWithKeys(fn ($v) => [(string) $v => (string) $v])
                                ->toArray())
                            ->default((string) now()->year),
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Mes')
                            ->live()
                            ->default((string) now()->format('n'))
                            ->afterStateUpdated(function () {
                                $this->dispatch('$refresh');
                                $this->dispatch(
                                    'filters-changed',
                                    filters: $this->currentPageFilters(),
                                )->to(\App\Filament\Widgets\MoodFilteredPieChart::class);
                            })
                            ->placeholder('Selecciona una opción')
                            ->options([
                                '1' => 'Enero',
                                '2' => 'Febrero',
                                '3' => 'Marzo',
                                '4' => 'Abril',
                                '5' => 'Mayo',
                                '6' => 'Junio',
                                '7' => 'Julio',
                                '8' => 'Agosto',
                                '9' => 'Septiembre',
                                '10' => 'Octubre',
                                '11' => 'Noviembre',
                                '12' => 'Diciembre',
                            ]),
                    ]),
            ])
            ->filtersTriggerAction(function (Action $action) use ($table) {
                return $action->extraModalFooterActions([
                    $table->getFiltersApplyAction()->close(),
                ]);
            })
            ->deferFilters(false)
            ->toolbarActions([
                ExportAction::make('export_excel')
                    ->label('Exportar Excel')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(function () {
                                $filters = $this->currentPageFilters();
                                $y = (string)($filters['year'] ?? now()->year);
                                $m = (string)($filters['month'] ?? now()->format('n'));
                                $m = str_pad($m, 2, '0', STR_PAD_LEFT);
                                return "estados_animo_{$y}_{$m}";
                            }),
                    ]),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10,25,50]);
    }

    public function currentAggregates(): array
    {
        $today = Carbon::today();
        $counts = Mood::whereDate('date', $today)
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $total = array_sum($counts);
        $weights = [
            'happy' => 1.0, 'med_happy' => 0.75, 'neutral' => 0.5, 'med_sad' => 0.25, 'sad' => 0.0,
        ];
        $score = 0;
        foreach ($weights as $mood => $w) {
            $pct = $total > 0 ? ($counts[$mood] ?? 0) * 100 / $total : 0;
            $score += $pct * $w;
        }
        $level = $total > 0 ? max(1, min(5, (int)ceil(($score / 100) * 5))) : 3;

        return [
            'date' => $today->toDateString(),
            'distribution' => $counts,
            'level' => $level,
            'total' => $total,
        ];
    }

    public function currentYearAggregates(): array
    {
        $year = now()->year;
        $counts = Mood::whereYear('date', $year)
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $total = array_sum($counts);
        $weights = [
            'happy' => 1.0, 'med_happy' => 0.75, 'neutral' => 0.5, 'med_sad' => 0.25, 'sad' => 0.0,
        ];
        $score = 0;
        foreach ($weights as $mood => $w) {
            $pct = $total > 0 ? ($counts[$mood] ?? 0) * 100 / $total : 0;
            $score += $pct * $w;
        }
        $level = $total > 0 ? max(1, min(5, (int)ceil(($score / 100) * 5))) : 3;

        return [
            'year' => $year,
            'distribution' => $counts,
            'level' => $level,
            'total' => $total,
        ];
    }

    protected function ensureYearSuggestion(): void
    {
        $year = now()->year;
        $stats = $this->currentYearAggregates();
        $latest = HappinessSuggestion::whereYear('date', $year)
            ->orderByDesc('date')
            ->first();

        $latestDistribution = $latest?->context['distribution'] ?? null;
        $changed = $latestDistribution === null || $latestDistribution !== $stats['distribution'];

        if ($changed) {
            $ai = app(AiMessageService::class)->generateCompanySuggestions($stats);
            HappinessSuggestion::create([
                'date' => now()->toDateString(),
                'requested_by' => Auth::id(),
                'suggestion' => $ai['text'] ?? '',
                'context' => $stats,
                'model' => $ai['model'] ?? null,
            ]);
        }
    }
    protected function labelForMood(?string $mood): string
    {
        return match ($mood) {
            'happy' => 'Feliz',
            'med_happy' => 'Med Feliz',
            'neutral' => 'Neutral',
            'med_sad' => 'Med Triste',
            'sad' => 'Triste',
            default => '—',
        };
    }

    protected function codeFromScore(?int $score): ?string
    {
        if ($score === null) {
            return null;
        }
        if ($score >= 88) return 'happy';
        if ($score >= 63) return 'med_happy';
        if ($score >= 38) return 'neutral';
        if ($score >= 13) return 'med_sad';
        return 'sad';
    }

    protected function labelForState($state, $record): string
    {
        $mood = $state ?? ($record->mood ?? null) ?? $this->codeFromScore($record->score ?? null);
        return $this->labelForMood($mood);
    }
}


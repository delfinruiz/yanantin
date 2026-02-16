<?php

namespace App\Filament\Pages;

use App\Livewire\GraficoGastosPorCategoria;
use App\Livewire\GraficoResumenAnualWidget;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Income;
use App\Models\Expense;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ResumenDashboard extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use HasPageShield;

    protected string $view = 'filament.pages.resumen-dashboard';
    //desactivar la navegacion en la sidebar
    protected static bool $shouldRegisterNavigation = false;
    //no mostrar titulo
    public function getHeading(): ?string
    {
        return null;
    }
    protected static ?string $title = 'Resumen Dashboard';
    //actualizar la tabla cuando se haga click en el tab resumen
    protected $listeners = ['refresh-resumen' => '$refresh'];



    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label(__('year')),
                TextColumn::make('month')
                    ->label(__('month'))
                    ->formatStateUsing(function (string $state): string {
                        return __(date('F', mktime(0, 0, 0, $state)));
                    }),
                TextColumn::make('total_incomes')
                    ->label(__('income'))
                    ->money('CLP'),
                TextColumn::make('total_expenses')
                    ->label(__('expense'))
                    ->money('CLP'),
                TextColumn::make('balance')
                    ->label(__('balance'))
                    ->money('CLP')
                    ->color(fn($record) => $record['balance'] >= 0 ? 'success' : 'danger'),
            ])

            ->filters([
                SelectFilter::make('year')
                    ->label(__('year'))
                    ->options(function () {
                        return Expense::select('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                    })
                    ->default(date('Y')),
            ])

            ->records(function () {
                $userId = Auth::id();

                // Obtener el año desde el filtro del usuario
                $filtros = $this->getTableFilters();
                $anio = $filtros['year'] ?? date('Y');

                // Ingresos agrupados por mes
                $incomes = Income::where('user_id', $userId)
                    ->where('year', $anio)
                    ->selectRaw('month, SUM(amount) as total')
                    ->groupBy('month')
                    ->get();

                // Gastos agrupados por mes
                $expenses = Expense::where('user_id', $userId)
                    ->where('year', $anio)
                    ->selectRaw('month, SUM(amount) as total')
                    ->groupBy('month')
                    ->get();

                // Construimos tabla mes a mes
                return collect(range(1, 12))->map(function ($month) use ($incomes, $expenses, $anio) {
                    $income = $incomes->firstWhere('month', $month)?->total ?? 0;
                    $expense = $expenses->firstWhere('month', $month)?->total ?? 0;

                    return [
                        'year' => $anio,
                        'month' => $month,
                        'total_incomes' => $income,
                        'total_expenses' => $expense,
                        'balance' => $income - $expense,
                    ];
                });
            });
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GraficoResumenAnualWidget::class,
            GraficoGastosPorCategoria::class,
        ];
    }
}

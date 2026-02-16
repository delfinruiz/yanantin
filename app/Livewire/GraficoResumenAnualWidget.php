<?php

namespace App\Livewire;

use App\Models\Income;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class GraficoResumenAnualWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    public function getHeading(): string
    {
        return __('resumen_anual');
    }

    protected function getType(): string
    {
        return 'line'; // o "bar"
    }

    // Controla la altura del gráfico
    //protected ?string $maxHeight = '400px';

    // full width
    // protected int | string | array $columnSpan = 'full';


    protected function getData(): array
    {
        $userId = Auth::id();
        $year = $this->filters['year'] ?? date('Y');

        // === INGRESOS ===
        $incomes = Income::where('user_id', $userId)
            ->where('year', $year)
            ->selectRaw('month, SUM(amount) AS total')
            ->groupBy('month')
            ->pluck('total', 'month');

        // === GASTOS ===
        $expenses = Expense::where('user_id', $userId)
            ->where('year', $year)
            ->selectRaw('month, SUM(amount) AS total')
            ->groupBy('month')
            ->pluck('total', 'month');

        // === MESES 1..12 ===
        $months = range(1, 12);

        return [
            'responsive' => true,
            'animation' => [
                'duration' => 1000,
                'easing' => 'ease',
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 100000,
                        'callback' => 'function(value) { return value.toLocaleString(); }',
                    ],
                ],
            ],
            'labels' => array_map(fn($m) => __(date('F', mktime(0, 0, 0, $m))), $months),
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => array_map(fn($m) => $incomes[$m] ?? 0, $months),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 3,
                ],
                [
                    'label' => 'Gastos',
                    'data' => array_map(fn($m) => $expenses[$m] ?? 0, $months),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 3,
                ],
            ]
        ];
    }
}

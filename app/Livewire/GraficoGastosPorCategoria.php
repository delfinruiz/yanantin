<?php

namespace App\Livewire;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class GraficoGastosPorCategoria extends ChartWidget
{
    use InteractsWithPageFilters;

    public function getHeading(): string
    {
        return __('expense_categories');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    //protected ?string $maxHeight = '400px';
    //protected int|string|array $columnSpan = 'full';

protected function getFilters(): ?array
{
    return [
        ''       => __('all_months'),
        '1'      => __('January'),
        '2'      => __('February'),
        '3'      => __('March'),
        '4'      => __('April'),
        '5'      => __('May'),
        '6'      => __('June'),
        '7'      => __('July'),
        '8'      => __('August'),
        '9'      => __('September'),
        '10'     => __('October'),
        '11'     => __('November'),
        '12'     => __('December'),
    ];
}

    protected function getData(): array
    {
        $userId = Auth::id();
        $year = $this->filters['year'] ?? date('Y');

        $month = $this->filter;

        $categories = Expense::where('expenses.user_id', $userId)
            ->where('expenses.year', $year)
            ->when($month, fn($q) => $q->where('expenses.month', $month))
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as categoria, SUM(expenses.amount) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        return [
            'labels' => $categories->keys()->toArray(),
            'datasets' => [
                [
                    'label' => 'Gastos por categoría',
                    'data' => $categories->values()->toArray(),
                    'backgroundColor' => [
                        'rgba(55, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(25, 159, 64, 0.7)',
                        'rgba(55, 203, 207, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(120, 119, 15, 0.7)',
                        'rgba(150, 203, 207, 0.7)',
                        'rgba(255, 19, 64, 0.7)',
                    ],
                ],
            ],
        ];
    }
}
